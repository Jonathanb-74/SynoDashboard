<?php

namespace App\Http\Controllers;

use App\Models\NasDevice;

class DashboardController extends Controller
{
    public function index()
    {
        $nasList = NasDevice::with(['apiModel', 'decoderModel', 'latestSnapshot'])
            ->withCount('snapshots')
            ->orderByDesc('last_contact_at')
            ->get();

        $stats = [
            'total'    => $nasList->count(),
            'approved' => $nasList->where('status', 'approved')->count(),
            'pending'  => $nasList->where('status', 'pending')->count(),
            'rejected' => $nasList->where('status', 'rejected')->count(),
        ];

        return view('dashboard.index', compact('nasList', 'stats'));
    }
}
