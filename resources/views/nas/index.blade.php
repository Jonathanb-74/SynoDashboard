<x-app-layout>
    <x-slot name="title">NAS</x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">Tous les NAS ({{ $nasList->count() }})</h6>
            <a href="{{ route('test.index') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tester un NAS
            </a>
        </div>
        <div class="card-body p-0">
            @if($nasList->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-hdd-stack display-4 d-block mb-2 opacity-25"></i>
                    Aucun NAS enregistré.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Modèle</th>
                                <th>N° Série</th>
                                <th>Version DSM</th>
                                <th>Modèle API</th>
                                <th>Décodeur</th>
                                <th>Dernier contact</th>
                                <th>Statut</th>
                                <th>Snapshots</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($nasList as $nas)
                            <tr>
                                <td class="fw-medium">{{ $nas->name }}</td>
                                <td>{{ $nas->model ?? '—' }}</td>
                                <td class="font-monospace small">{{ $nas->serial }}</td>
                                <td>{{ $nas->dsm_version ?? '—' }}</td>
                                <td>{{ $nas->apiModel?->name ?? '—' }}</td>
                                <td>{{ $nas->decoderModel?->name ?? '—' }}</td>
                                <td>
                                    @if($nas->last_contact_at)
                                        <span title="{{ $nas->last_contact_at->format('d/m/Y H:i:s') }}">
                                            {{ $nas->last_contact_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>@include('components.status-badge', ['status' => $nas->status])</td>
                                <td class="text-muted small">{{ $nas->snapshots_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('nas.show', $nas) }}" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form method="POST" action="{{ route('nas.destroy', $nas) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce NAS et toutes ses données ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
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
