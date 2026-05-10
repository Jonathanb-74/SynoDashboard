<x-app-layout>
    <x-slot name="title">Débogage méthode API</x-slot>

    <div class="row g-4">
        {{-- Formulaire --}}
        <div class="col-lg-5">

            {{-- Contexte API --}}
            @if($entry)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-2 small">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-bug text-warning mt-1"></i>
                        <div>
                            <div class="fw-semibold font-monospace">{{ $entry->api_name }}</div>
                            <div class="text-muted">
                                Modèle : <a href="{{ route('api-models.show', $entry->api_model_id) }}">{{ $entry->apiModel?->name ?? '—' }}</a>
                                — chemin : <code>{{ $entry->path }}</code>
                                — version max : <code>{{ $entry->max_version }}</code>
                                — méthode actuelle : <code class="text-danger">{{ $entry->method }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Connexion NAS --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-terminal me-2 text-primary"></i>Connexion NAS
                    </h6>
                </div>
                <div class="card-body">
                    @error('connection')
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}
                        </div>
                    @enderror

                    <form method="POST" action="{{ route('debug.api-method.probe') }}">
                        @csrf
                        <input type="hidden" name="entry_id" value="{{ $entry?->id ?? old('entry_id') }}">

                        <div class="mb-3">
                            <label class="form-label fw-medium">URL du NAS</label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   placeholder="https://192.168.1.100:5001"
                                   value="{{ old('url') }}" required>
                            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Identifiant DSM</label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="admin" value="{{ old('username') }}"
                                   required autocomplete="username">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Mot de passe DSM</label>
                            <input type="password" name="password" class="form-control"
                                   placeholder="••••••••" required autocomplete="new-password">
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ssl_verify" id="ssl_verify"
                                       value="1" {{ old('ssl_verify', '0') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="ssl_verify">Vérification SSL</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-semibold">
                            <i class="bi bi-search me-2"></i>Tester toutes les méthodes sûres
                        </button>
                    </form>
                </div>
            </div>

            {{-- Légende méthodes testées --}}
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body py-2 small text-muted">
                    <p class="mb-1 fw-medium text-body">Méthodes testées (debug activé)</p>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($debugMethods as $m)
                            <span class="badge bg-light text-dark border font-monospace">{{ $m }}</span>
                        @endforeach
                    </div>
                    <p class="mt-2 mb-0">
                        <a href="{{ route('settings.api-methods.index') }}" class="text-decoration-none">
                            <i class="bi bi-sliders me-1"></i>Configurer les méthodes autorisées
                        </a>
                    </p>
                </div>
            </div>
        </div>

        {{-- Résultats --}}
        <div class="col-lg-7">
            @if(session('probe_results'))
                @php
                    $results   = session('probe_results');
                    $entryId   = session('probe_entry_id');
                    $successes = array_filter($results, fn($r) => $r['success']);
                    $failures  = array_filter($results, fn($r) => !$r['success']);
                @endphp

                {{-- Résumé --}}
                <div class="alert {{ count($successes) > 0 ? 'alert-success' : 'alert-danger' }} py-2 small mb-3">
                    @if(count($successes) > 0)
                        <i class="bi bi-check-circle me-1"></i>
                        <strong>{{ count($successes) }} méthode(s) fonctionnelle(s)</strong> trouvée(s) sur {{ count($results) }} testées.
                    @else
                        <i class="bi bi-x-circle me-1"></i>
                        <strong>Aucune méthode</strong> n'a répondu avec succès sur {{ count($results) }} testées.
                    @endif
                </div>

                {{-- Méthodes qui marchent --}}
                @if(count($successes) > 0)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-semibold text-success">
                            <i class="bi bi-check-circle me-2"></i>Méthodes fonctionnelles ({{ count($successes) }})
                        </h6>
                    </div>
                    @foreach($successes as $method => $result)
                        @php $pretty = json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); @endphp
                        <div class="border-top" x-data="{ open: false }">
                            <div class="px-3 py-2 d-flex align-items-center justify-content-between"
                                 style="background:#f0fff4">
                                <span class="font-monospace fw-semibold text-success">
                                    <i class="bi bi-check-circle me-1"></i>{{ $method }}
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0"
                                            @click="open = !open" x-text="open ? 'Masquer' : 'Voir réponse'">
                                    </button>
                                    <form method="POST" action="{{ route('debug.api-method.apply') }}">
                                        @csrf
                                        <input type="hidden" name="entry_id" value="{{ $entryId }}">
                                        <input type="hidden" name="method" value="{{ $method }}">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check2 me-1"></i>Appliquer cette méthode
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div x-show="open" x-cloak>
                                <pre class="m-0 p-3 small" style="background:#1e1e1e;color:#d4d4d4;overflow-x:auto;max-height:300px">{{ $pretty }}</pre>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif

                {{-- Méthodes en échec --}}
                @if(count($failures) > 0)
                <div class="card border-0 shadow-sm" x-data="{ open: false }">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between"
                         style="cursor:pointer" @click="open = !open">
                        <h6 class="mb-0 fw-semibold text-muted">
                            <i class="bi bi-x-circle me-2"></i>Méthodes en échec ({{ count($failures) }})
                        </h6>
                        <i class="bi text-muted" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </div>
                    <div x-show="open" x-cloak>
                        @foreach($failures as $method => $result)
                            <div class="px-3 py-2 border-top d-flex align-items-center gap-3">
                                <span class="font-monospace text-danger" style="min-width:100px">
                                    <i class="bi bi-x-circle me-1"></i>{{ $method }}
                                </span>
                                <span class="text-muted small">{{ $result['error'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            @else
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5 text-muted">
                        <i class="bi bi-search display-3 mb-3 opacity-25"></i>
                        <p class="mb-0">
                            Renseignez les informations de connexion<br>
                            et lancez le test pour identifier la bonne méthode.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
