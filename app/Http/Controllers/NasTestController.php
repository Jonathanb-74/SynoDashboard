<?php

namespace App\Http\Controllers;

use App\Models\ApiModel;
use App\Models\ApiModelEntry;
use App\Models\NasDevice;
use App\Services\IngestionService;
use App\Services\SynologyApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class NasTestController extends Controller
{
    public function __construct(
        private SynologyApiClient $apiClient,
        private IngestionService  $ingestionService
    ) {}

    public function index()
    {
        return view('test.index');
    }

    public function run(Request $request)
    {
        $request->validate([
            'url'        => 'required|url',
            'username'   => 'required|string',
            'password'   => 'required|string',
            'ssl_verify' => 'nullable|boolean',
        ]);

        $sslVerify = $request->boolean('ssl_verify', false);

        try {
            $this->apiClient->connect($request->input('url'), $sslVerify);
            $apiList = $this->apiClient->getApiInfo();

            $this->apiClient->authenticate(
                $request->input('username'),
                $request->input('password')
            );

            $payload    = [];
            $modelUsed  = null;
            $errorCount = 0;
            $debugInfo  = [];

            try {
                // Toujours identifier le NAS via ces deux APIs
                $phase1 = [];
                foreach ([
                    'SYNO.Core.System'  => ['path' => 'entry.cgi', 'method' => 'info', 'version' => 1],
                    'SYNO.Core.Network' => ['path' => 'entry.cgi', 'method' => 'get',  'version' => 1],
                ] as $apiName => $cfg) {
                    try {
                        $phase1[$apiName] = $this->apiClient->callApiDirect(
                            $apiName, $cfg['path'], $cfg['method'], $cfg['version']
                        );
                    } catch (RuntimeException $e) {
                        $phase1[$apiName] = ['_error' => $e->getMessage()];
                    }
                }

                $nasIdentifier = $this->extractNasIdentifier($phase1);
                $debugInfo['serial'] = $nasIdentifier['serial'];

                // Chercher le NAS et son modèle
                $nas = NasDevice::where('serial', $nasIdentifier['serial'])->first();

                $debugInfo['nas_found']    = $nas !== null;
                $debugInfo['api_model_id'] = $nas?->api_model_id;

                if (!$nas || !$nas->api_model_id) {
                    // --- Nouveau NAS ou sans modèle ---
                    // Payload : header + api_list uniquement (sans réponses API pour des raisons de sécurité)
                    $debugInfo['mode'] = 'registration';

                    $payload = [
                        'agent_version'  => 'test-console',
                        'collected_at'   => now()->toIso8601String(),
                        'nas_identifier' => $nasIdentifier,
                        'api_list'       => $apiList,
                    ];

                } else {
                    // --- NAS connu avec modèle : collecter selon les entrées actives ---
                    $debugInfo['mode'] = 'collection';

                    $modelUsed    = ApiModel::find($nas->api_model_id);
                    $modelEntries = ApiModelEntry::where('api_model_id', $nas->api_model_id)
                        ->where('enabled', true)
                        ->orderBy('api_name')
                        ->get();

                    $debugInfo['model_name']     = $modelUsed?->name;
                    $debugInfo['entries_active']  = $modelEntries->count();
                    $debugInfo['entries_total']   = ApiModelEntry::where('api_model_id', $nas->api_model_id)->count();

                    $responses = [];
                    foreach ($modelEntries as $entry) {
                        $params = is_array($entry->parameters) ? $entry->parameters : [];
                        try {
                            $responses[$entry->api_name] = $this->apiClient->callApiDirect(
                                $entry->api_name,
                                $entry->path,
                                $entry->method,
                                $entry->version ?? $entry->min_version,
                                $params
                            );
                        } catch (RuntimeException $e) {
                            $responses[$entry->api_name] = ['_error' => $e->getMessage()];
                            $errorCount++;
                        }
                    }

                    // Payload : header + responses uniquement (pas d'api_list)
                    $payload = [
                        'agent_version'  => 'test-console',
                        'collected_at'   => now()->toIso8601String(),
                        'nas_identifier' => $nasIdentifier,
                        'responses'      => $responses,
                    ];
                }

            } finally {
                $this->apiClient->logout();
            }

            $result = $this->ingestionService->ingest($payload);

            // Persist the NAS URL so it can be used for direct browser links
            $result['nas']->update(['url' => rtrim($request->input('url'), '/')]);

            $debugInfo['api_calls'] = count($payload['responses'] ?? []);

            return back()
                ->with('test_result', $result)
                ->with('test_payload', $payload)
                ->with('test_model', $modelUsed)
                ->with('test_error_count', $errorCount)
                ->with('test_debug', $debugInfo)
                ->withInput($request->except('password'));

        } catch (\Throwable $e) {
            return back()
                ->withErrors(['api' => $e->getMessage()])
                ->withInput($request->except('password'));
        }
    }

    private function extractNasIdentifier(array $responses): array
    {
        $system  = $responses['SYNO.Core.System']  ?? [];
        $network = $responses['SYNO.Core.Network'] ?? [];

        return [
            'serial'      => $system['serial']       ?? 'UNKNOWN-' . uniqid(),
            'model'       => $system['model']         ?? 'Unknown',
            'server_name' => $network['server_name'] ?? 'Unknown',
            'dsm_version' => $system['firmware_ver'] ?? 'Unknown',
        ];
    }
}
