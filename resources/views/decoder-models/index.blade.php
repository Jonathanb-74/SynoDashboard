<x-app-layout>
    <x-slot name="title">Décodeurs JSON</x-slot>

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
</x-app-layout>
