<x-app-layout>
    <x-slot name="title">Nouveau modèle API</x-slot>

    <div class="card border-0 shadow-sm" x-data="{
        entries: [{ api_name:'', path:'entry.cgi', method:'get', min_version:1, max_version:99, enabled:true, parameters:'' }],
        methods: {{ json_encode($methods->values()) }},
        addEntry() { this.entries.push({ api_name:'', path:'entry.cgi', method:'get', min_version:1, max_version:99, enabled:true, parameters:'' }); },
        removeEntry(i) { this.entries.splice(i, 1); }
    }">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold">Nouveau modèle API</h6>
        </div>
        <form method="POST" action="{{ route('api-models.store') }}"
              @submit.prevent="$refs.entriesJson.value = JSON.stringify(entries); $el.submit()">
            @csrf
            <input type="hidden" name="entries_json" x-ref="entriesJson">
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Nom *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-medium">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ old('description') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-medium">Décodeur JSON lié</label>
                        <select name="decoder_model_id" class="form-select">
                            <option value="">— aucun —</option>
                            @foreach($decoderModels as $dm)
                                <option value="{{ $dm->id }}" {{ old('decoder_model_id') == $dm->id ? 'selected' : '' }}>
                                    {{ $dm->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h6 class="fw-semibold mb-3">Entrées API</h6>

                <div class="row g-2 mb-1 small fw-medium text-muted">
                    <div class="col-md-3">Nom API</div>
                    <div class="col-md-2">Chemin</div>
                    <div class="col-md-2">Méthode</div>
                    <div class="col-md-1">Min</div>
                    <div class="col-md-1">Max</div>
                    <div class="col-md-2">Paramètres JSON</div>
                    <div class="col-md-1">Actif</div>
                </div>

                <template x-for="(entry, i) in entries" :key="i">
                    <div class="row g-2 mb-2 align-items-end">
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
                                   placeholder="Min" x-model="entry.min_version" min="1">
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control form-control-sm"
                                   placeholder="Max" x-model="entry.max_version" min="1">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control form-control-sm"
                                   placeholder='{"key":"val"}' x-model="entry.parameters">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" x-model="entry.enabled">
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeEntry(i)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" @click="addEntry()">
                    <i class="bi bi-plus me-1"></i>Ajouter une API
                </button>
            </div>

            <div class="card-footer bg-white d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Créer le modèle
                </button>
                <a href="{{ route('api-models.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</x-app-layout>
