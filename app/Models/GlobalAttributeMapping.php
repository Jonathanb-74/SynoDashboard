<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalAttributeMapping extends Model
{
    protected $fillable = ['global_attribute_id', 'decoder_model_id', 'element_internal_key'];

    public function globalAttribute(): BelongsTo
    {
        return $this->belongsTo(GlobalAttribute::class, 'global_attribute_id');
    }

    public function decoderModel(): BelongsTo
    {
        return $this->belongsTo(JsonDecoderModel::class, 'decoder_model_id');
    }
}
