<x-app-layout>
    <x-slot name="title">Champs NAS personnalisés</x-slot>

    <div x-data="{
        createOpen: false,
        createType: '{{ old('type', 'text') }}',
        editId: null, editLabel: '', editType: 'text', editOptions: '',
        openEdit(def) {
            this.editId      = def.id;
            this.editLabel   = def.label;
            this.editType    = def.type;
            this.editOptions = def.options ? def.options.join(', ') : '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show();
        }
    }">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
            <span class="fw-semibold"><i class="bi bi-card-list me-2 text-primary"></i>Champs personnalisés NAS</span>
            <button class="btn btn-sm btn-primary" @click="createOpen = !createOpen">
                <i class="bi bi-plus-lg me-1"></i>Ajouter un champ
            </button>
        </div>

        {{-- Formulaire de création --}}
        <div x-show="createOpen" x-cloak class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('settings.nas-fields.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-medium mb-1">Nom du champ *</label>
                        <input type="text" name="label" class="form-control form-control-sm"
                               value="{{ old('label') }}" required autofocus placeholder="ex : Client, Emplacement…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium mb-1">Type *</label>
                        <select name="type" class="form-select form-select-sm" x-model="createType">
                            <option value="text">Texte court</option>
                            <option value="textarea">Texte long</option>
                            <option value="date">Date</option>
                            <option value="boolean">Case à cocher</option>
                            <option value="select">Menu déroulant</option>
                        </select>
                    </div>
                    <div class="col-md-4" x-show="createType === 'select'">
                        <label class="form-label small fw-medium mb-1">Options (séparées par virgule)</label>
                        <input type="text" name="options" class="form-control form-control-sm"
                               value="{{ old('options') }}" placeholder="Haute, Moyenne, Basse">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Créer</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                @click="createOpen = false">Annuler</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Liste des champs --}}
        @if($definitions->isEmpty())
            <div class="card-body text-muted small fst-italic text-center py-4">
                Aucun champ défini. Ajoutez votre premier champ ci-dessus.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:2.5rem">#</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Options</th>
                        <th style="width:8rem"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($definitions as $def)
                    <tr>
                        <td class="text-muted font-monospace">{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $def->label }}</td>
                        <td>
                            @php $typeLabels = ['text'=>'Texte court','textarea'=>'Texte long','date'=>'Date','boolean'=>'Case à cocher','select'=>'Menu déroulant']; @endphp
                            <span class="badge bg-secondary">{{ $typeLabels[$def->type] ?? $def->type }}</span>
                        </td>
                        <td class="text-muted small">
                            @if($def->type === 'select' && $def->options)
                                {{ implode(', ', $def->options) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            {{-- Monter --}}
                            @if(!$loop->first)
                            <form method="POST" action="{{ route('settings.nas-fields.reorder') }}" class="d-inline">
                                @csrf
                                @php
                                    $ids = $definitions->pluck('id')->toArray();
                                    $idx = array_search($def->id, $ids);
                                    [$ids[$idx], $ids[$idx-1]] = [$ids[$idx-1], $ids[$idx]];
                                @endphp
                                @foreach($ids as $i => $fid)
                                    <input type="hidden" name="ids[]" value="{{ $fid }}">
                                @endforeach
                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Monter">
                                    <i class="bi bi-arrow-up small"></i>
                                </button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 invisible"><i class="bi bi-arrow-up small"></i></button>
                            @endif
                            {{-- Descendre --}}
                            @if(!$loop->last)
                            <form method="POST" action="{{ route('settings.nas-fields.reorder') }}" class="d-inline">
                                @csrf
                                @php
                                    $ids2 = $definitions->pluck('id')->toArray();
                                    $idx2 = array_search($def->id, $ids2);
                                    [$ids2[$idx2], $ids2[$idx2+1]] = [$ids2[$idx2+1], $ids2[$idx2]];
                                @endphp
                                @foreach($ids2 as $i => $fid)
                                    <input type="hidden" name="ids[]" value="{{ $fid }}">
                                @endforeach
                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Descendre">
                                    <i class="bi bi-arrow-down small"></i>
                                </button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 invisible"><i class="bi bi-arrow-down small"></i></button>
                            @endif
                            {{-- Modifier --}}
                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                                    title="Modifier"
                                    @click="openEdit({{ $def->toJson() }})">
                                <i class="bi bi-pencil small"></i>
                            </button>
                            {{-- Supprimer --}}
                            <form method="POST" action="{{ route('settings.nas-fields.destroy', $def) }}" class="d-inline"
                                  onsubmit="return confirm('Supprimer « {{ $def->label }} » et toutes ses valeurs ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Supprimer">
                                    <i class="bi bi-trash small"></i>
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

    {{-- Modal édition --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Modifier le champ</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ url('settings/nas-fields') }}/' + editId" id="editForm">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nom du champ *</label>
                            <input type="text" name="label" class="form-control form-control-sm"
                                   x-model="editLabel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Type *</label>
                            <select name="type" class="form-select form-select-sm" x-model="editType">
                                <option value="text">Texte court</option>
                                <option value="textarea">Texte long</option>
                                <option value="date">Date</option>
                                <option value="boolean">Case à cocher</option>
                                <option value="select">Menu déroulant</option>
                            </select>
                        </div>
                        <div class="mb-0" x-show="editType === 'select'">
                            <label class="form-label small fw-medium">Options (séparées par virgule)</label>
                            <input type="text" name="options" class="form-control form-control-sm"
                                   x-model="editOptions" placeholder="Haute, Moyenne, Basse">
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

    </div>{{-- /x-data --}}
</x-app-layout>
