<?php

namespace App\Http\Controllers;

use App\Models\ApiModel;
use App\Models\JsonDecoderModel;
use App\Models\NasDevice;
use App\Services\JsonDecoderService;
use Illuminate\Http\Request;

class NasController extends Controller
{
    public function __construct(private JsonDecoderService $decoderService) {}

    public function index()
    {
        $nasList = NasDevice::with(['apiModel', 'decoderModel', 'latestSnapshot'])
            ->withCount('snapshots')
            ->orderByDesc('last_contact_at')
            ->get();

        return view('nas.index', compact('nasList'));
    }

    public function show(NasDevice $nas)
    {
        $nas->load(['apiModel', 'decoderModel.blocks.elements.columns.subColumns', 'approvedBy', 'availableApis']);
        $nas->loadCount('snapshots');

        $snapshots = $nas->snapshots()->latest('collected_at')->limit(20)->get();

        $decodedData     = null;
        $decodedSnapshot = null;

        if ($nas->decoderModel) {
            // Prefer the most recent snapshot that contains response data (collection payload)
            $decodedSnapshot = $nas->snapshots()
                ->where('raw_json', 'like', '%"responses":{%')
                ->latest('collected_at')
                ->first();

            // Fallback to the latest snapshot of any type
            if (!$decodedSnapshot) {
                $decodedSnapshot = $nas->latestSnapshot;
            }

            if ($decodedSnapshot) {
                $decodedData = $decodedSnapshot->decoded_cache === null
                    ? $this->decoderService->decode($decodedSnapshot, $nas->decoderModel)
                    : $decodedSnapshot->getDecodedCache();
            }
        }

        $allApiModels     = ApiModel::orderBy('name')->get();
        $allDecoderModels = JsonDecoderModel::orderBy('name')->get();

        return view('nas.show', compact('nas', 'snapshots', 'decodedData', 'decodedSnapshot', 'allApiModels', 'allDecoderModels'));
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
}
