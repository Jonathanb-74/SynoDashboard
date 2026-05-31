<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NasCustomFieldDefinition extends Model
{
    protected $fillable = ['label', 'type', 'options', 'position'];

    protected $casts = [
        'options' => 'array',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(NasCustomFieldValue::class, 'definition_id');
    }
}
