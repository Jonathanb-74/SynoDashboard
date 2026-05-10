<x-app-layout>
    <x-slot name="title">Test Console NAS</x-slot>

    <div class="row g-4">
        {{-- Formulaire --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-terminal me-2 text-primary"></i>Interroger un NAS Synology
                    </h6>
                </div>
                <div class="card-body">
                    @if($errors->has('api'))
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('api') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('test.run') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium">URL du NAS</label>
                            <input type="url" name="url" class="form-control @error('url') is-invalid @enderror"
                                   placeholder="https://192.168.1.100:5001"
                                   value="{{ old('url') }}" required>
                            <div class="form-text">Inclure le protocole et le port DSM (5000 ou 5001).</div>
                            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Identifiant DSM</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                                   placeholder="admin"
                                   value="{{ old('username') }}" required autocomplete="username">
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Mot de passe DSM</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••" required autocomplete="new-password">
                            <div class="form-text text-muted">
                                <i class="bi bi-shield-check me-1"></i>Non stocké — utilisé uniquement pour cette requête.
                            </div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ssl_verify" id="ssl_verify"
                                       value="1" {{ old('ssl_verify', '0') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="ssl_verify">
                                    Vérification SSL
                                </label>
                            </div>
                            <div class="form-text text-muted">Désactivez si le NAS utilise un certificat auto-signé.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-play-circle me-2"></i>Lancer l'interrogation
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">Comportement</h6>
                    <ul class="mb-0 small text-muted">
                        <li>Récupère <code>SYNO.Core.System</code> et <code>SYNO.Core.Network</code> pour identifier le NAS.</li>
                        <li>Si le NAS a un modèle API lié, appelle toutes ses entrées actives.</li>
                        <li>Sinon, repli sur les {{ count(config('synology.standard_apis')) }} APIs standard.</li>
                        <li>Enregistre le résultat en base (snapshot).</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Résultats --}}
        <div class="col-lg-7">
            @if(session('test_result'))
                @php
                    $result     = session('test_result');
                    $nas        = $result['nas'];
                    $snapshot   = $result['snapshot'];
                    $isNew      = $result['is_new'];
                    $payload    = session('test_payload');
                    $testModel  = session('test_model');
                    $errorCount = session('test_error_count', 0);
                    $debugInfo  = session('test_debug', []);
                    $apiCount   = count($payload['responses'] ?? []);
                @endphp

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-check-circle text-success me-2"></i>Collecte réussie
                        </h6>
                        @if($isNew)
                            <span class="badge bg-info text-white">Nouveau NAS détecté</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Nom</dt>
                            <dd class="col-sm-8">{{ $nas->name }}</dd>

                            <dt class="col-sm-4">Modèle</dt>
                            <dd class="col-sm-8">{{ $nas->model ?? '—' }}</dd>

                            <dt class="col-sm-4">N° Série</dt>
                            <dd class="col-sm-8 font-monospace">{{ $nas->serial }}</dd>

                            <dt class="col-sm-4">Version DSM</dt>
                            <dd class="col-sm-8">{{ $nas->dsm_version ?? '—' }}</dd>

                            <dt class="col-sm-4">Statut NAS</dt>
                            <dd class="col-sm-8">
                                @include('components.status-badge', ['status' => $nas->status])
                                @if($isNew)
                                    — <a href="{{ route('nas.pending') }}">Approuver</a>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Modèle API</dt>
                            <dd class="col-sm-8">
                                @if($testModel)
                                    <a href="{{ route('api-models.show', $testModel) }}" class="text-decoration-none">
                                        <i class="bi bi-diagram-3 me-1 text-primary"></i>{{ $testModel->name }}
                                    </a>
                                    <span class="text-muted small ms-2">{{ $apiCount }} appels</span>
                                    @if($errorCount > 0)
                                        <span class="badge bg-warning text-dark ms-1">{{ $errorCount }} erreur(s)</span>
                                    @endif
                                @else
                                    <span class="text-muted">APIs standard</span>
                                    <span class="text-muted small ms-2">(aucun modèle lié au NAS)</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">Snapshot</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('snapshots.show', $snapshot) }}">#{{ $snapshot->id }}</a>
                                <span class="text-muted small ms-2">{{ $snapshot->collected_at->format('d/m/Y H:i:s') }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>

                {{-- Bloc debug --}}
                @if(!empty($debugInfo))
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-2">
                        <span class="fw-semibold small"><i class="bi bi-bug me-1 text-muted"></i>Diagnostic collecte</span>
                    </div>
                    <div class="card-body py-2 small">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted w-50">Série détectée</td>
                                <td class="font-monospace">{{ $debugInfo['serial'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">NAS trouvé en base</td>
                                <td>
                                    @if($debugInfo['nas_found'] ?? false)
                                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>Oui</span>
                                    @else
                                        <span class="text-danger"><i class="bi bi-x-circle me-1"></i>Non — nouveau NAS</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">api_model_id</td>
                                <td class="font-monospace">{{ $debugInfo['api_model_id'] ?? 'NULL' }}</td>
                            </tr>
                            @if(isset($debugInfo['model_name']))
                            <tr>
                                <td class="text-muted">Modèle API</td>
                                <td>{{ $debugInfo['model_name'] }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Entrées actives / total</td>
                                <td>
                                    <span class="{{ ($debugInfo['entries_active'] ?? 0) > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $debugInfo['entries_active'] ?? 0 }}
                                    </span>
                                    / {{ $debugInfo['entries_total'] ?? 0 }}
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Mode utilisé</td>
                                <td>
                                    @if($debugInfo['fallback'] ?? false)
                                        <span class="badge bg-warning text-dark">Fallback APIs standard</span>
                                    @else
                                        <span class="badge bg-success">Modèle API</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Appels effectués / erreurs</td>
                                <td>{{ $apiCount }} appels — <span class="{{ $errorCount > 0 ? 'text-danger' : 'text-success' }}">{{ $errorCount }} erreur(s)</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif

                <div class="card border-0 shadow-sm" x-data="{ open: false }">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between"
                         style="cursor:pointer" @click="open = !open">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-code-slash me-2"></i>Payload JSON enregistré
                        </h6>
                        <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </div>
                    <div x-show="open" x-cloak>
                        <div class="card-body p-0">
                            <pre class="json-viewer m-0">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>

            @elseif(!$errors->has('api'))
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5 text-muted">
                        <i class="bi bi-terminal display-3 mb-3 opacity-25"></i>
                        <p class="mb-0">Renseignez les informations de connexion<br>et lancez l'interrogation pour voir les résultats.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
