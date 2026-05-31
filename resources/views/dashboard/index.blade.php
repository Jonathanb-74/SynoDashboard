<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    @php
        $builtinIcons = [
            'total'    => ['icon' => 'bi-hdd-stack',    'bg' => 'primary'],
            'approved' => ['icon' => 'bi-check-circle', 'bg' => 'success'],
            'pending'  => ['icon' => 'bi-clock-history','bg' => 'warning'],
            'rejected' => ['icon' => 'bi-x-circle',     'bg' => 'danger'],
        ];
    @endphp

    {{-- Widgets --}}
    @if($widgets->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach($widgets as $widget)
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-{{ $widget->color }} bg-opacity-10 p-3">
                        @if($widget->isBuiltin())
                            <i class="bi {{ $builtinIcons[$widget->builtin_key]['icon'] ?? 'bi-bar-chart' }} fs-4 text-{{ $widget->color }}"></i>
                        @else
                            <i class="bi bi-bar-chart fs-4 text-{{ $widget->color }}"></i>
                        @endif
                    </div>
                    <div>
                        <div class="fs-2 fw-bold">
                            @if($widget->isBuiltin())
                                {{ $builtinCounts[$widget->builtin_key] ?? 0 }}
                            @else
                                {{ $widgetCounts[$widget->id] ?? 0 }}
                            @endif
                        </div>
                        <div class="text-muted small">{{ $widget->label }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tableau NAS --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
                Tous les NAS
                @if($configuredView)
                    <span class="text-muted fw-normal small ms-2">— Vue : {{ $configuredView->name }}</span>
                @endif
            </h6>
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
            @elseif($configuredView && $configuredView->columns->isNotEmpty())
                {{-- Vue configurée --}}
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                @foreach($configuredView->columns as $col)
                                    <th>{{ $col->label ?: $col->getDisplayLabel() }}</th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($nasList as $nas)
                            <tr>
                                @foreach($configuredView->columns as $col)
                                <td>
                                    <x-nas-table-cell :nas="$nas" :column="$col" :customFieldDefs="$customFieldDefs" />
                                </td>
                                @endforeach
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
            @else
                {{-- Vue par défaut --}}
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
                                <td>@include('components.status-badge', ['status' => $nas->status])</td>
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
