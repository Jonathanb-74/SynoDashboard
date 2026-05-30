<x-app-layout>
    <x-slot name="title">Configuration SMTP</x-slot>

    @if($errors->has('smtp_test'))
        <div class="alert alert-danger">{{ $errors->first('smtp_test') }}</div>
    @endif

    <div class="row g-4">

        {{-- ─── SMTP form ───────────────────────────────────────────────── --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="bi bi-envelope-at me-2 text-primary"></i>Serveur SMTP
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.smtp.update') }}">
                        @csrf

                        @php
                            $v = fn(string $key, string $default = '') => old($key, $settings[$key] ?? $default);
                        @endphp

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-medium">Hôte SMTP</label>
                                <input type="text" name="mail[mailers][smtp][host]"
                                       class="form-control form-control-sm @error('mail.mailers.smtp.host') is-invalid @enderror"
                                       value="{{ $v('mail.mailers.smtp.host') }}"
                                       placeholder="smtp.gmail.com">
                                @error('mail.mailers.smtp.host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Port</label>
                                <input type="number" name="mail[mailers][smtp][port]"
                                       class="form-control form-control-sm @error('mail.mailers.smtp.port') is-invalid @enderror"
                                       value="{{ $v('mail.mailers.smtp.port', '587') }}"
                                       placeholder="587">
                                @error('mail.mailers.smtp.port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Utilisateur</label>
                                <input type="text" name="mail[mailers][smtp][username]"
                                       class="form-control form-control-sm"
                                       value="{{ $v('mail.mailers.smtp.username') }}"
                                       placeholder="user@example.com"
                                       autocomplete="username">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Mot de passe</label>
                                <input type="password" name="mail[mailers][smtp][password]"
                                       class="form-control form-control-sm"
                                       value="{{ $v('mail.mailers.smtp.password') }}"
                                       autocomplete="new-password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Chiffrement</label>
                                <select name="mail[mailers][smtp][encryption]" class="form-select form-select-sm">
                                    @php $enc = $v('mail.mailers.smtp.encryption', 'tls') @endphp
                                    <option value="tls"      @selected($enc === 'tls')>TLS (STARTTLS) — port 587</option>
                                    <option value="ssl"      @selected($enc === 'ssl')>SSL — port 465</option>
                                    <option value=""         @selected($enc === '')>Aucun — port 25</option>
                                </select>
                            </div>
                            <div class="col-md-8"></div>

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Adresse expéditeur</label>
                                <input type="email" name="mail[from][address]"
                                       class="form-control form-control-sm @error('mail.from.address') is-invalid @enderror"
                                       value="{{ $v('mail.from.address') }}"
                                       placeholder="noreply@mondomaine.fr">
                                @error('mail.from.address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Nom expéditeur</label>
                                <input type="text" name="mail[from][name]"
                                       class="form-control form-control-sm"
                                       value="{{ $v('mail.from.name', 'SynoManager') }}"
                                       placeholder="SynoManager">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save me-1"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ─── Test + Info ─────────────────────────────────────────────── --}}
        <div class="col-lg-5 d-flex flex-column gap-3">

            {{-- Test send --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="bi bi-send me-2 text-success"></i>Envoyer un email de test
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Vérifie la connexion en envoyant un email depuis la configuration ci-contre.
                    </p>
                    <form method="POST" action="{{ route('settings.smtp.test') }}">
                        @csrf
                        <div class="input-group input-group-sm">
                            <input type="email" name="to" class="form-control"
                                   value="{{ auth()->user()->email }}"
                                   placeholder="destinataire@example.com" required>
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-send me-1"></i>Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Info box --}}
            <div class="card border-0 bg-light">
                <div class="card-body py-3 small text-muted">
                    <p class="fw-medium text-body mb-2"><i class="bi bi-info-circle me-1"></i>À quoi sert cette config ?</p>
                    <ul class="mb-0 ps-3">
                        <li>Réinitialisation des mots de passe</li>
                        <li>Alertes NAS (hors ligne, erreur de collecte…)</li>
                        <li>Notifications futures</li>
                    </ul>
                    <p class="mt-2 mb-0">
                        Les paramètres sont stockés en base de données et remplacent
                        le fichier <code>.env</code> au démarrage.
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
