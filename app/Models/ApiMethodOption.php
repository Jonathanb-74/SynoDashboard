<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiMethodOption extends Model
{
    protected $fillable = ['name', 'sort_order', 'debug_enabled'];

    protected $casts = ['debug_enabled' => 'boolean'];
}
