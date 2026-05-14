<?php

namespace App\Http\Controllers;

use App\Models\NasDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NasApprovalController extends Controller
{
    public function index()
    {
        $nasList = NasDevice::with('latestSnapshot')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $allApiModels     = \App\Models\ApiModel::orderBy('name')->get();
        $allDecoderModels = \App\Models\JsonDecoderModel::orderBy('name')->get();

        return view('nas.pending', compact('nasList', 'allApiModels', 'allDecoderModels'));
    }

    public function approve(Request $request, NasDevice $nas)
    {
        $request->validate([
            'collection_frequency' => 'nullable|integer|min:1|max:10080',
            'api_model_id'         => 'nullable|exists:api_models,id',
            'decoder_model_id'     => 'nullable|exists:json_decoder_models,id',
        ]);

        $hmacSecret = bin2hex(random_bytes(32));

        $nas->update([
            'status'               => 'approved',
            'approved_at'          => now(),
            'approved_by_user_id'  => Auth::id(),
            'collection_frequency' => $request->input('collection_frequency', 60),
            'api_model_id'         => $request->input('api_model_id'),
            'decoder_model_id'     => $request->input('decoder_model_id'),
            'hmac_secret'          => $hmacSecret,
        ]);

        return redirect()->route('nas.show', $nas)
            ->with('hmac_generated', true)
            ->with('success', "Le NAS « {$nas->name} » a été approuvé. La clé HMAC a été générée.");
    }

    public function reject(NasDevice $nas)
    {
        $nas->update(['status' => 'rejected']);

        return redirect()->route('nas.pending')
            ->with('success', "Le NAS « {$nas->name} » a été rejeté.");
    }
}
