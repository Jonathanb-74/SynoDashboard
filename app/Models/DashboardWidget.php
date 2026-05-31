<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'type', 'builtin_key', 'label', 'active', 'color',
        'position', 'source', 'field_key', 'field_value',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function isBuiltin(): bool
    {
        return $this->type === 'builtin';
    }
}
