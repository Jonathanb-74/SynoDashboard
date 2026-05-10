<?php

namespace App\Http\Controllers;

use App\Models\ApiMethodOption;
use App\Models\ApiModelEntry;
use App\Services\SynologyApiClient;
use Illuminate\Http\Request;
use RuntimeException;

class ApiDebugController extends Controller
{
    public function __construct(private SynologyApiClient $apiClient) {}

    public function index(Request $request)
    {
        $entry = $request->filled('entry_id')
            ? ApiModelEntry::with('apiModel')->find($request->query('entry_id'))
            : null;

        $debugMethods = ApiMethodOption::where('debug_enabled', true)
            ->orderBy('sort_order')
            ->pluck('name');

        return view('debug.api-method', compact('entry', 'debugMethods'));
    }

    public function probe(Request $request)
    {
        $request->validate([
            'url'        => 'required|url',
            'username'   => 'required|string',
            'password'   => 'required|string',
            'ssl_verify' => 'nullable|boolean',
            'entry_id'   => 'required|exists:api_model_entries,id',
        ]);

        $entry      = ApiModelEntry::with('apiModel')->findOrFail($request->input('entry_id'));
        $sslVerify  = $request->boolean('ssl_verify', false);
        $safeMethods = ApiMethodOption::where('debug_enabled', true)
            ->orderBy('sort_order')
            ->pluck('name');

        $results = [];

        try {
            $this->apiClient->connect($request->input('url'), $sslVerify);
            $this->apiClient->authenticate($request->input('username'), $request->input('password'));

            foreach ($safeMethods as $method) {
                try {
                    $data = $this->apiClient->callApiDirect(
                        $entry->api_name,
                        $entry->path,
                        $method,
                        $entry->max_version
                    );
                    $results[$method] = ['success' => true, 'data' => $data];
                } catch (RuntimeException $e) {
                    $results[$method] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['connection' => $e->getMessage()])
                ->withInput($request->except('password'));
        } finally {
            $this->apiClient->logout();
        }

        return back()
            ->with('probe_results', $results)
            ->with('probe_entry_id', $entry->id)
            ->withInput($request->except('password'));
    }

    public function apply(Request $request)
    {
        $request->validate([
            'entry_id' => 'required|exists:api_model_entries,id',
            'method'   => 'required|string|max:50',
        ]);

        $entry = ApiModelEntry::findOrFail($request->input('entry_id'));
        $old   = $entry->method;
        $entry->update(['method' => $request->input('method')]);

        return redirect()
            ->route('api-models.show', $entry->api_model_id)
            ->with('success', "Méthode de « {$entry->api_name} » mise à jour : {$old} → {$entry->method}.");
    }
}
