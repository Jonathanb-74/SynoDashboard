<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiModel extends Model
{
    protected $fillable = ['name', 'description', 'decoder_model_id'];

    public function decoderModel(): BelongsTo
    {
        return $this->belongsTo(JsonDecoderModel::class, 'decoder_model_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ApiModelEntry::class);
    }

    public function nasDevices(): HasMany
    {
        return $this->hasMany(NasDevice::class);
    }
}
