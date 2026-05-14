<?php

namespace App\Services;

use App\Models\ApiModel;
use App\Models\ApiModelEntry;
use App\Models\NasDevice;
use App\Models\NasSnapshot;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IngestionService
{
    public function ingest(array $payload): array
    {
        $this->validatePayload($payload);

        return DB::transaction(function () use ($payload) {
            $identifier = $payload['nas_identifier'];
            $isNew      = false;

            $nas = NasDevice::where('serial', $identifier['serial'])->first();

            if ($nas === null) {
                $nas   = new NasDevice(['serial' => $identifier['serial'], 'status' => 'pending']);
                $isNew = true;
            }

            $nas->name            = $identifier['server_name'];
            $nas->model           = $identifier['model'];
            $nas->dsm_version     = $identifier['dsm_version'];
            $nas->last_contact_at = now();
            $nas->save();

            if (array_key_exists('api_list', $payload)) {
                $apiList = $payload['api_list'] ?? [];
                $this->syncApiList($nas, $apiList);
                $this->syncApiModel($nas, $identifier['dsm_version'], $apiList);
            }

            $snapshot = NasSnapshot::create([
                'nas_id'        => $nas->id,
                'agent_version' => $payload['agent_version'] ?? null,
                'collected_at'  => $payload['collected_at'] ?? now(),
                'raw_json'      => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'decoded_cache' => null,
            ]);

            $config = $this->buildCollectionConfig($nas);

            return ['nas' => $nas, 'snapshot' => $snapshot, 'is_new' => $isNew, 'collection_config' => $config];
        });
    }

    private function buildCollectionConfig(NasDevice $nas): ?array
    {
        if ($nas->status !== 'approved' || $nas->api_model_id === null) {
            return null;
        }

        $nas->loadMissing(['apiModel.entries', 'availableApis']);

        $availableByName = $nas->availableApis->keyBy('api_name');

        $apis = $nas->apiModel->entries
            ->where('enabled', true)
            ->filter(fn($entry) => $availableByName->has($entry->api_name))
            ->map(fn($entry) => [
                'api'     => $entry->api_name,
                'method'  => $entry->method,
                'version' => min($entry->max_version, $availableByName->get($entry->api_name)->max_version),
            ])
            ->values()
            ->toArray();

        return [
            'interval_seconds' => $nas->collection_frequency * 60,
            'apis'             => $apis,
        ];
    }

    private function syncApiModel(NasDevice $nas, string $dsmVersion, array $apiList): void
    {
        if (empty($apiList)) {
            return;
        }

        // Build a canonical fingerprint of the api_list to detect exact matches
        $incoming = collect($apiList)
            ->map(fn($info, $name) => $name . '|' . ($info['path'] ?? '') . '|' . ($info['minVersion'] ?? 1) . '|' . ($info['maxVersion'] ?? 1))
            ->sort()
            ->implode(',');

        // Check all existing ApiModels for an exact fingerprint match
        $matched = null;
        foreach (ApiModel::with('entries')->get() as $candidate) {
            $existing = $candidate->entries
                ->map(fn($e) => $e->api_name . '|' . $e->path . '|' . $e->min_version . '|' . $e->max_version)
                ->sort()
                ->implode(',');

            if ($existing === $incoming) {
                $matched = $candidate;
                break;
            }
        }

        if ($matched === null) {
            // No exact match — create a new ApiModel named after the DSM version
            $baseName = $dsmVersion;
            $name     = $baseName;
            $suffix   = 1;

            // Ensure unique name if the same DSM version exists with different APIs
            while (ApiModel::where('name', $name)->exists()) {
                $suffix++;
                $name = $baseName . ' (' . $suffix . ')';
            }

            $matched = ApiModel::create([
                'name'        => $name,
                'description' => 'Créé automatiquement à partir de la liste API reçue le ' . now()->format('d/m/Y'),
            ]);

            $entries = [];
            foreach ($apiList as $apiName => $info) {
                $entries[] = [
                    'api_model_id' => $matched->id,
                    'api_name'     => $apiName,
                    'path'         => $info['path'] ?? 'entry.cgi',
                    'method'       => self::guessMethod($apiName),
                    'parameters'   => null,
                    'enabled'      => true,
                    'min_version'  => (int) ($info['minVersion'] ?? 1),
                    'max_version'  => (int) ($info['maxVersion'] ?? 1),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            foreach (array_chunk($entries, 500) as $chunk) {
                ApiModelEntry::insert($chunk);
            }
        }

        $changed         = false;
        $apiModelChanged = $nas->api_model_id !== $matched->id;

        if ($apiModelChanged) {
            $nas->api_model_id = $matched->id;
            $changed = true;
        }

        // When the API model changes automatically, always follow its linked decoder
        if ($apiModelChanged && $matched->decoder_model_id !== null) {
            $nas->decoder_model_id = $matched->decoder_model_id;
            $changed = true;
        }

        if ($changed) {
            $nas->save();
        }
    }

    private function syncApiList(NasDevice $nas, array $apiList): void
    {
        DB::table('nas_api_available')->where('nas_id', $nas->id)->delete();

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($apiList as $apiName => $info) {
            $rows[] = [
                'nas_id'      => $nas->id,
                'api_name'    => $apiName,
                'path'        => $info['path'] ?? 'entry.cgi',
                'min_version' => (int) ($info['minVersion'] ?? 1),
                'max_version' => (int) ($info['maxVersion'] ?? 1),
                'created_at'  => $now,
            ];
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('nas_api_available')->insert($chunk);
            }
        }
    }

    private static function guessMethod(string $apiName): string
    {
        $map = [
            'SYNO.Core.System'         => 'info',
            'SYNO.Core.Network'        => 'get',
            'SYNO.Storage.CGI.Storage' => 'load_info',
            'SYNO.Core.Package'        => 'list',
            'SYNO.Core.Upgrade'        => 'check',
        ];

        return $map[$apiName] ?? 'get';
    }

    private function validatePayload(array $payload): void
    {
        $required = ['nas_identifier'];

        foreach ($required as $key) {
            if (!isset($payload[$key])) {
                throw new InvalidArgumentException("Missing required payload key: '{$key}'.");
            }
        }

        $idRequired = ['serial', 'model', 'server_name', 'dsm_version'];

        foreach ($idRequired as $key) {
            if (empty($payload['nas_identifier'][$key])) {
                throw new InvalidArgumentException("Missing nas_identifier field: '{$key}'.");
            }
        }
    }
}
