<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalAttribute extends Model
{
    protected $fillable = ['name', 'unit', 'description', 'position'];

    public function mappings(): HasMany
    {
        return $this->hasMany(GlobalAttributeMapping::class, 'global_attribute_id');
    }
}
