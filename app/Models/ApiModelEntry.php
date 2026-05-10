<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiModelEntry extends Model
{
    protected $fillable = [
        'api_model_id', 'api_name', 'path', 'method', 'version',
        'parameters', 'enabled', 'min_version', 'max_version',
    ];

    protected $casts = [
        'parameters' => 'array',
        'enabled'    => 'boolean',
    ];

    public function apiModel(): BelongsTo
    {
        return $this->belongsTo(ApiModel::class);
    }
}
