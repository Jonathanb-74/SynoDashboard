<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasSnapshot extends Model
{
    protected $fillable = [
        'nas_id', 'agent_version', 'collected_at', 'raw_json', 'decoded_cache',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    public function nas(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class, 'nas_id');
    }

    public function getRawData(): array
    {
        return json_decode($this->raw_json, true) ?? [];
    }

    public function getDecodedCache(): ?array
    {
        if ($this->decoded_cache === null) {
            return null;
        }
        return json_decode($this->decoded_cache, true);
    }
}
