<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasViewColumn extends Model
{
    public const DEVICE_COLUMNS = [
        'name'                 => 'Nom',
        'model'                => 'Modèle',
        'dsm_version'          => 'Version DSM',
        'serial'               => 'N° Série',
        'online_status'        => 'Statut ligne (OK/Erreur)',
        'nas_status'           => 'Statut approbation',
        'last_contact_at'      => 'Dernier contact',
        'collection_frequency' => 'Fréquence collecte',
        'created_at'           => 'Ajouté le',
    ];

    // Device fields that can be used as count widget conditions
    public const COUNTABLE_DEVICE_FIELDS = [
        'online_status' => ['OK', 'Erreur'],
        'status'        => ['approved', 'pending', 'rejected'],
        'model'         => null,
        'dsm_version'   => null,
    ];

    protected $fillable = ['view_id', 'source', 'field_key', 'label', 'position'];

    public function view(): BelongsTo
    {
        return $this->belongsTo(NasViewTable::class, 'view_id');
    }

    public function getDisplayLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->source === 'device') {
            return self::DEVICE_COLUMNS[$this->field_key] ?? $this->field_key;
        }
        return NasCustomFieldDefinition::find($this->field_key)?->label ?? $this->field_key;
    }
}
