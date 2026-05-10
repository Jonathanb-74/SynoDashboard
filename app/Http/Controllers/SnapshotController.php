<?php

namespace App\Http\Controllers;

use App\Models\ApiModelEntry;
use App\Models\NasSnapshot;
use Illuminate\Http\Response;

class SnapshotController extends Controller
{
    public function show(NasSnapshot $snapshot)
    {
        $snapshot->load('nas');

        $rawData    = $snapshot->getRawData();
        $prettyJson = json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $entryMap     = [];
        $entriesByApi = [];
        if ($snapshot->nas->api_model_id) {
            $entries = ApiModelEntry::where('api_model_id', $snapshot->nas->api_model_id)->get();
            foreach ($entries as $entry) {
                $entryMap[$entry->api_name]     = $entry->id;
                $entriesByApi[$entry->api_name] = $entry;
            }
        }

        return view('snapshots.show', compact('snapshot', 'prettyJson', 'entryMap', 'entriesByApi'));
    }

    public function raw(NasSnapshot $snapshot): Response
    {
        return response($snapshot->raw_json, 200, [
            'Content-Type'        => 'application/json; charset=utf-8',
            'Content-Disposition' => 'inline; filename="snapshot-' . $snapshot->id . '.json"',
        ]);
    }
}
