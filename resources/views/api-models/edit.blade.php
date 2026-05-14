<x-app-layout>
    <x-slot name="title">Modifier — {{ $apiModel->name }}</x-slot>

    <div class="card border-0 shadow-sm" x-data="{
        entries: {{ json_encode($apiModel->entries->map(fn($e) => [
            'api_name'     => $e->api_name,
            'path'         => $e->path,
            'method'       => $e->method,
            'version'      => $e->version,
            'min_version'  => $e->min_version,
            'max_version'  => $e->max_version,
            'enabled'      => $e->enabled,
            'parameters'   => $e->parameters ? json_encode($e->parameters) : '',
        ])->values()) }},
        methods: {{ json_encode($methods->values()) }},
        search: '',
        sortField: 'api_name',
        sortDir: 'asc',
        addEntry() { this.entries.push({ api_name:'', path:'entry.cgi', method:'get', version:null, min_version:1, max_version:99, enabled:true, parameters:'' }); },
        removeEntry(i) { this.entries.splice(i, 1); },
        sortBy(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'asc';
            }
            this.entries.sort((a, b) => {
                let va = String(a[field] ?? '').toLowerCase();
                let vb = String(b[field] ?? '').toLowerCase();
                return this.sortDir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va);
            });
        },
        isVisible(entry) {
            return !this.search || entry.api_name.toLowerCase().includes(this.search.toLowerCase());
        },
        toggleAll() {
            const visible = this.entries.filter(e => this.isVisible(e));
            const allOn = visible.every(e => e.enabled);
            visible.forEach(e => { e.enabled = !allOn; });
        },
        sortIcon(field, numeric) {
            if (this.sortField !== field) return 'bi-arrow-down-up opacity-50';
            if (numeric) return this.sortDir === 'asc' ? 'bi-sort-numeric-down' : 'bi-sort-numeric-up';
            return this.sortDir === 'asc' ? 'bi-sort-alpha-down' : 'bi-sort-alpha-up';
        }
    }">
        {{-- Soumission via un seul champ JSON pour éviter la limite max_input_vars --}}
        <form method="POST" action="{{ route('api-models.update', $apiModel) }}"
              @submit.prevent="$refs.entriesJson.value = JSON.stringify(entries); $el.submit()">
            @csrf @method('PUT')
            <input type="hidden" name="entries_json" x-ref="entriesJson">
            @if($filterActive)
                <input type="hidden" name="filter_active" value="1">
            @endif

            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold">Modifier le modèle API</h6>
                <div class="d-flex gap-2">
                    <a href="{{ route('api-models.show', $apiModel) }}" class="btn btn-sm btn-outline-secondary">Annuler</a>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-save me-1"></i>Enregistrer
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Nom *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $apiModel->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-medium">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ old('description', $apiModel->description) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Décodeur JSON lié</label>
                        <select name="decoder_model_id" class="form-select">
                            <option value="">— aucun —</option>
                            @foreach($decoderModels as $dm)
                                <option value="{{ $dm->id }}"
                                    {{ old('decoder_model_id', $apiModel->decoder_model_id) == $dm->id ? 'selected' : '' }}>
                                    {{ $dm->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted small">
                            Sera propagé aux NAS sans décodeur défini.
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-semibold mb-0">
                            Entrées API
                            <span class="text-muted fw-normal small ms-1"
                                  x-text="search ? entries.filter(e => isVisible(e)).length + ' / ' + entries.length + ' entrées' : entries.length + ' entrées'"></span>
                        </h6>
                        @if($filterActive)
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-funnel-fill me-1"></i>Actives uniquement
                                @if($totalCount) — {{ $apiModel->entries->count() }} / {{ $totalCount }} @endif
                            </span>
                            <a href="{{ route('api-models.edit', $apiModel) }}" class="btn btn-sm btn-outline-secondary py-0 px-2 small">
                                <i class="bi bi-list me-1"></i>Modifier tout
                            </a>
                        @endif
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm" style="width:240px">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Rechercher une API…" x-model="search">
                            <button type="button" class="btn btn-outline-secondary" x-show="search" @click="search=''" title="Effacer">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="addEntry()">
                            <i class="bi bi-plus me-1"></i>Ajouter
                        </button>
                    </div>
                </div>

                <div class="row g-2 mb-1 small fw-medium text-muted user-select-none">
                    <div class="col-md-3" style="cursor:pointer" @click="sortBy('api_name')">
                        Nom API <i class="bi" :class="sortIcon('api_name', false)"></i>
                    </div>
                    <div class="col-md-2" style="cursor:pointer" @click="sortBy('path')">
                        Chemin <i class="bi" :class="sortIcon('path', false)"></i>
                    </div>
                    <div class="col-md-2" style="cursor:pointer" @click="sortBy('method')">
                        Méthode <i class="bi" :class="sortIcon('method', false)"></i>
                    </div>
                    <div class="col-md-1" style="cursor:pointer" @click="sortBy('version')"
                         title="Version à envoyer (vide = min_version)">
                        Version <i class="bi" :class="sortIcon('version', true)"></i>
                    </div>
                    <div class="col-md-1 text-muted">
                        Plage
                    </div>
                    <div class="col-md-2">Paramètres JSON</div>
                    <div class="col-md-1" style="cursor:pointer" @click="toggleAll()"
                         title="Tout cocher / décocher les entrées visibles">
                        Actif <i class="bi bi-check-all"></i>
                    </div>
                </div>

                {{-- Les inputs n'ont plus de name : x-model met à jour entries[], soumis en JSON --}}
                <template x-for="(entry, i) in entries" :key="i">
                    <div class="row g-2 mb-2 align-items-end" x-show="isVisible(entry)">
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="SYNO.Core.System" x-model="entry.api_name">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="entry.cgi" x-model="entry.path">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" x-model="entry.method">
                                <template x-for="m in methods" :key="m">
                                    <option :value="m" :selected="entry.method === m" x-text="m"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control form-control-sm"
                                   x-model="entry.version" min="1" placeholder="auto"
                                   title="Vide = utilise min_version automatiquement">
                        </div>
                        <div class="col-md-1 d-flex align-items-center">
                            <span class="badge bg-light text-muted border font-monospace"
                                  style="font-size:.7rem"
                                  x-text="'v'+entry.min_version+'–v'+entry.max_version"></span>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm"
                                   placeholder='{"key":"val"}' x-model="entry.parameters">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" x-model="entry.enabled">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" disabled>
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="card-footer bg-white d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Enregistrer
                </button>
                <a href="{{ route('api-models.show', $apiModel) }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>

</x-app-layout>
