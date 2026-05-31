<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NasDevice extends Model
{
    protected $fillable = [
        'name', 'model', 'serial', 'url', 'dsm_version', 'status',
        'api_model_id', 'decoder_model_id', 'collection_frequency',
        'last_contact_at', 'approved_at', 'approved_by_user_id', 'hmac_secret',
    ];

    protected $casts = [
        'last_contact_at' => 'datetime',
        'approved_at'     => 'datetime',
    ];

    public function apiModel(): BelongsTo
    {
        return $this->belongsTo(ApiModel::class);
    }

    public function decoderModel(): BelongsTo
    {
        return $this->belongsTo(JsonDecoderModel::class, 'decoder_model_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function availableApis(): HasMany
    {
        return $this->hasMany(NasApiAvailable::class, 'nas_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(NasSnapshot::class, 'nas_id')->orderByDesc('collected_at');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(NasSnapshot::class, 'nas_id')->latestOfMany('collected_at');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(NasCustomFieldValue::class, 'nas_id');
    }

    public function isOnline(): bool
    {
        return $this->last_contact_at !== null
            && $this->last_contact_at->gt(now()->subMinutes($this->collection_frequency * 2));
    }
}
