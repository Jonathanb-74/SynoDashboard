<?php

namespace App\Services;

use App\Models\DisplayBlock;
use App\Models\DisplayColumn;
use App\Models\DisplayElement;
use App\Models\JsonDecoderModel;
use App\Models\NasSnapshot;

class JsonDecoderService
{
    /**
     * Decode a snapshot using the new block/element/column architecture.
     * Returns an array of blocks, each with their rendered elements.
     */
    public function decode(NasSnapshot $snapshot, JsonDecoderModel $model): array
    {
        $rawData = $snapshot->getRawData();

        $model->loadMissing(['blocks.elements.columns.subColumns']);

        $result = [];

        foreach ($model->blocks as $block) {
            $result[] = $this->decodeBlock($rawData, $block);
        }

        $snapshot->decoded_cache = json_encode($result, JSON_UNESCAPED_UNICODE);
        $snapshot->save();

        return $result;
    }

    public function decodeBlock(array $rawData, DisplayBlock $block): array
    {
        $elements = [];

        foreach ($block->elements as $element) {
            $elements[] = $this->decodeElement($rawData, $element);
        }

        return [
            'id'          => $block->id,
            'title'       => $block->title,
            'description' => $block->description,
            'icon'        => $block->icon,
            'elements'    => $elements,
        ];
    }

    public function decodeElement(array $rawData, DisplayElement $element): array
    {
        // Navigate to the api_name sub-object first if set
        $scope = $rawData;
        if ($element->api_name && isset($rawData['responses'][$element->api_name])) {
            $scope = $rawData['responses'][$element->api_name];
        }

        if ($element->type === 'simple') {
            $raw   = $this->getValueAtPath($scope, $element->json_path ?? []);
            $value = $this->applyTransformer($raw, $element->transformer, $element->transformer_config);

            return [
                'type'         => 'simple',
                'internal_key' => $element->internal_key,
                'label'        => $element->label,
                'value'        => $value,
                'raw'          => $raw,
            ];
        }

        // type === 'loop'
        $items = $this->getValueAtPath($scope, $element->json_path ?? []);

        $rows = [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $row = [];
                foreach ($element->columns as $column) {
                    $row[] = $this->decodeColumn($item, $column);
                }
                $rows[] = $row;
            }
        }

        return [
            'type'         => 'loop',
            'internal_key' => $element->internal_key,
            'label'        => $element->label,
            'columns'      => $element->columns->map(fn($c) => ['label' => $c->label, 'type' => $c->type])->values()->toArray(),
            'rows'         => $rows,
        ];
    }

    public function decodeColumn(array $item, DisplayColumn $column): array
    {
        if ($column->type === 'value') {
            $raw   = $this->getValueAtPath($item, $column->json_path ?? []);
            $value = $this->applyTransformer($raw, $column->transformer, $column->transformer_config);

            return [
                'type'  => 'value',
                'value' => $value,
                'raw'   => $raw,
            ];
        }

        // type === 'loop' (nested sub-table inside a cell)
        $subItems = $this->getValueAtPath($item, $column->json_path ?? []);
        $subRows  = [];

        if (is_array($subItems)) {
            foreach ($subItems as $subItem) {
                if (!is_array($subItem)) {
                    continue;
                }
                $subRow = [];
                foreach ($column->subColumns as $subCol) {
                    $raw      = $this->getValueAtPath($subItem, $subCol->json_path ?? []);
                    $subRow[] = [
                        'label' => $subCol->label,
                        'value' => $this->applyTransformer($raw, $subCol->transformer, $subCol->transformer_config),
                        'raw'   => $raw,
                    ];
                }
                $subRows[] = $subRow;
            }
        }

        return [
            'type'  => 'value',
            'value' => [
                'type'     => 'loop',
                'sub_cols' => $column->subColumns->map(fn($c) => $c->label)->values()->toArray(),
                'rows'     => $subRows,
            ],
            'raw'   => $subItems,
        ];
    }

    public function getValueAtPath(array $data, array $path): mixed
    {
        $current = $data;

        foreach ($path as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    public function applyTransformer(mixed $value, ?string $transformer, ?array $config): mixed
    {
        if ($value === null || !$transformer) {
            return $value;
        }

        return match ($transformer) {
            'date'      => $this->transformDate($value, $config),
            'timestamp' => $this->transformTimestamp($value, $config),
            'bytes'     => $this->transformBytes($value),
            'megabytes' => $this->transformMegabytes($value),
            'duration'  => $this->transformDuration($value),
            'uptime'    => $this->transformUptime($value),
            'boolean'   => $this->transformBoolean($value, $config),
            'badge_map' => $this->transformBadgeMap($value, $config),
            'color_if'  => $this->transformColorIf($value, $config),
            default     => $value,
        };
    }

    public function invalidateCache(NasSnapshot $snapshot): void
    {
        $snapshot->decoded_cache = null;
        $snapshot->save();
    }

    // ---------------------------------------------------------------------------
    // Transformers
    // ---------------------------------------------------------------------------

    private function transformDate(mixed $value, ?array $config): string
    {
        $format = $config['format'] ?? 'd/m/Y H:i';
        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function transformTimestamp(mixed $value, ?array $config): string
    {
        $format = $config['format'] ?? 'd/m/Y H:i';
        try {
            return \Carbon\Carbon::createFromTimestamp((int) $value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function transformBytes(mixed $value): string
    {
        $bytes = (float) $value;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function transformMegabytes(mixed $value): string
    {
        $units = ['MB', 'GB', 'TB', 'PB'];
        $size  = (float) $value;
        $i     = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    private function transformDuration(mixed $value): string
    {
        $seconds = (int) $value;
        if ($seconds < 60) {
            return $seconds . 's';
        }
        if ($seconds < 3600) {
            return intdiv($seconds, 60) . 'min ' . ($seconds % 60) . 's';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return $h . 'h ' . $m . 'min';
    }

    private function transformUptime(mixed $value): string
    {
        if (!preg_match('/^(\d+):(\d{1,2}):(\d{1,2})$/', (string) $value, $m)) {
            return (string) $value;
        }
        $total   = (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3];
        $days    = intdiv($total, 86400);
        $hours   = intdiv($total % 86400, 3600);
        $minutes = intdiv($total % 3600, 60);

        if ($days > 0) {
            return "{$days}j {$hours}h {$minutes}min";
        }
        return $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min";
    }

    private function transformBoolean(mixed $value, ?array $config): string
    {
        $trueLabel  = $config['true']  ?? 'Oui';
        $falseLabel = $config['false'] ?? 'Non';
        return $value ? $trueLabel : $falseLabel;
    }

    private function evaluateRules(mixed $value, ?array $config): array
    {
        $rules     = $config['rules'] ?? [];
        $color     = $config['default_color'] ?? 'secondary';
        $formatted = is_bool($value) ? ($value ? 'Oui' : 'Non') : (string) $value;

        foreach ($rules as $rule) {
            $op  = $rule['op']    ?? '==';
            $cmp = $rule['value'] ?? null;
            $hit = match ($op) {
                '=='  => $value == $cmp,
                '!='  => $value != $cmp,
                '>'   => is_numeric($value) && $value > $cmp,
                '>='  => is_numeric($value) && $value >= $cmp,
                '<'   => is_numeric($value) && $value < $cmp,
                '<='  => is_numeric($value) && $value <= $cmp,
                default => false,
            };
            if ($hit) {
                $color     = $rule['color'] ?? $color;
                $formatted = $rule['label'] ?? $formatted;
                break;
            }
        }

        return ['color' => $color, 'label' => $formatted];
    }

    private function transformBadgeMap(mixed $value, ?array $config): array
    {
        ['color' => $color, 'label' => $label] = $this->evaluateRules($value, $config);
        return ['type' => 'badge', 'label' => $label, 'color' => $color, 'raw' => $value];
    }

    private function transformColorIf(mixed $value, ?array $config): array
    {
        ['color' => $color, 'label' => $label] = $this->evaluateRules($value, $config);
        return ['type' => 'colored', 'label' => $label, 'color' => $color, 'raw' => $value];
    }
}
