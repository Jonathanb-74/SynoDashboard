<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use App\Models\GlobalAttributeMapping;
use App\Models\NasCustomFieldDefinition;
use App\Models\NasDevice;
use App\Models\NasViewTable;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $configuredView = NasViewTable::getDefault('dashboard');

        $needsCustomFields     = $configuredView && $configuredView->columns->where('source', 'custom_field')->isNotEmpty();
        $needsGlobalAttributes = $configuredView && $configuredView->columns->where('source', 'global_attribute')->isNotEmpty();

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

        $customFieldDefs       = NasCustomFieldDefinition::orderBy('position')->get();
        $globalAttributeValues = $needsGlobalAttributes
            ? $this->resolveGlobalAttributeValues($nasList, $configuredView->columns)
            : [];

        return view('dashboard.index', compact(
            'nasList', 'configuredView', 'widgets',
            'builtinCounts', 'widgetCounts', 'customFieldDefs', 'globalAttributeValues'
        ));
    }

    private function resolveGlobalAttributeValues(Collection $nasList, Collection $columns): array
    {
        $decoderIds = $nasList->pluck('decoder_model_id')->filter()->unique();
        if ($decoderIds->isEmpty()) return [];

        $mappings = GlobalAttributeMapping::whereIn('decoder_model_id', $decoderIds)
            ->get()->groupBy('decoder_model_id');

        $result = [];
        foreach ($nasList as $nas) {
            if (!$nas->decoder_model_id || !$nas->latestSnapshot?->decoded_cache) continue;
            $decodedData     = $nas->latestSnapshot->getDecodedCache();
            $decoderMappings = $mappings[$nas->decoder_model_id] ?? collect();
            foreach ($decoderMappings as $mapping) {
                $val = $this->findByInternalKey($decodedData, $mapping->element_internal_key);
                $result[$nas->id][$mapping->global_attribute_id] = $val;
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
