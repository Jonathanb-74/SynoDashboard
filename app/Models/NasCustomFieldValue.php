<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasCustomFieldValue extends Model
{
    protected $fillable = ['nas_id', 'definition_id', 'value'];

    public function nas(): BelongsTo
    {
        return $this->belongsTo(NasDevice::class, 'nas_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(NasCustomFieldDefinition::class, 'definition_id');
    }
}
