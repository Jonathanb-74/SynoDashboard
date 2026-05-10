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

        return view('api-models.show', compact('apiModel'));
    }

    public function edit(ApiModel $apiModel)
    {
        $apiModel->load(['entries', 'decoderModel']);
        $decoderModels = JsonDecoderModel::orderBy('name')->get();
        $methods       = ApiMethodOption::orderBy('sort_order')->orderBy('name')->pluck('name');

        return view('api-models.edit', compact('apiModel', 'decoderModels', 'methods'));
    }

    public function update(Request $request, ApiModel $apiModel)
    {
        $request->validate([
            'name'             => 'required|string|max:191|unique:api_models,name,' . $apiModel->id,
            'description'      => 'nullable|string',
            'decoder_model_id' => 'nullable|exists:json_decoder_models,id',
            'entries_json'     => 'nullable|string',
        ]);

        $apiModel->update($request->only('name', 'description', 'decoder_model_id'));

        $apiModel->entries()->delete();
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
