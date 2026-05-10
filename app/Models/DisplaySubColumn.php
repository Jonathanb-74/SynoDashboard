<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplaySubColumn extends Model
{
    protected $fillable = ['column_id', 'label', 'json_path', 'internal_key', 'transformer', 'transformer_config', 'sort_order'];

    protected $casts = [
        'json_path'          => 'array',
        'transformer_config' => 'array',
    ];

    protected $touches = ['column'];

    public function column(): BelongsTo
    {
        return $this->belongsTo(DisplayColumn::class, 'column_id');
    }
}
