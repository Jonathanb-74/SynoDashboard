<?php

namespace App\Http\Controllers;

use App\Models\NasDevice;
use App\Services\JsonDecoderService;

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

        return view('nas.show', compact('nas', 'snapshots', 'decodedData', 'decodedSnapshot'));
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

    public function destroy(NasDevice $nas)
    {
        $nas->delete();

        return redirect()->route('nas.index')
            ->with('success', "Le NAS « {$nas->name} » a été supprimé.");
    }
}
