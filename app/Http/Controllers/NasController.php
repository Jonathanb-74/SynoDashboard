<?php

namespace App\Http\Controllers;

use App\Models\ApiModel;
use App\Models\GlobalAttributeMapping;
use App\Models\JsonDecoderModel;
use App\Models\NasCustomFieldDefinition;
use App\Models\NasCustomFieldValue;
use App\Models\NasDevice;
use App\Models\NasViewTable;
use App\Services\JsonDecoderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NasController extends Controller
{
    public function __construct(private JsonDecoderService $decoderService) {}

    public function index(Request $request)
    {
        $allViews = NasViewTable::with('columns')->orderBy('name')->get();

        if ($request->filled('view')) {
            $configuredView = $allViews->firstWhere('id', (int) $request->query('view'));
        } else {
            $configuredView = $allViews->firstWhere('is_nas_page_default', true);
        }

        $query = NasDevice::with(['apiModel', 'decoderModel', 'latestSnapshot'])
            ->withCount('snapshots')
            ->orderByDesc('last_contact_at');

        $needsCustomFields    = $configuredView && $configuredView->columns->where('source', 'custom_field')->isNotEmpty();
        $needsGlobalAttributes = $configuredView && $configuredView->columns->where('source', 'global_attribute')->isNotEmpty();

        if ($needsCustomFields) {
            $query->with('customFieldValues');
        }
        if ($needsGlobalAttributes) {
            $query->with('latestSnapshot');
        }

        $nasList              = $query->get();
        $customFieldDefs      = NasCustomFieldDefinition::orderBy('position')->get();
        $globalAttributeValues = $needsGlobalAttributes
            ? $this->resolveGlobalAttributeValues($nasList, $configuredView->columns)
            : [];

        return view('nas.index', compact('nasList', 'configuredView', 'allViews', 'customFieldDefs', 'globalAttributeValues'));
    }

    public function show(Request $request, NasDevice $nas)
    {
        $nas->load(['apiModel', 'decoderModel.blocks.elements.columns.subColumns', 'approvedBy', 'availableApis']);
        $nas->loadCount('snapshots');

        $snapshots = $nas->snapshots()->latest('collected_at')->limit(20)->get();

        $decodedData     = null;
        $decodedSnapshot = null;

        if ($nas->decoderModel) {
            $requestedId = $request->query('snapshot');

            if ($requestedId) {
                $decodedSnapshot = $nas->snapshots()->find((int) $requestedId);
            }

            if (!$decodedSnapshot) {
                // Prefer the most recent snapshot that contains response data (collection payload)
                $decodedSnapshot = $nas->snapshots()
                    ->where('raw_json', 'like', '%"responses":{%')
                    ->latest('collected_at')
                    ->first();

                // Fallback to the latest snapshot of any type
                if (!$decodedSnapshot) {
                    $decodedSnapshot = $nas->latestSnapshot;
                }
            }

            if ($decodedSnapshot) {
                $decodedData = $decodedSnapshot->decoded_cache === null
                    ? $this->decoderService->decode($decodedSnapshot, $nas->decoderModel)
                    : $decodedSnapshot->getDecodedCache();
            }
        }

        $allApiModels     = ApiModel::orderBy('name')->get();
        $allDecoderModels = JsonDecoderModel::orderBy('name')->get();

        $customFieldDefs    = NasCustomFieldDefinition::orderBy('position')->orderBy('id')->get();
        $customFieldValues  = NasCustomFieldValue::where('nas_id', $nas->id)->get()->keyBy('definition_id');

        return view('nas.show', compact(
            'nas', 'snapshots', 'decodedData', 'decodedSnapshot',
            'allApiModels', 'allDecoderModels',
            'customFieldDefs', 'customFieldValues'
        ));
    }

    public function updateCustomFields(Request $request, NasDevice $nas)
    {
        $definitions = NasCustomFieldDefinition::all();

        foreach ($definitions as $def) {
            $key   = 'field_' . $def->id;
            $value = match ($def->type) {
                'boolean' => $request->has($key) ? '1' : '0',
                default   => $request->input($key),
            };

            NasCustomFieldValue::updateOrCreate(
                ['nas_id' => $nas->id, 'definition_id' => $def->id],
                ['value'  => $value]
            );
        }

        return redirect()->route('nas.show', $nas)
            ->with('success', 'Informations client enregistrées.');
    }

    public function redecode(NasDevice $nas)
    {
        $nas->load('decoderModel');

        if (!$nas->decoderModel) {
            return back()->with('error', 'Aucun décodeur rattaché à ce NAS.');
        }

        $snapshot = $nas->snapshots()
            ->where('raw_json', 'like', '%"responses":{%')
            ->latest('collected_at')
            ->first() ?? $nas->latestSnapshot;

        if (!$snapshot) {
            return back()->with('error', 'Aucun snapshot disponible.');
        }

        $snapshot->decoded_cache = null;
        $this->decoderService->decode($snapshot, $nas->decoderModel);

        return back()->with('success', 'Données recalculées depuis le snapshot #' . $snapshot->id . '.');
    }

    public function update(Request $request, NasDevice $nas)
    {
        $request->validate([
            'api_model_id'         => 'nullable|exists:api_models,id',
            'decoder_model_id'     => 'nullable|exists:json_decoder_models,id',
            'collection_frequency' => 'nullable|integer|min:1|max:10080',
        ]);

        $data = [];
        foreach (['api_model_id', 'decoder_model_id', 'collection_frequency'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        $nas->update($data);

        return redirect()->route('nas.show', $nas)
            ->with('success', 'Configuration mise à jour.');
    }

    public function regenerateHmac(NasDevice $nas)
    {
        $nas->update(['hmac_secret' => bin2hex(random_bytes(32))]);

        return redirect()->route('nas.show', $nas)
            ->with('hmac_generated', true)
            ->with('success', 'Clé HMAC régénérée. Mettez à jour la variable SYNOMANAGER_SECRET sur l\'agent.');
    }

    public function destroy(NasDevice $nas)
    {
        $nas->delete();

        return redirect()->route('nas.index')
            ->with('success', "Le NAS « {$nas->name} » a été supprimé.");
    }

    private function resolveGlobalAttributeValues(Collection $nasList, Collection $columns): array
    {
        $decoderIds = $nasList->pluck('decoder_model_id')->filter()->unique();
        if ($decoderIds->isEmpty()) return [];

        $mappings = GlobalAttributeMapping::whereIn('decoder_model_id', $decoderIds)
            ->get()
            ->groupBy('decoder_model_id');

        $result = [];
        foreach ($nasList as $nas) {
            if (!$nas->decoder_model_id || !$nas->latestSnapshot?->decoded_cache) continue;

            $decodedData     = $nas->latestSnapshot->getDecodedCache();
            $decoderMappings = $mappings[$nas->decoder_model_id] ?? collect();

            foreach ($decoderMappings as $mapping) {
                $value = $this->findByInternalKey($decodedData, $mapping->element_internal_key);
                $result[$nas->id][$mapping->global_attribute_id] = $value;
            }
        }

        return $result;
    }

    private function findByInternalKey(array $decodedData, string $key): mixed
    {
        foreach ($decodedData as $block) {
            foreach ($block['elements'] ?? [] as $el) {
                if ($el['type'] === 'simple' && ($el['internal_key'] ?? '') === $key) {
                    $val = $el['value'];
                    return is_array($val) ? ($val['label'] ?? $val['raw'] ?? null) : $val;
                }
            }
        }
        return null;
    }
}
