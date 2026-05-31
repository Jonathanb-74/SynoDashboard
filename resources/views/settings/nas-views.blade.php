<x-app-layout>
    <x-slot name="title">Vues NAS</x-slot>

    @php
        $deviceCols = \App\Models\NasViewColumn::DEVICE_COLUMNS;
        $typeLabels = ['text'=>'Texte','textarea'=>'Texte long','date'=>'Date','boolean'=>'Case','select'=>'Menu'];
    @endphp

    <div x-data="{
        createOpen: false,
        editViewId: null, editViewName: '',
        editViewNas: false, editViewDash: false,
        openEditView(v) {
            this.editViewId   = v.id;
            this.editViewName = v.name;
            this.editViewNas  = v.is_nas_page_default;
            this.editViewDash = v.is_dashboard_default;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editViewModal')).show();
        },
        addColViewId: null, addColSource: 'device', addColKey: '', addColLabel: '',
        openAddCol(viewId) {
            this.addColViewId = viewId;
            this.addColSource = 'device';
            this.addColKey    = '';
            this.addColLabel  = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addColModal')).show();
        },
        globalAttributes: {{ \Illuminate\Support\Js::from(\App\Models\GlobalAttribute::orderBy('position')->get(['id','name','unit'])) }}
    }">

    {{-- Header --}}
    <div class="d-flex gap-2 mb-3 align-items-center">
        <h6 class="mb-0 fw-semibold flex-grow-1"><i class="bi bi-table me-2 text-primary"></i>Vues NAS</h6>
        <button class="btn btn-sm btn-primary" @click="createOpen = !createOpen">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle vue
        </button>
    </div>

    {{-- Création rapide --}}
    <div x-show="createOpen" x-cloak class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.nas-views.store') }}" class="d-flex gap-2 align-items-end">
                @csrf
                <div class="flex-grow-1">
                    <label class="form-label small fw-medium mb-1">Nom de la vue *</label>
                    <input type="text" name="name" class="form-control form-control-sm"
                           placeholder="ex : Vue principale, Vue client…" required autofocus>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">Créer</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="createOpen = false">Annuler</button>
            </form>
        </div>
    </div>

    @if($views->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5 small fst-italic">
                Aucune vue configurée. Créez une vue pour personnaliser les tableaux NAS.
            </div>
        </div>
    @else
        @foreach($views as $view)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold flex-grow-1">{{ $view->name }}</span>
                @if($view->is_nas_page_default)
                    <span class="badge bg-primary"><i class="bi bi-hdd-stack me-1"></i>Page NAS</span>
                @endif
                @if($view->is_dashboard_default)
                    <span class="badge bg-success"><i class="bi bi-speedometer2 me-1"></i>Dashboard</span>
                @endif
                <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                        title="Modifier"
                        @click="openEditView({{ $view->toJson() }})">
                    <i class="bi bi-pencil small"></i>
                </button>
                <form method="POST" action="{{ route('settings.nas-views.destroy', $view) }}" class="d-inline"
                      onsubmit="return confirm('Supprimer la vue « {{ $view->name }} » ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Supprimer">
                        <i class="bi bi-trash small"></i>
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                        title="Ajouter une colonne"
                        @click="openAddCol({{ $view->id }})">
                    <i class="bi bi-plus-lg small"></i> Colonne
                </button>
            </div>

            @if($view->columns->isEmpty())
                <div class="card-body text-muted small fst-italic py-2 px-3">
                    Aucune colonne — cliquez sur "+ Colonne" pour en ajouter.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th style="width:2rem">#</th>
                            <th>Colonne</th>
                            <th>Source</th>
                            <th>Label affiché</th>
                            <th style="width:5rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($view->columns as $col)
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td class="fw-medium">
                                @if($col->source === 'device')
                                    {{ $deviceCols[$col->field_key] ?? $col->field_key }}
                                @elseif($col->source === 'custom_field')
                                    {{ $customFieldDefs->firstWhere('id', (int)$col->field_key)?->label ?? '#'.$col->field_key }}
                                @else
                                    @php $ga = \App\Models\GlobalAttribute::find($col->field_key); @endphp
                                    {{ $ga?->name ?? '#'.$col->field_key }}
                                @endif
                            </td>
                            <td>
                                @php $sourceBadge = match($col->source) {
                                        'device' => ['bg-secondary', 'NAS'],
                                        'custom_field' => ['bg-info text-dark', 'Client'],
                                        'global_attribute' => ['bg-primary', 'Global'],
                                        default => ['bg-secondary', $col->source],
                                    }; @endphp
                                <span class="badge {{ $sourceBadge[0] }}">{{ $sourceBadge[1] }}</span>
                            </td>
                            <td class="text-muted">{{ $col->label ?: '—' }}</td>
                            <td class="text-end pe-2">
                                {{-- Monter --}}
                                @if(!$loop->first)
                                <form method="POST" action="{{ route('settings.nas-views.columns.reorder', $view) }}" class="d-inline">
                                    @csrf
                                    @php
                                        $ids = $view->columns->pluck('id')->toArray();
                                        $idx = array_search($col->id, $ids);
                                        [$ids[$idx], $ids[$idx-1]] = [$ids[$idx-1], $ids[$idx]];
                                    @endphp
                                    @foreach($ids as $fid)<input type="hidden" name="ids[]" value="{{ $fid }}">@endforeach
                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"><i class="bi bi-arrow-up small"></i></button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1 invisible"><i class="bi bi-arrow-up small"></i></button>
                                @endif
                                {{-- Descendre --}}
                                @if(!$loop->last)
                                <form method="POST" action="{{ route('settings.nas-views.columns.reorder', $view) }}" class="d-inline">
                                    @csrf
                                    @php
                                        $ids2 = $view->columns->pluck('id')->toArray();
                                        $idx2 = array_search($col->id, $ids2);
                                        [$ids2[$idx2], $ids2[$idx2+1]] = [$ids2[$idx2+1], $ids2[$idx2]];
                                    @endphp
                                    @foreach($ids2 as $fid)<input type="hidden" name="ids[]" value="{{ $fid }}">@endforeach
                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"><i class="bi bi-arrow-down small"></i></button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1 invisible"><i class="bi bi-arrow-down small"></i></button>
                                @endif
                                {{-- Supprimer --}}
                                <form method="POST"
                                      action="{{ route('settings.nas-views.columns.destroy', [$view, $col]) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Supprimer cette colonne ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                        <i class="bi bi-x small"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endforeach
    @endif

    {{-- Modal : Modifier la vue --}}
    <div class="modal fade" id="editViewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Modifier la vue</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ url('settings/nas-views') }}/' + editViewId">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nom *</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   x-model="editViewName" required>
                        </div>
                        <div class="mb-2">
                            <div class="form-check">
                                <input type="checkbox" name="is_nas_page_default" value="1"
                                       class="form-check-input" id="chkNasDefault"
                                       x-model="editViewNas">
                                <label class="form-check-label small" for="chkNasDefault">
                                    <i class="bi bi-hdd-stack me-1 text-primary"></i>Défaut page NAS
                                </label>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="form-check">
                                <input type="checkbox" name="is_dashboard_default" value="1"
                                       class="form-check-input" id="chkDashDefault"
                                       x-model="editViewDash">
                                <label class="form-check-label small" for="chkDashDefault">
                                    <i class="bi bi-speedometer2 me-1 text-success"></i>Défaut Dashboard
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-warning">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal : Ajouter une colonne --}}
    <div class="modal fade" id="addColModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-plus-lg me-2"></i>Ajouter une colonne</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ url('settings/nas-views') }}/' + addColViewId + '/columns'">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Source *</label>
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm"
                                        :class="addColSource==='device' ? 'btn-secondary' : 'btn-outline-secondary'"
                                        @click="addColSource='device'; addColKey=''">
                                    <i class="bi bi-hdd-stack me-1"></i>Données NAS
                                </button>
                                <button type="button" class="btn btn-sm"
                                        :class="addColSource==='custom_field' ? 'btn-info' : 'btn-outline-secondary'"
                                        @click="addColSource='custom_field'; addColKey=''">
                                    <i class="bi bi-person-vcard me-1"></i>Informations client
                                </button>
                                <button type="button" class="btn btn-sm"
                                        :class="addColSource==='global_attribute' ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="addColSource='global_attribute'; addColKey=''">
                                    <i class="bi bi-diagram-2 me-1"></i>Attribut global
                                </button>
                            </div>
                            <input type="hidden" name="source" x-model="addColSource">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Champ *</label>
                            <select name="field_key" class="form-select form-select-sm" x-model="addColKey" required>
                                <option value="">— Choisir —</option>
                                <template x-if="addColSource === 'device'">
                                    <optgroup label="Données NAS">
                                        @foreach($deviceCols as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                </template>
                                <template x-if="addColSource === 'custom_field'">
                                    <optgroup label="Informations client">
                                        @foreach($customFieldDefs as $def)
                                        <option value="{{ $def->id }}">{{ $def->label }}</option>
                                        @endforeach
                                    </optgroup>
                                </template>
                                <template x-if="addColSource === 'global_attribute'">
                                    <optgroup label="Attributs globaux">
                                        <template x-for="ga in globalAttributes" :key="ga.id">
                                            <option :value="ga.id" x-text="ga.name + (ga.unit ? ' (' + ga.unit + ')' : '')"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Label personnalisé <span class="text-muted">(optionnel)</span></label>
                            <input type="text" name="label" class="form-control form-control-sm"
                                   x-model="addColLabel" placeholder="Laisser vide pour utiliser le nom par défaut">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="!addColKey">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>{{-- /x-data --}}
</x-app-layout>
