<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasApiAvailable extends Model
{
    protected $table = 'nas_api_available';

    public $timestamps = false;

    protected $fillable = [
        'nas_id', 'api_name', 'path', 'min_version', 'max_version',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function nas(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class, 'nas_id');
    }
}
