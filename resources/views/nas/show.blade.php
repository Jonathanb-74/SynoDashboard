<x-app-layout>
    <x-slot name="title">{{ $nas->name }}</x-slot>

    @php
        $apiIcons = [
            'SYNO.Core.System'           => 'bi-cpu',
            'SYNO.Core.Network'          => 'bi-wifi',
            'SYNO.Storage.CGI.Storage'   => 'bi-hdd-stack',
            'SYNO.Core.Package'          => 'bi-box-seam',
            'SYNO.Core.Upgrade'          => 'bi-arrow-up-circle',
            'SYNO.Core.Hardware'         => 'bi-motherboard',
            'SYNO.Core.Service'          => 'bi-gear',
            'SYNO.Core.Security'         => 'bi-shield-check',
            'SYNO.Core.User'             => 'bi-people',
            'SYNO.FileStation'           => 'bi-folder2',
            'SYNO.DownloadStation'       => 'bi-cloud-arrow-down',
            'SYNO.SurveillanceStation'   => 'bi-camera-video',
        ];
        $icon = fn($name) => $apiIcons[$name] ?? 'bi-code-square';
    @endphp

    <div class="row g-4">

        {{-- ─── Colonne gauche ─────────────────────────────── --}}
        <div class="col-xl-3 col-lg-4">

            {{-- Fiche NAS --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold">Informations NAS</h6>
                    @include('components.status-badge', ['status' => $nas->status])
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 small">
                        <tbody>
                            <tr><td class="text-muted w-50">Nom</td><td class="fw-medium">{{ $nas->name }}</td></tr>
                            <tr><td class="text-muted">Modèle</td><td>{{ $nas->model ?? '—' }}</td></tr>
                            <tr><td class="text-muted">N° Série</td><td class="font-monospace small">{{ $nas->serial }}</td></tr>
                            <tr><td class="text-muted">Version DSM</td><td>{{ $nas->dsm_version ?? '—' }}</td></tr>
                            <tr x-data="{ editing: false }">
                                <td class="text-muted">Modèle API</td>
                                <td>
                                    <div x-show="!editing" class="d-flex align-items-center gap-1">
                                        @if($nas->apiModel)
                                            <a href="{{ route('api-models.show', $nas->apiModel) }}" class="text-decoration-none">{{ $nas->apiModel->name }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        <button type="button" @click="editing = true" class="btn btn-link p-0 ms-1 text-muted" title="Modifier"><i class="bi bi-pencil" style="font-size:.7rem"></i></button>
                                    </div>
                                    <form x-show="editing" style="display:none" method="POST" action="{{ route('nas.update', $nas) }}">
                                        @csrf @method('PATCH')
                                        <div class="d-flex align-items-center gap-1">
                                            <select name="api_model_id" class="form-select form-select-sm py-0" style="font-size:.8rem">
                                                <option value="">— aucun —</option>
                                                @foreach($allApiModels as $m)
                                                    <option value="{{ $m->id }}" @selected($nas->api_model_id == $m->id)>{{ $m->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm p-1 lh-1"><i class="bi bi-check"></i></button>
                                            <button type="button" @click="editing = false" class="btn btn-outline-secondary btn-sm p-1 lh-1"><i class="bi bi-x"></i></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr x-data="{ editing: false }">
                                <td class="text-muted">Décodeur</td>
                                <td>
                                    <div x-show="!editing" class="d-flex align-items-center gap-1">
                                        @if($nas->decoderModel)
                                            <a href="{{ route('decoder-models.edit', $nas->decoderModel) }}" class="text-decoration-none">{{ $nas->decoderModel->name }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        <button type="button" @click="editing = true" class="btn btn-link p-0 ms-1 text-muted" title="Modifier"><i class="bi bi-pencil" style="font-size:.7rem"></i></button>
                                    </div>
                                    <form x-show="editing" style="display:none" method="POST" action="{{ route('nas.update', $nas) }}">
                                        @csrf @method('PATCH')
                                        <div class="d-flex align-items-center gap-1">
                                            <select name="decoder_model_id" class="form-select form-select-sm py-0" style="font-size:.8rem">
                                                <option value="">— aucun —</option>
                                                @foreach($allDecoderModels as $m)
                                                    <option value="{{ $m->id }}" @selected($nas->decoder_model_id == $m->id)>{{ $m->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm p-1 lh-1"><i class="bi bi-check"></i></button>
                                            <button type="button" @click="editing = false" class="btn btn-outline-secondary btn-sm p-1 lh-1"><i class="bi bi-x"></i></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr x-data="{ editing: false }">
                                <td class="text-muted">Fréquence</td>
                                <td>
                                    <div x-show="!editing" class="d-flex align-items-center gap-1">
                                        <span>{{ $nas->collection_frequency }} min</span>
                                        <button type="button" @click="editing = true" class="btn btn-link p-0 ms-1 text-muted" title="Modifier"><i class="bi bi-pencil" style="font-size:.7rem"></i></button>
                                    </div>
                                    <form x-show="editing" style="display:none" method="POST" action="{{ route('nas.update', $nas) }}">
                                        @csrf @method('PATCH')
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="number" name="collection_frequency" class="form-control form-control-sm py-0" style="font-size:.8rem;width:80px"
                                                   value="{{ $nas->collection_frequency }}" min="1" max="10080">
                                            <button type="submit" class="btn btn-success btn-sm p-1 lh-1"><i class="bi bi-check"></i></button>
                                            <button type="button" @click="editing = false" class="btn btn-outline-secondary btn-sm p-1 lh-1"><i class="bi bi-x"></i></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dernier contact</td>
                                <td title="{{ $nas->last_contact_at?->format('d/m/Y H:i:s') }}">
                                    {{ $nas->last_contact_at?->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                            <tr><td class="text-muted">Snapshots</td><td>{{ $nas->snapshots_count }}</td></tr>
                            @if($nas->approvedBy)
                                <tr>
                                    <td class="text-muted">Approuvé par</td>
                                    <td>{{ $nas->approvedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Approuvé le</td>
                                    <td>{{ $nas->approved_at?->format('d/m/Y') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Approbation en attente --}}
            @if($nas->status === 'pending')
                <div class="card border-0 shadow-sm bg-warning bg-opacity-10 mb-3">
                    <div class="card-body py-2">
                        <p class="small fw-semibold text-warning-emphasis mb-2">
                            <i class="bi bi-clock-history me-1"></i>En attente d'approbation
                        </p>
                        <a href="{{ route('nas.pending') }}" class="btn btn-warning btn-sm w-100">
                            Gérer l'approbation
                        </a>
                    </div>
                </div>
            @endif

            {{-- Clé HMAC --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-key text-primary"></i>
                    <h6 class="mb-0 fw-semibold small flex-grow-1">Authentification HMAC</h6>
                    @if($nas->hmac_secret)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Non configurée</span>
                    @endif
                </div>
                <div class="card-body py-2 px-3">
                    @if(session('hmac_generated'))
                        <div class="alert alert-success py-1 px-2 small mb-2">
                            <i class="bi bi-check-circle me-1"></i>Clé générée — copiez-la maintenant.
                        </div>
                    @endif

                    @if($nas->hmac_secret)
                        <p class="small text-muted mb-1">Variable <code>SYNOMANAGER_SECRET</code> à configurer sur l'agent :</p>
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" id="hmac-key-input" class="form-control font-monospace"
                                   value="{{ $nas->hmac_secret }}" readonly>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="navigator.clipboard.writeText(document.getElementById('hmac-key-input').value).then(()=>{ this.innerHTML='<i class=\'bi bi-check\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i>',1500) })">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    @else
                        <p class="small text-muted mb-2">Aucune clé configurée. La clé est générée automatiquement à l'approbation du NAS.</p>
                    @endif

                    <form method="POST" action="{{ route('nas.regenerate-hmac', $nas) }}"
                          onsubmit="return confirm('Régénérer la clé HMAC ? L\'ancienne clé sera immédiatement invalidée et l\'agent ne pourra plus envoyer de données tant qu\'il n\'aura pas été mis à jour.')">
                        @csrf
                        <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                            <i class="bi bi-arrow-repeat me-1"></i>
                            {{ $nas->hmac_secret ? 'Régénérer la clé' : 'Générer une clé' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- APIs disponibles (repliable) --}}
            <div class="card border-0 shadow-sm mb-3" x-data="{ open: false }">
                <div class="card-header bg-white d-flex align-items-center justify-content-between"
                     style="cursor:pointer" @click="open = !open">
                    <h6 class="mb-0 fw-semibold small">
                        <i class="bi bi-list-ul me-2 text-muted"></i>
                        APIs disponibles ({{ $nas->availableApis->count() }})
                    </h6>
                    <i class="bi text-muted small" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div x-show="open" x-cloak>
                    <div style="max-height:300px;overflow-y:auto">
                        <ul class="list-group list-group-flush" style="font-size:.75rem">
                            @forelse($nas->availableApis->sortBy('api_name') as $api)
                                <li class="list-group-item px-3 py-1 d-flex justify-content-between">
                                    <span>{{ $api->api_name }}</span>
                                    <span class="text-muted">v{{ $api->min_version }}–{{ $api->max_version }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted small">Aucune API enregistrée.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Historique des collectes (repliable) --}}
            <div class="card border-0 shadow-sm" x-data="{ open: false }">
                <div class="card-header bg-white d-flex align-items-center justify-content-between"
                     style="cursor:pointer" @click="open = !open">
                    <h6 class="mb-0 fw-semibold small">
                        <i class="bi bi-clock-history me-2 text-muted"></i>
                        Historique des collectes
                        <span class="text-muted fw-normal ms-1">({{ $nas->snapshots_count }})</span>
                    </h6>
                    <i class="bi text-muted small" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </div>
                <div x-show="open" x-cloak>
                    @if($snapshots->isEmpty())
                        <div class="text-center py-3 text-muted small">Aucun snapshot enregistré.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle" style="font-size:.82rem">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-muted fw-medium">#</th>
                                        <th class="text-muted fw-medium">Collecté le</th>
                                        <th class="text-muted fw-medium">Ko</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($snapshots as $snap)
                                        <tr {{ $decodedSnapshot && $snap->id === $decodedSnapshot->id ? 'class=table-primary' : '' }}>
                                            <td class="text-muted">{{ $snap->id }}</td>
                                            <td>
                                                {{ $snap->collected_at->format('d/m/Y H:i') }}
                                                @if($decodedSnapshot && $snap->id === $decodedSnapshot->id)
                                                    <span class="badge bg-primary ms-1" style="font-size:.6rem">affiché</span>
                                                @endif
                                            </td>
                                            <td class="text-muted font-monospace" style="font-size:.75rem">
                                                {{ number_format(strlen($snap->raw_json) / 1024, 1) }}
                                            </td>
                                            <td class="text-end text-nowrap">
                                                @if($nas->decoderModel && $snap->hasResponses() && !($decodedSnapshot && $snap->id === $decodedSnapshot->id))
                                                    <a href="{{ route('nas.show', [$nas, 'snapshot' => $snap->id]) }}"
                                                       class="btn btn-sm btn-outline-primary py-0 px-2"
                                                       title="Afficher ce snapshot">
                                                        <i class="bi bi-display small"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('snapshots.show', $snap) }}"
                                                   class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                   title="Voir le snapshot">
                                                    <i class="bi bi-eye small"></i>
                                                </a>
                                                <a href="{{ route('snapshots.raw', $snap) }}"
                                                   class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                   target="_blank" title="JSON brut">
                                                    <i class="bi bi-download small"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ─── Colonne droite ──────────────────────────────── --}}
        <div class="col-xl-9 col-lg-8">

            @if($decodedData !== null)

                @php
                    /**
                     * Render a single value cell from the decoded output.
                     * Handles scalars, badge_map and color_if transformer outputs.
                     */
                    $renderValue = function(mixed $value) use (&$renderValue): string {
                        if ($value === null) {
                            return '<span class="text-muted">—</span>';
                        }
                        if (is_bool($value)) {
                            $cls = $value ? 'bg-success' : 'bg-secondary';
                            $lbl = $value ? 'Oui' : 'Non';
                            return "<span class=\"badge {$cls}\">{$lbl}</span>";
                        }
                        if (is_array($value)) {
                            $type = $value['type'] ?? null;
                            if ($type === 'badge') {
                                $lbl = e($value['label'] ?? '');
                                $col = e($value['color'] ?? 'secondary');
                                return "<span class=\"badge bg-{$col}\">{$lbl}</span>";
                            }
                            if ($type === 'colored') {
                                $lbl = e($value['label'] ?? '');
                                $col = e($value['color'] ?? 'secondary');
                                return "<span class=\"text-{$col} fw-medium\">{$lbl}</span>";
                            }
                            // Nested loop (sub-table cell)
                            if ($type === 'loop') {
                                $subCols = $value['sub_cols'] ?? [];
                                $subRows = $value['rows'] ?? [];
                                if (empty($subRows)) return '<span class="text-muted">—</span>';
                                $th = implode('', array_map(
                                    fn($c) => '<th class="fw-medium small sortable-th" style="cursor:pointer;user-select:none;white-space:nowrap">'.e($c).'<span class="sort-icon text-muted ms-1" style="font-size:.6rem">↕</span></th>',
                                    $subCols
                                ));
                                $tbody = '';
                                foreach ($subRows as $subRow) {
                                    $tds = implode('', array_map(fn($c) => '<td>'.$renderValue($c['value'] ?? null).'</td>', $subRow));
                                    $tbody .= "<tr>{$tds}</tr>";
                                }
                                return "<table class=\"table table-sm mb-0 table-sub-sort\" style=\"font-size:.75rem\"><thead><tr>{$th}</tr></thead><tbody>{$tbody}</tbody></table>";
                            }
                            return '<span class="text-muted font-monospace small">'.e(json_encode($value, JSON_UNESCAPED_UNICODE)).'</span>';
                        }
                        $str = (string) $value;
                        return $str !== '' ? e($str) : '<span class="text-muted fst-italic small">vide</span>';
                    };
                @endphp

                {{-- Bandeau source --}}
                <div class="d-flex align-items-center gap-2 mb-3 small">
                    <i class="bi bi-clock-history text-muted"></i>
                    <span class="text-muted">
                        Snapshot
                        <a href="{{ route('snapshots.show', $decodedSnapshot) }}" class="fw-medium text-decoration-none">
                            #{{ $decodedSnapshot->id }}
                        </a>
                        — {{ $decodedSnapshot->collected_at->format('d/m/Y à H:i') }}
                        ({{ $decodedSnapshot->collected_at->diffForHumans() }})
                    </span>
                    @if($decodedSnapshot->decoded_cache !== null)
                        <span class="badge bg-success">
                            <i class="bi bi-lightning-charge me-1"></i>cache
                        </span>
                    @endif
                    <form method="POST" action="{{ route('nas.redecode', $nas) }}" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                title="Forcer le recalcul depuis ce snapshot">
                            <i class="bi bi-arrow-clockwise me-1"></i>Recalculer
                        </button>
                    </form>
                </div>

                @if(empty($decodedData))
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body text-center text-muted py-4">
                            <i class="bi bi-code-square display-5 d-block mb-2 opacity-25"></i>
                            Le décodeur ne contient aucun bloc configuré.
                            <a href="{{ route('decoder-models.edit', $nas->decoderModel) }}" class="d-block mt-2">
                                Configurer le décodeur
                            </a>
                        </div>
                    </div>
                @endif

                @foreach($decodedData as $block)

                    @php
                        $simpleElements = array_filter($block['elements'], fn($e) => $e['type'] === 'simple');
                        $loopElements   = array_filter($block['elements'], fn($e) => $e['type'] === 'loop');
                    @endphp

                    {{-- Block card --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
                            <i class="bi {{ $block['icon'] ?? 'bi-box' }} text-primary"></i>
                            <span class="fw-semibold">{{ $block['title'] }}</span>
                            @if($block['description'])
                                <span class="text-muted small ms-1">{{ $block['description'] }}</span>
                            @endif
                        </div>
                        <div class="card-body p-0">

                            {{-- Simple value elements --}}
                            @if(!empty($simpleElements))
                                <div class="px-3 py-2">
                                    <dl class="row mb-0" style="font-size:.85rem">
                                        @foreach($simpleElements as $el)
                                            <dt class="col-sm-4 col-md-3 text-muted fw-normal text-truncate"
                                                title="{{ $el['label'] }}">
                                                {{ $el['label'] }}
                                            </dt>
                                            <dd class="col-sm-8 col-md-9 fw-medium mb-1">
                                                {!! $renderValue($el['value']) !!}
                                            </dd>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif

                            {{-- Loop elements (tables) --}}
                            @foreach($loopElements as $el)
                                @if(!empty($el['rows']))
                                    @if(!empty($simpleElements))
                                        <hr class="my-0">
                                    @endif
                                    <div class="px-3 py-2 d-flex align-items-center gap-2 flex-wrap">
                                        <i class="bi bi-table text-primary small"></i>
                                        <span class="fw-semibold small">{{ $el['label'] }}</span>
                                        <span class="badge bg-secondary ms-1 row-count-badge" style="font-size:.7rem">{{ count($el['rows']) }}</span>
                                        <div class="ms-auto">
                                            <input type="search" class="form-control form-control-sm table-search-input"
                                                   placeholder="Rechercher…" style="width:160px;font-size:.8rem"
                                                   aria-label="Rechercher dans {{ $el['label'] }}">
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0 sortable-table" style="font-size:.82rem">
                                            <thead class="table-light">
                                                <tr>
                                                    @foreach($el['columns'] as $col)
                                                        <th class="fw-medium sortable-th"
                                                            style="cursor:pointer;user-select:none;white-space:nowrap">
                                                            {{ $col['label'] }}
                                                            <span class="sort-icon text-muted ms-1" style="font-size:.65rem">↕</span>
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($el['rows'] as $row)
                                                    <tr>
                                                        @foreach($row as $cell)
                                                            <td>{!! $renderValue($cell['value'] ?? null) !!}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @endforeach

                        </div>
                    </div>

                @endforeach

            @else

                {{-- Pas de données décodées --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-code-square display-4 d-block mb-3 opacity-20"></i>
                        @if(!$nas->decoderModel)
                            <p class="mb-2">Aucun décodeur JSON rattaché à ce NAS.</p>
                            <a href="{{ route('decoder-models.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus me-1"></i>Créer un décodeur
                            </a>
                        @elseif(!$nas->latestSnapshot)
                            <p class="mb-2">Aucun snapshot disponible.</p>
                            <a href="{{ route('test.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-terminal me-1"></i>Lancer une collecte
                            </a>
                        @else
                            <p class="mb-0">Le décodage n'a retourné aucune donnée.</p>
                        @endif
                    </div>
                </div>

            @endif

        </div>
    </div>

@push('scripts')
<script>
(function () {
    // ─── Sort ────────────────────────────────────────────────────────────────

    function directRows(table) {
        const tbody = table.querySelector(':scope > tbody') || table.querySelector('tbody');
        return tbody ? Array.from(tbody.querySelectorAll(':scope > tr')) : [];
    }

    function sortRows(table, colIdx, dir) {
        const tbody = table.querySelector(':scope > tbody') || table.querySelector('tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll(':scope > tr'));
        rows.sort(function (a, b) {
            const aText = (a.cells[colIdx] ? a.cells[colIdx].textContent : '').trim();
            const bText = (b.cells[colIdx] ? b.cells[colIdx].textContent : '').trim();
            const aNum  = parseFloat(aText.replace(/[^\d.-]/g, ''));
            const bNum  = parseFloat(bText.replace(/[^\d.-]/g, ''));
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return dir === 'asc' ? aNum - bNum : bNum - aNum;
            }
            return dir === 'asc'
                ? aText.localeCompare(bText, 'fr', { sensitivity: 'base' })
                : bText.localeCompare(aText, 'fr', { sensitivity: 'base' });
        });
        rows.forEach(function (r) { tbody.appendChild(r); });
    }

    function initSort(table) {
        var sortCol = -1, sortDir = 'asc';
        table.querySelectorAll('th.sortable-th').forEach(function (th, idx) {
            th.addEventListener('click', function () {
                if (sortCol === idx) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortCol = idx;
                    sortDir = 'asc';
                }
                table.querySelectorAll('th.sortable-th .sort-icon').forEach(function (icon, i) {
                    icon.textContent = i === idx ? (sortDir === 'asc' ? ' ↑' : ' ↓') : ' ↕';
                });
                sortRows(table, idx, sortDir);
            });
        });
    }

    // ─── Search ──────────────────────────────────────────────────────────────

    function initSearch(input, table, badge) {
        var total = directRows(table).length;
        input.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            var visible = 0;
            directRows(table).forEach(function (row) {
                var match = q === '' || row.textContent.toLowerCase().indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (badge) {
                badge.textContent = q === '' ? total : (visible + '/' + total);
            }
        });
    }

    // ─── Init ─────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        // First-level: sort + search
        document.querySelectorAll('.sortable-table').forEach(function (table) {
            var wrapper    = table.closest('.table-responsive');
            var headerDiv  = wrapper ? wrapper.previousElementSibling : null;
            var badge      = headerDiv ? headerDiv.querySelector('.row-count-badge') : null;
            var searchInput = headerDiv ? headerDiv.querySelector('.table-search-input') : null;
            initSort(table);
            if (searchInput) initSearch(searchInput, table, badge);
        });

        // Sub-tables: sort only
        document.querySelectorAll('.table-sub-sort').forEach(function (table) {
            initSort(table);
        });
    });
}());
</script>
@endpush

</x-app-layout>
