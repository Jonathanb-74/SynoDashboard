<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="bi bi-hdd-stack fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
                        <div class="text-muted small">NAS total</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">{{ $stats['approved'] }}</div>
                        <div class="text-muted small">Approuvés</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                        <i class="bi bi-clock-history fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">{{ $stats['pending'] }}</div>
                        <div class="text-muted small">En attente</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                        <i class="bi bi-x-circle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">{{ $stats['rejected'] }}</div>
                        <div class="text-muted small">Rejetés</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- NAS List --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">Tous les NAS</h6>
            <a href="{{ route('test.index') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tester un NAS
            </a>
        </div>
        <div class="card-body p-0">
            @if($nasList->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-hdd-stack display-4 d-block mb-2 opacity-25"></i>
                    Aucun NAS enregistré. <a href="{{ route('test.index') }}">Tester un NAS</a> pour commencer.
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
                                <td>
                                    @if($nas->last_contact_at)
                                        <span title="{{ $nas->last_contact_at->format('d/m/Y H:i:s') }}">
                                            {{ $nas->last_contact_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @include('components.status-badge', ['status' => $nas->status])
                                </td>
                                <td class="text-muted small">{{ $nas->snapshots_count }}</td>
                                <td>
                                    <a href="{{ route('nas.show', $nas) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
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
