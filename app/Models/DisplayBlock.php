<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplayBlock extends Model
{
    protected $fillable = ['decoder_model_id', 'title', 'description', 'icon', 'sort_order'];

    protected $touches = ['decoderModel'];

    public function decoderModel(): BelongsTo
    {
        return $this->belongsTo(JsonDecoderModel::class, 'decoder_model_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(DisplayElement::class, 'block_id')->orderBy('sort_order');
    }
}
