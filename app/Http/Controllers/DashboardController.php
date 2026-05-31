<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use App\Models\NasCustomFieldDefinition;
use App\Models\NasDevice;
use App\Models\NasViewTable;

class DashboardController extends Controller
{
    public function index()
    {
        $configuredView = NasViewTable::getDefault('dashboard');

        $needsCustomFields = $configuredView
            && $configuredView->columns->where('source', 'custom_field')->isNotEmpty();

        $query = NasDevice::with(['apiModel', 'decoderModel', 'latestSnapshot'])
            ->withCount('snapshots')
            ->orderByDesc('last_contact_at');

        if ($needsCustomFields) {
            $query->with('customFieldValues');
        }

        $nasList = $query->get();

        // Built-in widget counts (computed from loaded collection)
        $builtinCounts = [
            'total'    => $nasList->count(),
            'approved' => $nasList->where('status', 'approved')->count(),
            'pending'  => $nasList->where('status', 'pending')->count(),
            'rejected' => $nasList->where('status', 'rejected')->count(),
        ];

        $widgets = DashboardWidget::where('active', true)->orderBy('position')->get();

        // Compute count for custom count widgets
        $widgetCounts = [];
        foreach ($widgets->where('type', 'count') as $widget) {
            $widgetCounts[$widget->id] = $this->computeCount($widget, $nasList);
        }

        $customFieldDefs = NasCustomFieldDefinition::orderBy('position')->get();

        return view('dashboard.index', compact(
            'nasList', 'configuredView', 'widgets',
            'builtinCounts', 'widgetCounts', 'customFieldDefs'
        ));
    }

    private function computeCount($widget, $nasList): int
    {
        if ($widget->source === 'device') {
            return match ($widget->field_key) {
                'online_status' => $widget->field_value === 'OK'
                    ? $nasList->filter(fn($n) => $n->isOnline())->count()
                    : $nasList->filter(fn($n) => !$n->isOnline())->count(),
                default => $nasList->where($widget->field_key, $widget->field_value)->count(),
            };
        }

        if ($widget->source === 'custom_field') {
            $defId = (int) $widget->field_key;

            return $nasList->filter(function ($n) use ($defId, $widget) {
                $val = $n->customFieldValues->firstWhere('definition_id', $defId)?->value;
                return $val === $widget->field_value;
            })->count();
        }

        return 0;
    }
}
