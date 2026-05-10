<x-app-layout>
    <x-slot name="title">{{ $apiModel->name }}</x-slot>

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('api-models.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
        <a href="{{ route('api-models.edit', $apiModel) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-pencil me-1"></i>Modifier
        </a>
    </div>

    <div class="card border-0 shadow-sm" x-data="{
        entries: {{ json_encode($apiModel->entries->map(fn($e) => [
            'api_name'    => $e->api_name,
            'path'        => $e->path,
            'method'      => $e->method,
            'min_version' => $e->min_version,
            'max_version' => $e->max_version,
            'enabled'     => $e->enabled,
            'parameters'  => $e->parameters ? json_encode($e->parameters) : '',
        ])->values()) }},
        search: '',
        sortField: 'api_name',
        sortDir: 'asc',
        filterEnabled: 'all',
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'asc';
            }
        },
        sortIcon(field, numeric) {
            if (this.sortField !== field) return 'bi-arrow-down-up opacity-50';
            if (numeric) return this.sortDir === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
            return this.sortDir === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
        },
        get filtered() {
            let result = [...this.entries];
            if (this.search) {
                const q = this.search.toLowerCase();
                result = result.filter(e => e.api_name.toLowerCase().includes(q));
            }
            if (this.filterEnabled === 'yes') result = result.filter(e => e.enabled);
            if (this.filterEnabled === 'no')  result = result.filter(e => !e.enabled);
            const numeric = ['min_version', 'max_version'];
            result.sort((a, b) => {
                let va = a[this.sortField] ?? '';
                let vb = b[this.sortField] ?? '';
                if (numeric.includes(this.sortField)) {
                    return this.sortDir === 'asc' ? Number(va) - Number(vb) : Number(vb) - Number(va);
                }
                va = String(va).toLowerCase();
                vb = String(vb).toLowerCase();
                return this.sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            });
            return result;
        }
    }">
        <div class="card-header bg-white d-flex align-items-start justify-content-between">
            <div>
                <h6 class="mb-0 fw-semibold">{{ $apiModel->name }}</h6>
                @if($apiModel->description)
                    <small class="text-muted">{{ $apiModel->description }}</small>
                @endif
            </div>
            <div class="text-end small">
                @if($apiModel->decoderModel)
                    <span class="text-muted">Décodeur JSON :</span>
                    <a href="{{ route('decoder-models.edit', $apiModel->decoderModel) }}" class="text-decoration-none ms-1">
                        <i class="bi bi-code-square text-success"></i> {{ $apiModel->decoderModel->name }}
                    </a>
                @else
                    <form method="POST" action="{{ route('api-models.create-decoder', $apiModel) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-plus-circle me-1"></i>Créer un décodeur JSON
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($apiModel->entries->isNotEmpty())
        <div class="card-body border-bottom py-2">
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="input-group input-group-sm" style="width:260px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" placeholder="Rechercher une API…" x-model="search">
                    <button type="button" class="btn btn-outline-secondary" x-show="search" @click="search=''" title="Effacer">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn"
                            :class="filterEnabled === 'all' ? 'btn-secondary' : 'btn-outline-secondary'"
                            @click="filterEnabled = 'all'">Toutes</button>
                    <button type="button" class="btn"
                            :class="filterEnabled === 'yes' ? 'btn-success' : 'btn-outline-secondary'"
                            @click="filterEnabled = 'yes'">
                        <i class="bi bi-check-circle me-1"></i>Actives
                    </button>
                    <button type="button" class="btn"
                            :class="filterEnabled === 'no' ? 'btn-danger' : 'btn-outline-secondary'"
                            @click="filterEnabled = 'no'">
                        <i class="bi bi-x-circle me-1"></i>Inactives
                    </button>
                </div>
                <span class="text-muted small ms-auto" x-text="filtered.length + ' / {{ $apiModel->entries->count() }} entrées'"></span>
            </div>
        </div>
        @endif

        <div class="card-body p-0">
            @if($apiModel->entries->isEmpty())
                <div class="text-center py-4 text-muted small">Aucune entrée API.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light user-select-none">
                            <tr>
                                <th style="cursor:pointer" @click="sortBy('api_name')">
                                    API <i class="bi" :class="sortIcon('api_name', false)"></i>
                                </th>
                                <th style="cursor:pointer" @click="sortBy('path')">
                                    Chemin <i class="bi" :class="sortIcon('path', false)"></i>
                                </th>
                                <th style="cursor:pointer" @click="sortBy('method')">
                                    Méthode <i class="bi" :class="sortIcon('method', false)"></i>
                                </th>
                                <th style="cursor:pointer" @click="sortBy('min_version')">
                                    Min <i class="bi" :class="sortIcon('min_version', true)"></i>
                                </th>
                                <th style="cursor:pointer" @click="sortBy('max_version')">
                                    Max <i class="bi" :class="sortIcon('max_version', true)"></i>
                                </th>
                                <th>Paramètres</th>
                                <th style="cursor:pointer" @click="sortBy('enabled')">
                                    Actif <i class="bi" :class="sortIcon('enabled', false)"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="filtered.length === 0">
                                <tr><td colspan="7" class="text-center text-muted py-3">Aucun résultat.</td></tr>
                            </template>
                            <template x-for="entry in filtered" :key="entry.api_name">
                                <tr>
                                    <td class="fw-medium" x-text="entry.api_name"></td>
                                    <td class="font-monospace" x-text="entry.path"></td>
                                    <td><span class="badge bg-secondary" x-text="entry.method"></span></td>
                                    <td x-text="'v' + entry.min_version"></td>
                                    <td x-text="'v' + entry.max_version"></td>
                                    <td x-text="entry.parameters || '—'"></td>
                                    <td>
                                        <template x-if="entry.enabled">
                                            <i class="bi bi-check-circle text-success"></i>
                                        </template>
                                        <template x-if="!entry.enabled">
                                            <i class="bi bi-x-circle text-danger"></i>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
