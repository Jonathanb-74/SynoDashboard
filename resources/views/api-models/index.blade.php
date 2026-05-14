<x-app-layout>
    <x-slot name="title">Modèles API</x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">Modèles API ({{ $models->count() }})</h6>
            <a href="{{ route('api-models.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nouveau modèle
            </a>
        </div>
        <div class="card-body p-0">
            @if($models->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 display-4 d-block mb-2 opacity-25"></i>
                    Aucun modèle API.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Entrées API</th>
                                <th>Décodeur JSON lié</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($models as $model)
                            <tr>
                                <td class="fw-medium">{{ $model->name }}</td>
                                <td class="text-muted small">{{ $model->description ?? '—' }}</td>
                                <td>{{ $model->entries_count }}</td>
                                <td>
                                    @if($model->decoderModel)
                                        <a href="{{ route('decoder-models.edit', $model->decoderModel) }}" class="text-decoration-none small">
                                            <i class="bi bi-code-square me-1 text-success"></i>{{ $model->decoderModel->name }}
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('api-models.create-decoder', $model) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success py-0 px-1" title="Créer un décodeur JSON lié">
                                                <i class="bi bi-plus-circle me-1"></i><span class="small">Créer décodeur</span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('api-models.show', $model) }}" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('api-models.edit', $model) . '?filter=active' }}" class="btn btn-sm btn-outline-primary me-1" title="Modifier les entrées actives">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('api-models.duplicate', $model) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary me-1" title="Dupliquer">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('api-models.destroy', $model) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce modèle API ?')">
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
</x-app-layout>
