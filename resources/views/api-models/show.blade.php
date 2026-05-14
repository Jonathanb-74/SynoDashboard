<x-app-layout>
    <x-slot name="title">{{ $apiModel->name }}</x-slot>

    <div x-data="{
        entries: {{ json_encode($apiModel->entries->map(fn($e) => [
            'api_name'    => $e->api_name,
            'path'        => $e->path,
            'method'      => $e->method,
            'version'     => $e->version,
            'min_version' => $e->min_version,
            'max_version' => $e->max_version,
            'enabled'     => $e->enabled,
            'parameters'  => $e->parameters ? json_encode($e->parameters) : '',
        ])->values()) }},
        otherModelsByApi: {{ Illuminate\Support\Js::from($otherModelsByApi) }},
        propagateApiName: '',
        propagateTargets: [],
        openPropagateModal(apiName) {
            this.propagateApiName = apiName;
            this.propagateTargets = this.otherModelsByApi[apiName] ?? [];
            bootstrap.Modal.getOrCreateInstance(document.getElementById('propagateModal')).show();
        },
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
        <div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
            <a href="{{ route('api-models.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            <a href="{{ route('api-models.edit', $apiModel) . '?filter=active' }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil me-1"></i>Modifier actifs
            </a>
            <a href="{{ route('api-models.edit', $apiModel) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i>Modifier tout
            </a>
            <form method="POST" action="{{ route('api-models.duplicate', $apiModel) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-copy me-1"></i>Dupliquer
                </button>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
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
                                <th style="cursor:pointer" @click="sortBy('version')" title="Version forcée à envoyer (si définie)">
                                    Envoi <i class="bi" :class="sortIcon('version', true)"></i>
                                </th>
                                <th>Paramètres</th>
                                <th style="cursor:pointer" @click="sortBy('enabled')">
                                    Actif <i class="bi" :class="sortIcon('enabled', false)"></i>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="filtered.length === 0">
                                <tr><td colspan="9" class="text-center text-muted py-3">Aucun résultat.</td></tr>
                            </template>
                            <template x-for="entry in filtered" :key="entry.api_name">
                                <tr>
                                    <td class="fw-medium" x-text="entry.api_name"></td>
                                    <td class="font-monospace" x-text="entry.path"></td>
                                    <td><span class="badge bg-secondary" x-text="entry.method"></span></td>
                                    <td x-text="'v' + entry.min_version"></td>
                                    <td x-text="'v' + entry.max_version"></td>
                                    <td>
                                        <template x-if="entry.version">
                                            <span class="badge bg-primary" x-text="'v' + entry.version" title="Version forcée"></span>
                                        </template>
                                        <template x-if="!entry.version">
                                            <span class="text-muted small">auto</span>
                                        </template>
                                    </td>
                                    <td x-text="entry.parameters || '—'"></td>
                                    <td>
                                        <template x-if="entry.enabled">
                                            <i class="bi bi-check-circle text-success"></i>
                                        </template>
                                        <template x-if="!entry.enabled">
                                            <i class="bi bi-x-circle text-danger"></i>
                                        </template>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                                title="Propager les paramètres vers d'autres modèles"
                                                @click="openPropagateModal(entry.api_name)">
                                            <i class="bi bi-broadcast"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        </div>{{-- /.card --}}

    {{-- Modal propagation --}}
    <div class="modal fade" id="propagateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold">
                        <i class="bi bi-broadcast me-2 text-primary"></i>Propager les paramètres
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('api-models.propagate-entry', $apiModel) }}">
                    @csrf
                    <input type="hidden" name="api_name" :value="propagateApiName">
                    <div class="modal-body">
                        <p class="small mb-1">
                            Copier les paramètres de <strong x-text="propagateApiName"></strong> vers :
                        </p>
                        <template x-if="propagateTargets.length === 0">
                            <p class="text-muted small fst-italic">
                                Aucun autre modèle ne contient cette API.
                            </p>
                        </template>
                        <template x-if="propagateTargets.length > 0">
                            <div>
                                <template x-for="model in propagateTargets" :key="model.id">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input"
                                               name="target_model_ids[]"
                                               :value="model.id"
                                               :id="'pm-' + model.id">
                                        <label class="form-check-label small" :for="'pm-' + model.id" x-text="model.name"></label>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="propagateTargets.length === 0">
                            <i class="bi bi-broadcast me-1"></i>Propager
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>{{-- /.modal --}}
    </div>{{-- /x-data wrapper --}}
</x-app-layout>
