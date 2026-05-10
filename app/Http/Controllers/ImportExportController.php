<?php

namespace App\Http\Controllers;

use App\Models\ApiModel;
use App\Models\ApiModelEntry;
use App\Models\DisplayBlock;
use App\Models\DisplayColumn;
use App\Models\DisplayElement;
use App\Models\DisplaySubColumn;
use App\Models\JsonDecoderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImportExportController extends Controller
{
    // =========================================================================
    // Index
    // =========================================================================

    public function index()
    {
        $apiModels     = ApiModel::with('decoderModel')->orderBy('name')->get();
        $decoderModels = JsonDecoderModel::orderBy('name')->get();
        $pendingImport = session('import_pending');

        return view('import-export.index', compact('apiModels', 'decoderModels', 'pendingImport'));
    }

    // =========================================================================
    // Export
    // =========================================================================

    public function export(Request $request)
    {
        $request->validate([
            'api_model_id'     => 'nullable|exists:api_models,id',
            'decoder_model_id' => 'nullable|exists:json_decoder_models,id',
        ]);

        if (!$request->filled('api_model_id') && !$request->filled('decoder_model_id')) {
            return back()->withErrors(['export' => 'Sélectionnez au moins un modèle à exporter.']);
        }

        $payload = [
            'version'     => '1',
            'exported_at' => now()->toIso8601String(),
        ];

        if ($request->filled('api_model_id')) {
            $m = ApiModel::with('entries')->findOrFail($request->api_model_id);

            $payload['api_model'] = [
                'name'                => $m->name,
                'description'         => $m->description,
                'linked_decoder_name' => $m->decoderModel?->name,
                'entries'             => $m->entries->map(fn($e) => [
                    'api_name'    => $e->api_name,
                    'path'        => $e->path,
                    'method'      => $e->method,
                    'version'     => $e->version,
                    'parameters'  => $e->parameters,
                    'enabled'     => $e->enabled,
                    'min_version' => $e->min_version,
                    'max_version' => $e->max_version,
                ])->values()->toArray(),
            ];
        }

        if ($request->filled('decoder_model_id')) {
            $m = JsonDecoderModel::with('blocks.elements.columns.subColumns')
                ->findOrFail($request->decoder_model_id);
            $payload['decoder_model'] = $this->serializeDecoder($m);
        }

        $parts    = array_filter([
            $payload['api_model']['name']     ?? null,
            $payload['decoder_model']['name'] ?? null,
        ]);
        $filename = 'synomanager-' . Str::slug(implode('-', $parts), '-') . '-' . now()->format('Ymd') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    // =========================================================================
    // Import — step 1: validate + preview
    // =========================================================================

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json,txt|max:4096',
        ]);

        $content = file_get_contents($request->file('file')->path());
        $data    = json_decode($content, true);

        if (!is_array($data) || ($data['version'] ?? null) !== '1') {
            return back()->withErrors(['import' => 'Fichier invalide ou format non reconnu (version attendue : 1).']);
        }

        $hasApi     = isset($data['api_model'])     && is_array($data['api_model']);
        $hasDecoder = isset($data['decoder_model']) && is_array($data['decoder_model']);

        if (!$hasApi && !$hasDecoder) {
            return back()->withErrors(['import' => 'Le fichier ne contient ni modèle API ni décodeur.']);
        }

        $preview  = [];
        $warnings = [];

        if ($hasDecoder) {
            $name = $data['decoder_model']['name'] ?? '(sans nom)';
            $preview['decoder_model'] = [
                'name'         => $name,
                'blocks_count' => count($data['decoder_model']['blocks'] ?? []),
                'exists'       => JsonDecoderModel::where('name', $name)->exists(),
            ];
        }

        if ($hasApi) {
            $name = $data['api_model']['name'] ?? '(sans nom)';
            $preview['api_model'] = [
                'name'          => $name,
                'entries_count' => count($data['api_model']['entries'] ?? []),
                'exists'        => ApiModel::where('name', $name)->exists(),
            ];

            $linkedName = $data['api_model']['linked_decoder_name'] ?? null;
            if ($linkedName) {
                $inFile = $hasDecoder && ($data['decoder_model']['name'] ?? null) === $linkedName;
                $inDb   = JsonDecoderModel::where('name', $linkedName)->exists();

                $preview['api_model']['linked_decoder_name']       = $linkedName;
                $preview['api_model']['linked_decoder_resolvable'] = $inFile || $inDb;

                if (!$inFile && !$inDb) {
                    $warnings[] = "Le modèle API référence le décodeur « {$linkedName} » qui n'est ni dans le fichier ni en base de données. La liaison ne pourra pas être établie.";
                }
            }
        }

        session(['import_pending' => [
            'data'     => $data,
            'preview'  => $preview,
            'warnings' => $warnings,
        ]]);

        return redirect()->route('import-export.index');
    }

    // =========================================================================
    // Import — step 2: execute
    // =========================================================================

    public function importConfirm(Request $request)
    {
        $pending = session('import_pending');

        if (!$pending) {
            return redirect()->route('import-export.index')
                ->withErrors(['import' => 'Session expirée. Veuillez ré-importer le fichier.']);
        }

        session()->forget('import_pending');

        $data            = $pending['data'];
        $skipDecoderLink = (bool) $request->input('skip_decoder_link', false);
        $messages        = [];

        // Import decoder first so we can immediately link it
        $decoderModel = null;

        if (isset($data['decoder_model'])) {
            $decoderModel = $this->importDecoder($data['decoder_model']);
            $messages[]   = "Décodeur « {$decoderModel->name} » importé.";
        }

        if (isset($data['api_model'])) {
            [$apiModel, $linked] = $this->importApiModel(
                $data['api_model'],
                $skipDecoderLink ? null : $decoderModel
            );

            $count = count($data['api_model']['entries'] ?? []);
            $msg   = "Modèle API « {$apiModel->name} » importé ({$count} entrée(s)).";

            if ($linked) {
                $msg .= " Lié au décodeur « {$decoderModel->name} ».";
            } elseif (!$skipDecoderLink && isset($data['api_model']['linked_decoder_name'])) {
                $msg .= ' (liaison décodeur non établie)';
            }

            $messages[] = $msg;
        }

        return redirect()->route('import-export.index')
            ->with('success', implode(' — ', $messages));
    }

    public function importCancel()
    {
        session()->forget('import_pending');
        return redirect()->route('import-export.index');
    }

    // =========================================================================
    // Serialization helpers
    // =========================================================================

    private function serializeDecoder(JsonDecoderModel $model): array
    {
        return [
            'name'        => $model->name,
            'description' => $model->description,
            'blocks'      => $model->blocks->map(fn($b) => [
                'title'       => $b->title,
                'description' => $b->description,
                'icon'        => $b->icon,
                'sort_order'  => $b->sort_order,
                'elements'    => $b->elements->map(fn($e) => [
                    'type'               => $e->type,
                    'label'              => $e->label,
                    'api_name'           => $e->api_name,
                    'json_path'          => $e->json_path,
                    'transformer'        => $e->transformer,
                    'transformer_config' => $e->transformer_config,
                    'sort_order'         => $e->sort_order,
                    'columns'            => $e->columns->map(fn($c) => [
                        'type'               => $c->type,
                        'label'              => $c->label,
                        'json_path'          => $c->json_path,
                        'transformer'        => $c->transformer,
                        'transformer_config' => $c->transformer_config,
                        'sort_order'         => $c->sort_order,
                        'sub_columns'        => $c->subColumns->map(fn($s) => [
                            'label'              => $s->label,
                            'json_path'          => $s->json_path,
                            'transformer'        => $s->transformer,
                            'transformer_config' => $s->transformer_config,
                            'sort_order'         => $s->sort_order,
                        ])->values()->toArray(),
                    ])->values()->toArray(),
                ])->values()->toArray(),
            ])->values()->toArray(),
        ];
    }

    private function importDecoder(array $data): JsonDecoderModel
    {
        $name = $data['name'];
        if (JsonDecoderModel::where('name', $name)->exists()) {
            $name .= ' (importé ' . now()->format('d/m H:i') . ')';
        }

        $model = JsonDecoderModel::create([
            'name'        => $name,
            'description' => $data['description'] ?? null,
        ]);

        foreach ($data['blocks'] ?? [] as $blockData) {
            $block = $model->blocks()->create([
                'title'       => $blockData['title'],
                'description' => $blockData['description'] ?? null,
                'icon'        => $blockData['icon'] ?? null,
                'sort_order'  => $blockData['sort_order'] ?? 0,
            ]);

            foreach ($blockData['elements'] ?? [] as $elData) {
                $element = $block->elements()->create([
                    'type'               => $elData['type'],
                    'label'              => $elData['label'],
                    'api_name'           => $elData['api_name'] ?? null,
                    'json_path'          => $elData['json_path'] ?? [],
                    'internal_key'       => (string) Str::uuid(),
                    'transformer'        => $elData['transformer'] ?? null,
                    'transformer_config' => $elData['transformer_config'] ?? null,
                    'sort_order'         => $elData['sort_order'] ?? 0,
                ]);

                foreach ($elData['columns'] ?? [] as $colData) {
                    $column = $element->columns()->create([
                        'type'               => $colData['type'],
                        'label'              => $colData['label'],
                        'json_path'          => $colData['json_path'] ?? [],
                        'internal_key'       => (string) Str::uuid(),
                        'transformer'        => $colData['transformer'] ?? null,
                        'transformer_config' => $colData['transformer_config'] ?? null,
                        'sort_order'         => $colData['sort_order'] ?? 0,
                    ]);

                    foreach ($colData['sub_columns'] ?? [] as $subData) {
                        $column->subColumns()->create([
                            'label'              => $subData['label'],
                            'json_path'          => $subData['json_path'] ?? [],
                            'internal_key'       => (string) Str::uuid(),
                            'transformer'        => $subData['transformer'] ?? null,
                            'transformer_config' => $subData['transformer_config'] ?? null,
                            'sort_order'         => $subData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        return $model;
    }

    private function importApiModel(array $data, ?JsonDecoderModel $decoderFromFile): array
    {
        $name = $data['name'];
        if (ApiModel::where('name', $name)->exists()) {
            $name .= ' (importé ' . now()->format('d/m H:i') . ')';
        }

        // If decoder not provided from file, try to find by name in DB
        $decoderModel = $decoderFromFile;
        if ($decoderModel === null && isset($data['linked_decoder_name'])) {
            $decoderModel = JsonDecoderModel::where('name', $data['linked_decoder_name'])->first();
        }

        $apiModel = ApiModel::create([
            'name'             => $name,
            'description'      => $data['description'] ?? null,
            'decoder_model_id' => $decoderModel?->id,
        ]);

        foreach ($data['entries'] ?? [] as $entryData) {
            $apiModel->entries()->create([
                'api_name'    => $entryData['api_name'],
                'path'        => $entryData['path']        ?? 'entry.cgi',
                'method'      => $entryData['method']      ?? 'get',
                'version'     => $entryData['version']     ?? null,
                'parameters'  => $entryData['parameters']  ?? null,
                'enabled'     => $entryData['enabled']     ?? true,
                'min_version' => $entryData['min_version'] ?? 1,
                'max_version' => $entryData['max_version'] ?? 99,
            ]);
        }

        return [$apiModel, $decoderModel !== null];
    }
}
