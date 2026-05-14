<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nas_id', 'nas_serial', 'ip_address', 'http_method', 'path',
        'status_code', 'payload', 'response', 'duration_ms', 'error',
        'hmac_signature', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function nas(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class, 'nas_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match(true) {
            $this->status_code >= 500 => 'danger',
            $this->status_code >= 400 => 'warning',
            $this->status_code >= 300 => 'info',
            default                   => 'success',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status_code) {
            200 => 'OK',
            401 => 'Signature invalide',
            422 => 'Validation',
            500 => 'Erreur serveur',
            default => (string) $this->status_code,
        };
    }
}
