<x-app-layout>
    <x-slot name="title">NAS</x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <h6 class="mb-0 fw-semibold">Tous les NAS ({{ $nasList->count() }})</h6>
            <div class="d-flex align-items-center gap-2 ms-auto">
                @if($allViews->isNotEmpty())
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-table me-1"></i>
                        {{ $configuredView ? $configuredView->name : 'Vue par défaut' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item small {{ !$configuredView ? 'active' : '' }}"
                               href="{{ route('nas.index') }}">
                                <i class="bi bi-list-ul me-2"></i>Vue par défaut
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        @foreach($allViews as $v)
                        <li>
                            <a class="dropdown-item small {{ $configuredView?->id === $v->id ? 'active' : '' }}"
                               href="{{ route('nas.index', ['view' => $v->id]) }}">
                                <i class="bi bi-table me-2"></i>{{ $v->name }}
                                @if($v->is_nas_page_default)
                                    <span class="badge bg-primary ms-1" style="font-size:.6rem">défaut</span>
                                @endif
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($nasList->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-hdd-stack display-4 d-block mb-2 opacity-25"></i>
                    Aucun NAS enregistré.
                </div>
            @elseif($configuredView && $configuredView->columns->isNotEmpty())
                {{-- Vue configurée --}}
                <div x-data="tableController()" x-init="init()">
                    <x-table-search />
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light user-select-none">
                                <tr>
                                    @foreach($configuredView->columns as $col)
                                        <th @click="sortBy({{ $loop->index }})" style="cursor:pointer">
                                            {{ $col->label ?: $col->getDisplayLabel() }}
                                            <i class="bi small ms-1" :class="sortIcon({{ $loop->index }})"></i>
                                        </th>
                                    @endforeach
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody x-ref="tbody">
                                @foreach($nasList as $nas)
                                <tr>
                                    @foreach($configuredView->columns as $col)
                                    <td>
                                        <x-nas-table-cell :nas="$nas" :column="$col" :customFieldDefs="$customFieldDefs" :globalAttributeValues="$globalAttributeValues" />
                                    </td>
                                    @endforeach
                                    <td class="text-end">
                                        <a href="{{ route('nas.show', $nas) }}" class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" action="{{ route('nas.destroy', $nas) }}" class="d-inline"
                                              onsubmit="return confirm('Supprimer ce NAS et toutes ses données ?')">
                                            @csrf @method('DELETE')
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
                </div>
            @else
                {{-- Vue par défaut (aucune vue configurée) --}}
                <div x-data="tableController()" x-init="init()">
                    <x-table-search />
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light user-select-none">
                                <tr>
                                    <th @click="sortBy(0)" style="cursor:pointer">Nom <i class="bi small ms-1" :class="sortIcon(0)"></i></th>
                                    <th @click="sortBy(1)" style="cursor:pointer">Modèle <i class="bi small ms-1" :class="sortIcon(1)"></i></th>
                                    <th @click="sortBy(2)" style="cursor:pointer">N° Série <i class="bi small ms-1" :class="sortIcon(2)"></i></th>
                                    <th @click="sortBy(3)" style="cursor:pointer">Version DSM <i class="bi small ms-1" :class="sortIcon(3)"></i></th>
                                    <th @click="sortBy(4)" style="cursor:pointer">Modèle API <i class="bi small ms-1" :class="sortIcon(4)"></i></th>
                                    <th @click="sortBy(5)" style="cursor:pointer">Décodeur <i class="bi small ms-1" :class="sortIcon(5)"></i></th>
                                    <th @click="sortBy(6)" style="cursor:pointer">Dernier contact <i class="bi small ms-1" :class="sortIcon(6)"></i></th>
                                    <th @click="sortBy(7)" style="cursor:pointer">Statut <i class="bi small ms-1" :class="sortIcon(7)"></i></th>
                                    <th @click="sortBy(8)" style="cursor:pointer">Snapshots <i class="bi small ms-1" :class="sortIcon(8)"></i></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody x-ref="tbody">
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
                                            @csrf @method('DELETE')
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
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
