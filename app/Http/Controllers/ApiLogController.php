<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use App\Models\NasDevice;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiLog::with('nas')->orderBy('created_at', 'desc');

        if ($request->filled('nas')) {
            $search = $request->nas;
            $query->where(function ($q) use ($search) {
                $q->where('nas_serial', 'like', "%{$search}%")
                  ->orWhereHas('nas', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status_code', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs       = $query->paginate(50)->withQueryString();
        $nasDevices = NasDevice::orderBy('name')->pluck('name', 'serial');

        return view('api-logs.index', compact('logs', 'nasDevices'));
    }

    public function show(ApiLog $apiLog)
    {
        $apiLog->load('nas');
        return view('api-logs.show', compact('apiLog'));
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'older_than_days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($request->filled('older_than_days')) {
            $count = ApiLog::where('created_at', '<', now()->subDays($request->older_than_days))->delete();
            return back()->with('success', "{$count} log(s) antérieur(s) à {$request->older_than_days} jours supprimé(s).");
        }

        ApiLog::truncate();
        return back()->with('success', 'Tous les logs ont été supprimés.');
    }
}
