<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JsonDecoderModel extends Model
{
    protected $fillable = ['name', 'description'];

    public function blocks(): HasMany
    {
        return $this->hasMany(DisplayBlock::class, 'decoder_model_id')->orderBy('sort_order');
    }

    public function nasDevices(): HasMany
    {
        return $this->hasMany(NasDevice::class, 'decoder_model_id');
    }
}
