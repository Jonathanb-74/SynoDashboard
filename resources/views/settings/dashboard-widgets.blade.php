<x-app-layout>
    <x-slot name="title">Widgets Dashboard</x-slot>

    @php
        $customFieldDefs = \App\Models\NasCustomFieldDefinition::orderBy('position')->get();
        $countableDevice = \App\Models\NasViewColumn::COUNTABLE_DEVICE_FIELDS;
        $colors = ['primary'=>'Bleu','success'=>'Vert','warning'=>'Orange','danger'=>'Rouge','info'=>'Cyan','secondary'=>'Gris'];
    @endphp

    <div x-data="{
        createOpen: false,
        createSource: 'device',
        createDeviceField: '',
        createCustomField: '',
        createValue: '',
        createColor: 'primary',
        createLabel: '',
        editId: null, editLabel: '', editColor: 'primary', editActive: true,
        editSource: 'device', editDeviceField: '', editCustomField: '', editValue: '',
        editIsBuiltin: false,
        openEdit(w) {
            this.editId       = w.id;
            this.editLabel    = w.label;
            this.editColor    = w.color;
            this.editActive   = w.active;
            this.editIsBuiltin = w.type === 'builtin';
            this.editSource   = w.source || 'device';
            this.editDeviceField = w.source === 'device' ? w.field_key : '';
            this.editCustomField = w.source === 'custom_field' ? w.field_key : '';
            this.editValue    = w.field_value || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editWidgetModal')).show();
        }
    }">

    <div class="row g-3">

        {{-- ─── Widgets intégrés ───────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <span class="fw-semibold small"><i class="bi bi-grid-1x2 me-2 text-primary"></i>Widgets intégrés</span>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($widgets->where('type', 'builtin') as $w)
                    <div class="list-group-item d-flex align-items-center gap-3 py-2">
                        <span class="badge bg-{{ $w->color }} px-2 py-1" style="min-width:3rem">{{ $w->position + 1 }}</span>
                        <div class="flex-grow-1">
                            <span class="fw-medium small">{{ $w->label }}</span>
                        </div>
                        <span class="badge {{ $w->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $w->active ? 'Actif' : 'Masqué' }}
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                                @click="openEdit({{ $w->toJson() }})">
                            <i class="bi bi-pencil small"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ─── Widgets personnalisés ──────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold small"><i class="bi bi-bar-chart me-2 text-success"></i>Widgets compteurs</span>
                    <button class="btn btn-sm btn-primary" @click="createOpen = !createOpen">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </button>
                </div>

                {{-- Formulaire création --}}
                <div x-show="createOpen" x-cloak class="card-body border-bottom bg-light">
                    <form method="POST" action="{{ route('settings.dashboard-widgets.store') }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-medium mb-1">Label *</label>
                            <input type="text" name="label" class="form-control form-control-sm"
                                   x-model="createLabel" required placeholder="ex : NAS sous contrat">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-medium mb-1">Source *</label>
                            <div class="btn-group btn-group-sm w-100">
                                <button type="button" class="btn"
                                        :class="createSource==='device' ? 'btn-secondary' : 'btn-outline-secondary'"
                                        @click="createSource='device'; createDeviceField=''; createValue=''">
                                    Données NAS
                                </button>
                                <button type="button" class="btn"
                                        :class="createSource==='custom_field' ? 'btn-info' : 'btn-outline-secondary'"
                                        @click="createSource='custom_field'; createCustomField=''; createValue=''">
                                    Infos client
                                </button>
                            </div>
                            <input type="hidden" name="source" x-model="createSource">
                        </div>
                        <template x-if="createSource === 'device'">
                            <div class="mb-2">
                                <label class="form-label small fw-medium mb-1">Champ NAS *</label>
                                <select name="field_key" class="form-select form-select-sm"
                                        x-model="createDeviceField" required>
                                    <option value="">— Choisir —</option>
                                    <option value="online_status">Statut ligne</option>
                                    <option value="status">Statut approbation</option>
                                    <option value="model">Modèle</option>
                                    <option value="dsm_version">Version DSM</option>
                                </select>
                            </div>
                        </template>
                        <template x-if="createSource === 'custom_field'">
                            <div class="mb-2">
                                <label class="form-label small fw-medium mb-1">Champ client *</label>
                                <select name="field_key" class="form-select form-select-sm"
                                        x-model="createCustomField" required>
                                    <option value="">— Choisir —</option>
                                    @foreach($customFieldDefs as $def)
                                    <option value="{{ $def->id }}">{{ $def->label }} ({{ $def->type }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </template>
                        <div class="mb-2">
                            <label class="form-label small fw-medium mb-1">Valeur = *</label>
                            <input type="text" name="field_value" class="form-control form-control-sm"
                                   x-model="createValue" required
                                   placeholder="ex : OK, approved, FCI Groupe, 1 (pour case cochée)…">
                            <div class="form-text" style="font-size:.75rem">
                                Statut ligne : OK / Erreur — Approbation : approved / pending / rejected — Case cochée : 1
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-medium mb-1">Couleur</label>
                            <select name="color" class="form-select form-select-sm" x-model="createColor">
                                @foreach($colors as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="createOpen=false">Annuler</button>
                            <button type="submit" class="btn btn-sm btn-primary">Créer</button>
                        </div>
                    </form>
                </div>

                @if($widgets->where('type', 'count')->isEmpty())
                    <div class="card-body text-muted small fst-italic py-3">Aucun widget compteur.</div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($widgets->where('type', 'count') as $w)
                    <div class="list-group-item d-flex align-items-center gap-3 py-2">
                        <span class="badge bg-{{ $w->color }} px-2 py-1" style="min-width:2rem"></span>
                        <div class="flex-grow-1 small">
                            <div class="fw-medium">{{ $w->label }}</div>
                            <div class="text-muted" style="font-size:.75rem">
                                {{ $w->source === 'device' ? 'NAS' : 'Client' }}
                                · {{ $w->field_key }} = {{ $w->field_value }}
                            </div>
                        </div>
                        <span class="badge {{ $w->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $w->active ? 'Actif' : 'Masqué' }}
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                                @click="openEdit({{ $w->toJson() }})">
                            <i class="bi bi-pencil small"></i>
                        </button>
                        <form method="POST"
                              action="{{ route('settings.dashboard-widgets.destroy', $w) }}"
                              class="d-inline"
                              onsubmit="return confirm('Supprimer ce widget ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                <i class="bi bi-trash small"></i>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>{{-- /row --}}

    {{-- Modal édition --}}
    <div class="modal fade" id="editWidgetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Modifier le widget</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ url('settings/dashboard-widgets') }}/' + editId">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Label *</label>
                            <input type="text" name="label" class="form-control form-control-sm"
                                   x-model="editLabel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Couleur</label>
                            <select name="color" class="form-select form-select-sm" x-model="editColor">
                                @foreach($colors as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="active" value="1" class="form-check-input"
                                       id="editWidgetActive" x-model="editActive">
                                <label class="form-check-label small" for="editWidgetActive">Actif (visible sur le dashboard)</label>
                            </div>
                        </div>
                        <template x-if="!editIsBuiltin">
                            <div>
                                <div class="mb-2">
                                    <label class="form-label small fw-medium mb-1">Source</label>
                                    <div class="btn-group btn-group-sm w-100">
                                        <button type="button" class="btn"
                                                :class="editSource==='device' ? 'btn-secondary' : 'btn-outline-secondary'"
                                                @click="editSource='device'">Données NAS</button>
                                        <button type="button" class="btn"
                                                :class="editSource==='custom_field' ? 'btn-info' : 'btn-outline-secondary'"
                                                @click="editSource='custom_field'">Infos client</button>
                                    </div>
                                    <input type="hidden" name="source" x-model="editSource">
                                </div>
                                <template x-if="editSource === 'device'">
                                    <div class="mb-2">
                                        <label class="form-label small fw-medium mb-1">Champ NAS</label>
                                        <select name="field_key" class="form-select form-select-sm" x-model="editDeviceField">
                                            <option value="online_status">Statut ligne</option>
                                            <option value="status">Statut approbation</option>
                                            <option value="model">Modèle</option>
                                            <option value="dsm_version">Version DSM</option>
                                        </select>
                                    </div>
                                </template>
                                <template x-if="editSource === 'custom_field'">
                                    <div class="mb-2">
                                        <label class="form-label small fw-medium mb-1">Champ client</label>
                                        <select name="field_key" class="form-select form-select-sm" x-model="editCustomField">
                                            @foreach($customFieldDefs as $def)
                                            <option value="{{ $def->id }}">{{ $def->label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </template>
                                <div class="mb-0">
                                    <label class="form-label small fw-medium mb-1">Valeur =</label>
                                    <input type="text" name="field_value" class="form-control form-control-sm"
                                           x-model="editValue">
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-warning">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>{{-- /x-data --}}
</x-app-layout>
