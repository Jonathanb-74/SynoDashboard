<x-app-layout>
    <x-slot name="title">Log #{{ $apiLog->id }}</x-slot>

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('api-logs.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>

    <div class="row g-3">
        {{-- Meta --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-primary"></i>Informations</h6>
                </div>
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Date</dt>
                        <dd class="col-7 font-monospace">{{ $apiLog->created_at->format('d/m/Y H:i:s') }}</dd>

                        <dt class="col-5 text-muted fw-normal">Statut</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $apiLog->statusColor }}">
                                {{ $apiLog->status_code }} {{ $apiLog->statusLabel }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Méthode</dt>
                        <dd class="col-7 font-monospace">{{ $apiLog->http_method }}</dd>

                        <dt class="col-5 text-muted fw-normal">Chemin</dt>
                        <dd class="col-7 font-monospace">{{ $apiLog->path }}</dd>

                        <dt class="col-5 text-muted fw-normal">IP</dt>
                        <dd class="col-7 font-monospace">{{ $apiLog->ip_address ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Durée</dt>
                        <dd class="col-7">
                            @if($apiLog->duration_ms !== null)
                                <span class="{{ $apiLog->duration_ms > 1000 ? 'text-warning fw-semibold' : '' }}">
                                    {{ $apiLog->duration_ms }} ms
                                </span>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Série NAS</dt>
                        <dd class="col-7 font-monospace">{{ $apiLog->nas_serial ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">NAS</dt>
                        <dd class="col-7">
                            @if($apiLog->nas_id)
                                <a href="{{ route('nas.show', $apiLog->nas_id) }}" class="text-decoration-none">
                                    {{ $apiLog->nas?->name ?? $apiLog->nas_serial ?? '#' . $apiLog->nas_id }}
                                </a>
                            @else
                                <span class="text-muted fst-italic">—</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Payload + Response --}}
        <div class="col-lg-8">
            @if($apiLog->error)
            <div class="alert alert-danger d-flex align-items-start gap-2 mb-3 small">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                <div><strong>Erreur :</strong> {{ $apiLog->error }}</div>
            </div>
            @endif

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0 fw-semibold small">
                        <i class="bi bi-shield-lock me-2 text-secondary"></i>Signature HMAC
                    </h6>
                </div>
                <div class="card-body py-2">
                    @if($apiLog->hmac_signature)
                        @php
                            $sig     = $apiLog->hmac_signature;
                            $hasPrefix = str_starts_with($sig, 'sha256=');
                            $hexPart   = $hasPrefix ? substr($sig, 7) : $sig;
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-1">
                            @if($hasPrefix)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">sha256=</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"
                                      title="Préfixe 'sha256=' manquant">sans préfixe</span>
                            @endif
                            <code class="small text-break">{{ $hexPart }}</code>
                        </div>
                        <div class="text-muted" style="font-size:.75rem">
                            Longueur hex : {{ strlen($hexPart) }} caractères
                            @if(strlen($hexPart) !== 64)
                                <span class="text-danger fw-semibold ms-1">
                                    <i class="bi bi-exclamation-triangle"></i> attendu 64 (SHA-256)
                                </span>
                            @else
                                <span class="text-success ms-1"><i class="bi bi-check-circle"></i> valide</span>
                            @endif
                        </div>
                    @else
                        <span class="text-muted small fst-italic">
                            <i class="bi bi-dash-circle me-1"></i>Aucun header <code>X-Agent-Signature</code> reçu
                        </span>
                    @endif
                </div>
            </div>

            {{-- Payload --}}
            <div class="card border-0 shadow-sm mb-3" x-data="{ open: true }">
                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between"
                     style="cursor:pointer" @click="open = !open">
                    <h6 class="mb-0 fw-semibold small">
                        <i class="bi bi-upload me-2 text-secondary"></i>Payload (requête)
                    </h6>
                    <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div x-show="open">
                    @if($apiLog->payload)
                        @php
                            $decoded = json_decode($apiLog->payload, true);
                            $pretty  = $decoded !== null
                                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $apiLog->payload;
                        @endphp
                        <div class="card-body p-0">
                            <pre class="mb-0 p-3 small font-monospace" style="background:#1e1e1e;color:#d4d4d4;border-radius:0 0 .375rem .375rem;max-height:400px;overflow:auto;white-space:pre-wrap;word-break:break-all">{{ $pretty }}</pre>
                        </div>
                    @else
                        <div class="card-body text-muted small fst-italic">Aucun payload.</div>
                    @endif
                </div>
            </div>

            {{-- Response --}}
            <div class="card border-0 shadow-sm" x-data="{ open: true }">
                <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between"
                     style="cursor:pointer" @click="open = !open">
                    <h6 class="mb-0 fw-semibold small">
                        <i class="bi bi-download me-2 text-secondary"></i>Réponse serveur
                    </h6>
                    <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div x-show="open">
                    @if($apiLog->response)
                        @php
                            $decodedResp = json_decode($apiLog->response, true);
                            $prettyResp  = $decodedResp !== null
                                ? json_encode($decodedResp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : $apiLog->response;
                        @endphp
                        <div class="card-body p-0">
                            <pre class="mb-0 p-3 small font-monospace" style="background:#1e1e1e;color:#d4d4d4;border-radius:0 0 .375rem .375rem;max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all">{{ $prettyResp }}</pre>
                        </div>
                    @else
                        <div class="card-body text-muted small fst-italic">Aucune réponse enregistrée.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
