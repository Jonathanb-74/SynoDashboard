<x-app-layout>
    <x-slot name="title">Attributs globaux</x-slot>

    <div x-data="{
        createOpen: false,
        editId: null, editName: '', editUnit: '', editDesc: '',
        openEdit(a) {
            this.editId   = a.id;
            this.editName = a.name;
            this.editUnit = a.unit ?? '';
            this.editDesc = a.description ?? '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editAttrModal')).show();
        }
    }">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
            <span class="fw-semibold">
                <i class="bi bi-diagram-2 me-2 text-primary"></i>Attributs globaux NAS
            </span>
            <button class="btn btn-sm btn-primary" @click="createOpen = !createOpen">
                <i class="bi bi-plus-lg me-1"></i>Ajouter
            </button>
        </div>

        {{-- Formulaire création --}}
        <div x-show="createOpen" x-cloak class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('settings.global-attributes.store') }}">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-medium mb-1">Nom *</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="{{ old('name') }}" required autofocus placeholder="ex : Processeur, RAM totale…">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-medium mb-1">Unité</label>
                        <input type="text" name="unit" class="form-control form-control-sm"
                               value="{{ old('unit') }}" placeholder="ex : °C, GB">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium mb-1">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm"
                               value="{{ old('description') }}" placeholder="Optionnel">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Créer</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                                @click="createOpen = false">Annuler</button>
                    </div>
                </div>
            </form>
        </div>

        @if($attributes->isEmpty())
            <div class="card-body text-center text-muted small fst-italic py-4">
                Aucun attribut défini.<br>
                Créez des attributs pour normaliser les données entre vos décodeurs JSON.
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:2rem">#</th>
                        <th>Nom</th>
                        <th>Unité</th>
                        <th>Description</th>
                        <th>Mappings</th>
                        <th style="width:7rem"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attributes as $attr)
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-medium">{{ $attr->name }}</td>
                        <td class="text-muted font-monospace">{{ $attr->unit ?: '—' }}</td>
                        <td class="text-muted small">{{ $attr->description ?: '—' }}</td>
                        <td>
                            @php $count = $attr->mappings()->count(); @endphp
                            @if($count > 0)
                                <span class="badge bg-success">{{ $count }} décodeur{{ $count > 1 ? 's' : '' }}</span>
                            @else
                                <span class="text-muted small fst-italic">aucun</span>
                            @endif
                        </td>
                        <td class="text-end pe-2">
                            {{-- Monter --}}
                            @if(!$loop->first)
                            <form method="POST" action="{{ route('settings.global-attributes.reorder') }}" class="d-inline">
                                @csrf
                                @php
                                    $ids = $attributes->pluck('id')->toArray();
                                    $idx = array_search($attr->id, $ids);
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
                            <form method="POST" action="{{ route('settings.global-attributes.reorder') }}" class="d-inline">
                                @csrf
                                @php
                                    $ids2 = $attributes->pluck('id')->toArray();
                                    $idx2 = array_search($attr->id, $ids2);
                                    [$ids2[$idx2], $ids2[$idx2+1]] = [$ids2[$idx2+1], $ids2[$idx2]];
                                @endphp
                                @foreach($ids2 as $fid)<input type="hidden" name="ids[]" value="{{ $fid }}">@endforeach
                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"><i class="bi bi-arrow-down small"></i></button>
                            </form>
                            @else
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 invisible"><i class="bi bi-arrow-down small"></i></button>
                            @endif
                            {{-- Modifier --}}
                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2"
                                    @click="openEdit({{ $attr->toJson() }})">
                                <i class="bi bi-pencil small"></i>
                            </button>
                            {{-- Supprimer --}}
                            <form method="POST" action="{{ route('settings.global-attributes.destroy', $attr) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Supprimer « {{ $attr->name }} » et tous ses mappings ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
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

    <div class="alert alert-info mt-3 small">
        <i class="bi bi-info-circle me-2"></i>
        Une fois les attributs définis, ouvrez chaque <strong>décodeur JSON</strong> et mappez ses éléments simples
        sur ces attributs. Les attributs mappés seront ensuite disponibles comme colonnes dans les <strong>Vues NAS</strong>.
    </div>

    {{-- Modal édition --}}
    <div class="modal fade" id="editAttrModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-pencil me-2"></i>Modifier l'attribut</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ url('settings/global-attributes') }}/' + editId">
                    @csrf @method('PATCH')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Nom *</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   x-model="editName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Unité</label>
                            <input type="text" name="unit" class="form-control form-control-sm"
                                   x-model="editUnit" placeholder="ex : °C, GB, MHz">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-medium">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm"
                                   x-model="editDesc">
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
