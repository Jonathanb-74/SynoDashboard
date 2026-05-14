<?php

namespace App\Http\Controllers;

use App\Models\ApiMethodOption;
use App\Models\ApiModel;
use App\Models\ApiModelEntry;
use App\Models\JsonDecoderModel;
use Illuminate\Http\Request;

class ApiModelController extends Controller
{
    public function index()
    {
        $models = ApiModel::with('decoderModel')->withCount('entries')->orderBy('name')->get();

        return view('api-models.index', compact('models'));
    }

    public function create()
    {
        $decoderModels = JsonDecoderModel::orderBy('name')->get();
        $methods       = ApiMethodOption::orderBy('sort_order')->orderBy('name')->pluck('name');

        return view('api-models.create', compact('decoderModels', 'methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:191|unique:api_models,name',
            'description'      => 'nullable|string',
            'decoder_model_id' => 'nullable|exists:json_decoder_models,id',
            'entries_json'     => 'nullable|string',
        ]);

        $model = ApiModel::create($request->only('name', 'description', 'decoder_model_id'));

        $entries = json_decode($request->input('entries_json', '[]'), true) ?? [];
        $this->syncEntries($model, $entries);

        return redirect()->route('api-models.show', $model)
            ->with('success', "Le modèle API « {$model->name} » a été créé.");
    }

    public function show(ApiModel $apiModel)
    {
        $apiModel->load(['entries', 'decoderModel']);

        $apiNames = $apiModel->entries->pluck('api_name');

        $otherModelsByApi = \App\Models\ApiModelEntry::whereIn('api_name', $apiNames)
            ->where('api_model_id', '!=', $apiModel->id)
            ->with('apiModel:id,name')
            ->get()
            ->filter(fn($e) => $e->apiModel !== null)
            ->groupBy('api_name')
            ->map(fn($entries) => $entries
                ->map(fn($e) => ['id' => $e->apiModel->id, 'name' => $e->apiModel->name])
                ->unique('id')
                ->values()
            );

        return view('api-models.show', compact('apiModel', 'otherModelsByApi'));
    }

    public function duplicate(ApiModel $apiModel)
    {
        $apiModel->load('entries');

        $copy = ApiModel::create([
            'name'             => $apiModel->name . ' (copie)',
            'description'      => $apiModel->description,
            'decoder_model_id' => $apiModel->decoder_model_id,
        ]);

        $rows = $apiModel->entries->map(fn($e) => [
            'api_model_id' => $copy->id,
            'api_name'     => $e->api_name,
            'path'         => $e->path,
            'method'       => $e->method,
            'version'      => $e->version,
            'parameters'   => $e->parameters ? json_encode($e->parameters) : null,
            'enabled'      => $e->enabled,
            'min_version'  => $e->min_version,
            'max_version'  => $e->max_version,
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->toArray();

        foreach (array_chunk($rows, 500) as $chunk) {
            ApiModelEntry::insert($chunk);
        }

        return redirect()->route('api-models.show', $copy)
            ->with('success', "Modèle API dupliqué : « {$copy->name} ».");
    }

    public function propagateEntry(Request $request, ApiModel $apiModel)
    {
        $request->validate([
            'api_name'            => 'required|string',
            'target_model_ids'    => 'nullable|array',
            'target_model_ids.*'  => 'exists:api_models,id',
        ]);

        if (empty($request->target_model_ids)) {
            return back()->with('error', 'Aucun modèle cible sélectionné.');
        }

        $source = $apiModel->entries()->where('api_name', $request->api_name)->first();

        if (!$source) {
            return back()->with('error', 'Entrée source introuvable.');
        }

        $count = ApiModelEntry::whereIn('api_model_id', $request->target_model_ids)
            ->where('api_name', $request->api_name)
            ->update([
                'path'        => $source->path,
                'method'      => $source->method,
                'min_version' => $source->min_version,
                'max_version' => $source->max_version,
                'parameters'  => $source->parameters ? json_encode($source->parameters) : null,
                'enabled'     => $source->enabled,
                'updated_at'  => now(),
            ]);

        return back()->with('success', "Paramètres de « {$request->api_name} » propagés vers {$count} entrée(s).");
    }

    public function edit(Request $request, ApiModel $apiModel)
    {
        $filterActive = $request->query('filter') === 'active';

        $apiModel->load(['decoderModel']);
        $totalCount = $filterActive ? $apiModel->entries()->count() : null;
        $query = $apiModel->entries();
        if ($filterActive) {
            $query->where('enabled', true);
        }
        $apiModel->setRelation('entries', $query->get());
        $decoderModels = JsonDecoderModel::orderBy('name')->get();
        $methods       = ApiMethodOption::orderBy('sort_order')->orderBy('name')->pluck('name');

        return view('api-models.edit', compact('apiModel', 'decoderModels', 'methods', 'filterActive', 'totalCount'));
    }

    public function update(Request $request, ApiModel $apiModel)
    {
        $request->validate([
            'name'             => 'required|string|max:191|unique:api_models,name,' . $apiModel->id,
            'description'      => 'nullable|string',
            'decoder_model_id' => 'nullable|exists:json_decoder_models,id',
            'entries_json'     => 'nullable|string',
            'filter_active'    => 'nullable|boolean',
        ]);

        $apiModel->update($request->only('name', 'description', 'decoder_model_id'));

        if ($request->boolean('filter_active')) {
            // Active-only mode: only replace enabled entries, keep disabled ones intact
            $apiModel->entries()->where('enabled', true)->delete();
        } else {
            $apiModel->entries()->delete();
        }
        $entries = json_decode($request->input('entries_json', '[]'), true) ?? [];
        $this->syncEntries($apiModel, $entries);

        // Propagate the new decoder to all NAS using this ApiModel that have no decoder yet
        if ($request->filled('decoder_model_id')) {
            $apiModel->nasDevices()
                ->whereNull('decoder_model_id')
                ->update(['decoder_model_id' => $request->input('decoder_model_id')]);
        }

        return redirect()->route('api-models.show', $apiModel)
            ->with('success', "Le modèle API « {$apiModel->name} » a été mis à jour.");
    }

    public function createDecoder(ApiModel $apiModel)
    {
        $decoder = JsonDecoderModel::create([
            'name' => $apiModel->name . ' - décodeur',
        ]);

        $apiModel->update(['decoder_model_id' => $decoder->id]);

        return redirect()->route('decoder-models.edit', $decoder)
            ->with('success', "Décodeur « {$decoder->name} » créé et lié au modèle API.");
    }

    public function destroy(ApiModel $apiModel)
    {
        $name = $apiModel->name;
        $apiModel->delete();

        return redirect()->route('api-models.index')
            ->with('success', "Le modèle API « {$name} » a été supprimé.");
    }

    private function syncEntries(ApiModel $model, array $entries): void
    {
        foreach ($entries as $entry) {
            if (empty($entry['api_name'])) {
                continue;
            }

            ApiModelEntry::create([
                'api_model_id' => $model->id,
                'api_name'     => $entry['api_name'],
                'path'         => $entry['path'] ?? 'entry.cgi',
                'method'       => $entry['method'] ?? 'query',
                'version'      => ($entry['version'] ?? '') !== '' ? (int) $entry['version'] : null,
                'parameters'   => !empty($entry['parameters']) ? json_decode($entry['parameters'], true) : null,
                'enabled'      => isset($entry['enabled']) ? (bool) $entry['enabled'] : true,
                'min_version'  => (int) ($entry['min_version'] ?? 1),
                'max_version'  => (int) ($entry['max_version'] ?? 99),
            ]);
        }
    }
}
