<x-app-layout>
    <x-slot name="title">Logs API Agent</x-slot>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-journal-code me-2 text-primary"></i>Logs API Agent
                <span class="text-muted fw-normal small ms-1">({{ $logs->total() }})</span>
            </h6>
            <div x-data="{ showPurge: false }" class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        @click="showPurge = !showPurge">
                    <i class="bi bi-trash me-1"></i>Vider
                </button>
                <div x-show="showPurge" style="display:none" class="d-flex gap-2 align-items-center">
                    <form method="POST" action="{{ route('api-logs.destroy') }}">
                        @csrf @method('DELETE')
                        <input type="hidden" name="older_than_days" value="30">
                        <button type="submit" class="btn btn-sm btn-outline-warning"
                                onclick="return confirm('Supprimer les logs de plus de 30 jours ?')">
                            &gt; 30 jours
                        </button>
                    </form>
                    <form method="POST" action="{{ route('api-logs.destroy') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Supprimer TOUS les logs ?')">
                            Tout
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="showPurge = false">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom py-2">
            <form method="GET" action="{{ route('api-logs.index') }}" class="d-flex gap-2 align-items-end flex-wrap">
                <div>
                    <label class="form-label small mb-1 text-muted">NAS</label>
                    <input type="text" name="nas" class="form-control form-control-sm" style="width:200px"
                           placeholder="Série ou nom…" value="{{ request('nas') }}">
                </div>
                <div>
                    <label class="form-label small mb-1 text-muted">Statut</label>
                    <select name="status" class="form-select form-select-sm" style="width:160px">
                        <option value="">— Tous —</option>
                        <option value="200"  {{ request('status') == '200'  ? 'selected' : '' }}>200 OK</option>
                        <option value="401"  {{ request('status') == '401'  ? 'selected' : '' }}>401 Signature invalide</option>
                        <option value="422"  {{ request('status') == '422'  ? 'selected' : '' }}>422 Validation</option>
                        <option value="500"  {{ request('status') == '500'  ? 'selected' : '' }}>500 Erreur serveur</option>
                    </select>
                </div>
                <div>
                    <label class="form-label small mb-1 text-muted">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ request('date') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filtrer
                </button>
                @if(request()->hasAny(['nas','status','date']))
                    <a href="{{ route('api-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x me-1"></i>Réinitialiser
                    </a>
                @endif
            </form>
        </div>

        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x display-4 d-block mb-2 opacity-25"></i>
                    Aucun log pour ces critères.
                </div>
            @else
                <div x-data="tableController()" x-init="init()">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light user-select-none">
                            <tr>
                                <th @click="sortBy(0)" style="cursor:pointer">Date / Heure <i class="bi small ms-1" :class="sortIcon(0)"></i></th>
                                <th @click="sortBy(1)" style="cursor:pointer">NAS <i class="bi small ms-1" :class="sortIcon(1)"></i></th>
                                <th @click="sortBy(2)" style="cursor:pointer">IP <i class="bi small ms-1" :class="sortIcon(2)"></i></th>
                                <th @click="sortBy(3)" style="cursor:pointer">Statut <i class="bi small ms-1" :class="sortIcon(3)"></i></th>
                                <th @click="sortBy(4)" style="cursor:pointer">Durée <i class="bi small ms-1" :class="sortIcon(4)"></i></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody x-ref="tbody">
                            @foreach($logs as $log)
                            <tr>
                                <td class="text-nowrap font-monospace">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </td>
                                <td>
                                    @php
                                        $nasName   = $log->nas?->name;
                                        $nasSerial = $log->nas_serial;
                                        $nasLink   = $log->nas_id ? route('nas.show', $log->nas_id) : null;
                                        $label     = $nasName ?: $nasSerial;
                                    @endphp
                                    @if($nasLink)
                                        <a href="{{ $nasLink }}" class="text-decoration-none fw-medium">
                                            {{ $label ?? '#' . $log->nas_id }}
                                        </a>
                                        @if($nasName && $nasSerial)
                                            <span class="text-muted d-block" style="font-size:.75rem">{{ $nasSerial }}</span>
                                        @endif
                                    @elseif($nasSerial)
                                        <span class="text-muted font-monospace">{{ $nasSerial }}</span>
                                    @else
                                        <span class="text-muted fst-italic">—</span>
                                    @endif
                                </td>
                                <td class="font-monospace text-muted">{{ $log->ip_address ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->statusColor }}">
                                        {{ $log->status_code }} {{ $log->statusLabel }}
                                    </span>
                                </td>
                                <td class="text-muted">
                                    @if($log->duration_ms !== null)
                                        {{ $log->duration_ms }}&nbsp;ms
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('api-logs.show', $log) }}"
                                       class="btn btn-sm btn-outline-secondary py-0 px-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>{{-- /tableController --}}
                <div class="card-footer bg-white">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
