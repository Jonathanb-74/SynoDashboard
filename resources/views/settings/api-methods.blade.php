<x-app-layout>
    <x-slot name="title">Paramètres — Méthodes API</x-slot>

    <div class="row g-4">

        {{-- Liste + formulaire principal --}}
        <div class="col-lg-8">
            <form method="POST" action="{{ route('settings.api-methods.save-all') }}" id="methods-form">
                @csrf
                {{-- L'ordre sérialisé (JSON des IDs dans l'ordre affiché) est injecté avant soumission --}}
                <input type="hidden" name="order_json" id="order-json-input">

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-list-check me-2 text-primary"></i>
                            Méthodes disponibles
                            <span class="text-muted fw-normal small ms-1">({{ $methods->count() }})</span>
                        </h6>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-save me-1"></i>Enregistrer
                        </button>
                    </div>

                    {{-- En-tête colonnes --}}
                    <div class="d-flex align-items-center px-3 py-1 border-bottom bg-light small fw-medium text-muted user-select-none">
                        <div style="width:1.5rem"></div>
                        <div class="flex-grow-1 ms-3">Nom</div>
                        <div style="width:150px" class="text-center">
                            <i class="bi bi-bug me-1"></i>Debug activé
                        </div>
                        <div style="width:44px"></div>
                    </div>

                    <div id="methods-list" class="p-0">
                        @forelse($methods as $method)
                            <div class="d-flex align-items-center px-3 py-2 border-bottom method-row"
                                 data-id="{{ $method->id }}">
                                <i class="bi bi-grip-vertical text-muted grip-handle"
                                   style="width:1.5rem; cursor:grab"></i>
                                <span class="font-monospace fw-medium flex-grow-1 ms-3">
                                    {{ $method->name }}
                                </span>
                                <div style="width:150px" class="text-center">
                                    <div class="form-check form-switch d-flex align-items-center justify-content-center gap-2 mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               name="debug[]"
                                               value="{{ $method->id }}"
                                               {{ $method->debug_enabled ? 'checked' : '' }}>
                                        <span class="small {{ $method->debug_enabled ? 'text-success' : 'text-muted' }}">
                                            {{ $method->debug_enabled ? 'Oui' : 'Non' }}
                                        </span>
                                    </div>
                                </div>
                                <div style="width:44px" class="text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger delete-btn"
                                            data-id="{{ $method->id }}"
                                            data-name="{{ $method->name }}"
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">Aucune méthode configurée.</div>
                        @endforelse
                    </div>

                    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Glisser-déposer les lignes pour modifier l'ordre dans les menus déroulants.
                        </span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Ajouter une méthode --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-plus-circle me-2 text-success"></i>Ajouter une méthode
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.api-methods.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-medium">Nom de la méthode</label>
                            <input type="text" name="name"
                                   class="form-control font-monospace @error('name') is-invalid @enderror"
                                   placeholder="ex : get_status"
                                   value="{{ old('name') }}"
                                   autocomplete="off">
                            <div class="form-text text-muted">Lettres minuscules et underscores uniquement.</div>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus me-1"></i>Ajouter
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body small text-muted">
                    <p class="mb-2 fw-medium text-body">Méthodes marquées "Debug activé"</p>
                    <p class="mb-0">
                        Seules ces méthodes sont testées lors du débogage d'une API en erreur.
                        Activez uniquement les méthodes <strong>lecture seule</strong> (sans effet de bord).
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulaires de suppression (hors du formulaire principal) --}}
    @foreach($methods as $method)
        <form id="delete-form-{{ $method->id }}"
              method="POST"
              action="{{ route('settings.api-methods.destroy', $method) }}"
              style="display:none">
            @csrf @method('DELETE')
        </form>
    @endforeach

</x-app-layout>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var list = document.getElementById('methods-list');
    var form = document.getElementById('methods-form');
    var orderInput = document.getElementById('order-json-input');

    // --- Drag & drop ---
    if (list && typeof Sortable !== 'undefined') {
        Sortable.create(list, {
            animation: 150,
            handle: '.grip-handle',
            ghostClass: 'bg-light',
            onEnd: function () {
                // Met à jour visuellement le label Oui/Non après drag (les cases restent cochées)
            }
        });
    }

    // --- Mise à jour du label Oui/Non au changement de checkbox ---
    list.addEventListener('change', function (e) {
        if (e.target.type === 'checkbox' && e.target.name === 'debug[]') {
            var span = e.target.closest('.form-check').querySelector('span');
            if (span) {
                span.textContent  = e.target.checked ? 'Oui' : 'Non';
                span.className    = 'small ' + (e.target.checked ? 'text-success' : 'text-muted');
            }
        }
    });

    // --- Sérialise l'ordre avant soumission ---
    form.addEventListener('submit', function () {
        var ids = Array.from(list.querySelectorAll('.method-row')).map(function (el) {
            return el.dataset.id;
        });
        orderInput.value = JSON.stringify(ids);
    });

    // --- Boutons de suppression ---
    document.querySelectorAll('.delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id   = this.dataset.id;
            var name = this.dataset.name;
            if (confirm('Supprimer « ' + name + ' » ?')) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    });

});
</script>
@endpush
