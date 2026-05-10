<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplayElement extends Model
{
    protected $fillable = ['block_id', 'type', 'label', 'api_name', 'json_path', 'internal_key', 'transformer', 'transformer_config', 'sort_order'];

    protected $casts = [
        'json_path'          => 'array',
        'transformer_config' => 'array',
    ];

    protected $touches = ['block'];

    public function block(): BelongsTo
    {
        return $this->belongsTo(DisplayBlock::class, 'block_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(DisplayColumn::class, 'element_id')->orderBy('sort_order');
    }
}
