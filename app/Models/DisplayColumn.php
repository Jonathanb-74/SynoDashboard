<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplayColumn extends Model
{
    protected $fillable = ['element_id', 'type', 'label', 'json_path', 'internal_key', 'transformer', 'transformer_config', 'sort_order'];

    protected $casts = [
        'json_path'          => 'array',
        'transformer_config' => 'array',
    ];

    protected $touches = ['element'];

    public function element(): BelongsTo
    {
        return $this->belongsTo(DisplayElement::class, 'element_id');
    }

    public function subColumns(): HasMany
    {
        return $this->hasMany(DisplaySubColumn::class, 'column_id')->orderBy('sort_order');
    }
}
