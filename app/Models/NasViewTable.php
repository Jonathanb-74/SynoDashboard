<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NasViewTable extends Model
{
    protected $fillable = ['name', 'is_nas_page_default', 'is_dashboard_default'];

    protected $casts = [
        'is_nas_page_default'  => 'boolean',
        'is_dashboard_default' => 'boolean',
    ];

    public function columns(): HasMany
    {
        return $this->hasMany(NasViewColumn::class, 'view_id')->orderBy('position');
    }

    public static function getDefault(string $page): ?self
    {
        $field = $page === 'dashboard' ? 'is_dashboard_default' : 'is_nas_page_default';

        return self::where($field, true)->with('columns')->first();
    }
}
