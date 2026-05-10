<x-app-layout>
    <x-slot name="title">Snapshot #{{ $snapshot->id }}</x-slot>

    @php
        $raw        = $snapshot->getRawData();
        $responses  = $raw['responses']      ?? [];
        $nasId      = $raw['nas_identifier'] ?? [];
        $apiList    = $raw['api_list']       ?? [];
    @endphp

    <div class="mb-3 d-flex align-items-center gap-2">
        <a href="{{ route('nas.show', $snapshot->nas) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ $snapshot->nas->name }}
        </a>
        <a href="{{ route('snapshots.raw', $snapshot) }}" class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="bi bi-download me-1"></i>JSON brut
        </a>
    </div>

    {{-- Métadonnées --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <dl class="row mb-0 small">
                <dt class="col-sm-2">NAS</dt>
                <dd class="col-sm-10">{{ $snapshot->nas->name }} <span class="text-muted font-monospace">({{ $snapshot->nas->serial }})</span></dd>

                <dt class="col-sm-2">Collecté le</dt>
                <dd class="col-sm-10">{{ $snapshot->collected_at->format('d/m/Y H:i:s') }}</dd>

                <dt class="col-sm-2">Agent</dt>
                <dd class="col-sm-10">{{ $snapshot->agent_version ?? '—' }}</dd>

                <dt class="col-sm-2">Taille JSON</dt>
                <dd class="col-sm-10">{{ number_format(strlen($snapshot->raw_json) / 1024, 1) }} Ko
                    — {{ count($responses) }} réponse(s) API — {{ count($apiList) }} APIs disponibles
                </dd>
            </dl>
        </div>
    </div>

    {{-- Réponses API --}}
    <div class="card border-0 shadow-sm mb-3" x-data="{}">
        <div class="card-header bg-white">
            <h6 class="mb-0 fw-semibold">
                <i class="bi bi-cloud-download me-2 text-primary"></i>
                Réponses API ({{ count($responses) }})
            </h6>
        </div>

        @if(empty($responses))
            <div class="card-body text-muted small text-center py-3">Aucune réponse enregistrée.</div>
        @else
            @foreach($responses as $apiName => $data)
                @php
                    $hasError = isset($data['_error']);
                    $pretty   = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                @endphp
                <div class="border-top" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                    <div class="px-3 py-2 d-flex align-items-center justify-content-between"
                         style="cursor:pointer; background:#fafafa" @click.self="open = !open">
                        <span class="font-monospace small fw-medium {{ $hasError ? 'text-danger' : 'text-success' }}"
                              @click="open = !open" style="flex:1">
                            @if($hasError)
                                <i class="bi bi-x-circle me-1"></i>
                            @else
                                <i class="bi bi-check-circle me-1"></i>
                            @endif
                            {{ $apiName }}
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            @if($hasError && isset($entryMap[$apiName]))
                                <a href="{{ route('debug.api-method.index', ['entry_id' => $entryMap[$apiName]]) }}"
                                   class="btn btn-sm btn-outline-warning py-0 px-2"
                                   title="Trouver la bonne méthode"
                                   @click.stop>
                                    <i class="bi bi-bug me-1"></i><span class="small">Déboguer</span>
                                </a>
                            @endif
                            <i class="bi text-muted small" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                               @click="open = !open"></i>
                        </div>
                    </div>
                    <div x-show="open" x-cloak>
                        <pre class="m-0 p-3 small" style="background:#1e1e1e;color:#d4d4d4;overflow-x:auto;max-height:400px">{{ $pretty }}</pre>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- api_list collapsible --}}
    <div class="card border-0 shadow-sm mb-3" x-data="{ open: false }">
        <div class="card-header bg-white d-flex align-items-center justify-content-between"
             style="cursor:pointer" @click="open = !open">
            <h6 class="mb-0 fw-semibold text-muted">
                <i class="bi bi-list-ul me-2"></i>APIs disponibles sur le NAS ({{ count($apiList) }})
            </h6>
            <i class="bi text-muted" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </div>
        <div x-show="open" x-cloak>
            <pre class="m-0 p-3 small" style="background:#1e1e1e;color:#d4d4d4;overflow-x:auto;max-height:400px">{{ json_encode($apiList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    {{-- Requêtes HTTP reconstruites --}}
    @if(!empty($responses) && !empty($entriesByApi))
    @php $nasUrl = rtrim($snapshot->nas->url ?? '', '/'); @endphp
    <div class="card border-0 shadow-sm" x-data="{ open: false, sid: '', nasUrl: '{{ $nasUrl }}' }">
        <div class="card-header bg-white d-flex align-items-center justify-content-between"
             style="cursor:pointer" @click="open = !open">
            <h6 class="mb-0 fw-semibold text-muted">
                <i class="bi bi-terminal me-2"></i>Requêtes HTTP envoyées
            </h6>
            <i class="bi text-muted" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </div>
        <div x-show="open" x-cloak>
            {{-- Champ SID --}}
            <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center gap-2">
                <label class="form-label small mb-0 text-muted fw-medium text-nowrap">Session SID :</label>
                <input type="text" x-model="sid"
                       class="form-control form-control-sm font-monospace"
                       style="max-width:420px"
                       placeholder="Collez votre _sid DSM pour activer les liens">
                <span class="small text-muted">
                    Dans DSM → F12 → Réseau → n'importe quelle requête → param <code>_sid</code>
                </span>
            </div>

            @foreach($responses as $apiName => $data)
                @php
                    $entry   = $entriesByApi[$apiName] ?? null;
                    $apiInfo = $apiList[$apiName] ?? null;
                @endphp
                @if($entry)
                @php
                    $path    = $apiInfo['path'] ?? 'entry.cgi';
                    $maxVer  = min((int)($apiInfo['maxVersion'] ?? 1), 99);
                    $version = $entry->version ?? $maxVer;
                    $method  = $entry->method ?? 'get';

                    $params = array_merge(
                        ['api' => $apiName, 'version' => $version, 'method' => $method],
                        $entry->parameters ?? []
                    );

                    $queryParts = [];
                    foreach ($params as $k => $v) {
                        $encoded      = is_array($v)
                            ? json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : $v;
                        $queryParts[] = $k . '=' . urlencode((string) $encoded);
                    }
                    $queryString = implode('&', $queryParts);
                    $hasError    = isset($data['_error']);
                    $fullBase    = $nasUrl . '/webapi/' . $path . '?' . $queryString . '&_sid=';
                @endphp
                <div class="border-top px-3 py-2">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge {{ $hasError ? 'bg-danger' : 'bg-success' }}" style="font-size:.65rem">
                            {{ $hasError ? 'ERR' : 'OK' }}
                        </span>
                        <code class="small text-muted">{{ $apiName }}</code>
                        <a :href="nasUrl ? nasUrl + '/webapi/{{ $path }}?{{ $queryString }}&_sid=' + sid : '#'"
                           :class="(sid && nasUrl) ? 'btn btn-sm btn-outline-primary py-0 px-2' : 'btn btn-sm btn-outline-secondary py-0 px-2 disabled'"
                           target="_blank" class="ms-auto" title="Ouvrir dans le navigateur">
                            <i class="bi bi-box-arrow-up-right small"></i>
                        </a>
                    </div>
                    <pre class="m-0 p-2 rounded small font-monospace"
                         style="background:#f8f9fa;color:#212529;overflow-x:auto;white-space:pre-wrap;word-break:break-all"
                    >{{ $queryString }}&_sid=<span x-text="sid || '___'"></span></pre>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

</x-app-layout>
