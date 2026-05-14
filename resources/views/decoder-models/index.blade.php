<x-app-layout>
    <x-slot name="title">Décodeurs JSON</x-slot>

    <div x-data="{ copySourceId: null, copySourceName: '' }">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">Décodeurs JSON ({{ $models->count() }})</h6>
            <a href="{{ route('decoder-models.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nouveau décodeur
            </a>
        </div>
        <div class="card-body p-0">
            @if($models->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-code-square display-4 d-block mb-2 opacity-25"></i>
                    Aucun décodeur JSON.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Blocs</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($models as $model)
                            <tr>
                                <td class="fw-medium">{{ $model->name }}</td>
                                <td class="text-muted small">{{ $model->description ?? '—' }}</td>
                                <td>{{ $model->blocks_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('decoder-models.edit', $model) }}" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('decoder-models.duplicate', $model) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Dupliquer">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" title="Copier vers..."
                                            @click="copySourceId = {{ $model->id }}; copySourceName = '{{ addslashes($model->name) }}'; bootstrap.Modal.getOrCreateInstance(document.getElementById('copyToModal')).show()">
                                        <i class="bi bi-arrow-right-square"></i>
                                    </button>
                                    <form method="POST" action="{{ route('decoder-models.destroy', $model) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce décodeur ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
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
    </div>

    {{-- Modal copier vers --}}
    <div class="modal fade" id="copyToModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold">
                        <i class="bi bi-arrow-right-square me-2 text-primary"></i>Copier vers un autre décodeur
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'{{ route('decoder-models.copy-to', '__id__') }}'.replace('__id__', copySourceId)">
                    @csrf
                    <div class="modal-body">
                        <p class="small mb-3">
                            Source : <strong x-text="copySourceName"></strong>
                        </p>

                        <div class="mb-3">
                            <label class="form-label small fw-medium">Décodeur cible</label>
                            <select name="target_id" class="form-select form-select-sm" required>
                                <option value="">— choisir —</option>
                                @foreach($models as $m)
                                    <option value="{{ $m->id }}" x-bind:disabled="copySourceId === {{ $m->id }}">
                                        {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label small fw-medium">Mode</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" value="append" id="mode-append" checked>
                                    <label class="form-check-label small" for="mode-append">
                                        <strong>Ajouter à la fin</strong> — conserve les blocs existants
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode" value="replace" id="mode-replace">
                                    <label class="form-check-label small" for="mode-replace">
                                        <strong>Remplacer</strong> — efface tout le contenu du décodeur cible
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-right-square me-1"></i>Copier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>{{-- /.modal --}}
    </div>{{-- /x-data wrapper --}}
</x-app-layout>
