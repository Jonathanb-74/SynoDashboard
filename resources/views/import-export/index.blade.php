<x-app-layout>
    <x-slot name="title">Import / Export</x-slot>

    @if($errors->has('export'))
        <div class="alert alert-danger">{{ $errors->first('export') }}</div>
    @endif

    @if($errors->has('import'))
        <div class="alert alert-danger">{{ $errors->first('import') }}</div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         PENDING IMPORT CONFIRMATION
    ═══════════════════════════════════════════════════════════════════════ --}}
    @if($pendingImport)
    @php
        $preview  = $pendingImport['preview'];
        $warnings = $pendingImport['warnings'];
        $hasWarning = count($warnings) > 0;
    @endphp

    <div class="card border-0 shadow mb-4 border-start border-4 {{ $hasWarning ? 'border-warning' : 'border-info' }}">
        <div class="card-header {{ $hasWarning ? 'bg-warning bg-opacity-10' : 'bg-info bg-opacity-10' }} py-2">
            <span class="fw-semibold">
                <i class="bi bi-{{ $hasWarning ? 'exclamation-triangle text-warning' : 'eye text-info' }} me-2"></i>
                Prévisualisation de l'import
            </span>
        </div>
        <div class="card-body">

            {{-- Warnings --}}
            @foreach($warnings as $warning)
                <div class="alert alert-warning d-flex gap-2 py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <span>{{ $warning }}</span>
                </div>
            @endforeach

            {{-- Preview items --}}
            <div class="row g-3 mb-4">
                @if(isset($preview['decoder_model']))
                @php $d = $preview['decoder_model'] @endphp
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3 p-3 border rounded bg-light">
                        <i class="bi bi-code-square text-primary fs-4 mt-1"></i>
                        <div>
                            <div class="fw-semibold">{{ $d['name'] }}</div>
                            <div class="text-muted small">
                                Décodeur · {{ $d['blocks_count'] }} bloc(s)
                            </div>
                            @if($d['exists'])
                                <span class="badge bg-warning text-dark mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>Nom déjà utilisé — sera renommé
                                </span>
                            @else
                                <span class="badge bg-success mt-1">Nouveau</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                @if(isset($preview['api_model']))
                @php $a = $preview['api_model'] @endphp
                <div class="col-md-6">
                    <div class="d-flex align-items-start gap-3 p-3 border rounded bg-light">
                        <i class="bi bi-diagram-3 text-primary fs-4 mt-1"></i>
                        <div>
                            <div class="fw-semibold">{{ $a['name'] }}</div>
                            <div class="text-muted small">
                                Modèle API · {{ $a['entries_count'] }} entrée(s)
                            </div>
                            @if($a['exists'])
                                <span class="badge bg-warning text-dark mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>Nom déjà utilisé — sera renommé
                                </span>
                            @else
                                <span class="badge bg-success mt-1">Nouveau</span>
                            @endif

                            @if(isset($a['linked_decoder_name']))
                                <div class="mt-1 small">
                                    <i class="bi bi-link-45deg me-1"></i>
                                    Liaison décodeur :
                                    <code>{{ $a['linked_decoder_name'] }}</code>
                                    @if($a['linked_decoder_resolvable'])
                                        <span class="badge bg-success ms-1">Trouvé</span>
                                    @else
                                        <span class="badge bg-danger ms-1">Introuvable</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Action buttons --}}
            <div class="d-flex gap-2 flex-wrap">
                {{-- Primary: import everything --}}
                @if(!$hasWarning)
                <form method="POST" action="{{ route('import-export.import.confirm') }}">
                    @csrf
                    <input type="hidden" name="skip_decoder_link" value="0">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-download me-1"></i>Importer
                    </button>
                </form>
                @else
                {{-- Warning case: two buttons --}}
                <form method="POST" action="{{ route('import-export.import.confirm') }}">
                    @csrf
                    <input type="hidden" name="skip_decoder_link" value="0">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-cloud-download me-1"></i>Importer sans la liaison décodeur
                    </button>
                </form>
                @endif

                {{-- Cancel --}}
                <form method="POST" action="{{ route('import-export.import.cancel') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-1"></i>Annuler
                    </button>
                </form>
            </div>

        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════
         MAIN PANELS
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="row g-4">

        {{-- ─── Export ──────────────────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="bi bi-box-arrow-up me-2 text-primary"></i>Exporter
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Génère un fichier <code>.json</code> contenant la configuration du modèle API et/ou du décodeur.
                        Les deux peuvent être exportés ensemble pour conserver leur liaison.
                    </p>

                    <form method="POST" action="{{ route('import-export.export') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium small">Modèle API</label>
                            <select name="api_model_id" class="form-select form-select-sm">
                                <option value="">— aucun —</option>
                                @foreach($apiModels as $m)
                                    <option value="{{ $m->id }}">
                                        {{ $m->name }}
                                        @if($m->decoderModel)
                                            · lié à {{ $m->decoderModel->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium small">Décodeur JSON</label>
                            <select name="decoder_model_id" class="form-select form-select-sm">
                                <option value="">— aucun —</option>
                                @foreach($decoderModels as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Sélectionner le modèle API lié au décodeur exporte automatiquement la référence de liaison dans le fichier.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download me-1"></i>Télécharger le fichier JSON
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ─── Import ──────────────────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-down me-2 text-success"></i>Importer
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Importe un fichier d'export SynoManager (<code>.json</code>). Si un modèle portant le même nom existe déjà,
                        il sera importé sous un nom différent (sans écraser l'existant).
                    </p>

                    <form method="POST" action="{{ route('import-export.import') }}"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-medium small">Fichier d'export <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control form-control-sm"
                                   accept=".json,application/json" required>
                            <div class="form-text">Format : fichier <code>.json</code> exporté depuis SynoManager.</div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload me-1"></i>Analyser et prévisualiser
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- ─── Existing models reference ───────────────────────────────────── --}}
    @if($apiModels->isNotEmpty() || $decoderModels->isNotEmpty())
    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            @if($apiModels->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 small fw-semibold text-muted">
                    Modèles API existants
                </div>
                <ul class="list-group list-group-flush" style="font-size:.85rem">
                    @foreach($apiModels as $m)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-diagram-3 text-primary"></i>
                        <span class="flex-grow-1">{{ $m->name }}</span>
                        @if($m->decoderModel)
                            <span class="text-muted small">
                                <i class="bi bi-link-45deg me-1"></i>{{ $m->decoderModel->name }}
                            </span>
                        @endif
                        <a href="{{ route('api-models.edit', $m) }}" class="text-muted small">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        <div class="col-lg-6">
            @if($decoderModels->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2 small fw-semibold text-muted">
                    Décodeurs existants
                </div>
                <ul class="list-group list-group-flush" style="font-size:.85rem">
                    @foreach($decoderModels as $m)
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <i class="bi bi-code-square text-primary"></i>
                        <span class="flex-grow-1">{{ $m->name }}</span>
                        <a href="{{ route('decoder-models.edit', $m) }}" class="text-muted small">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

</x-app-layout>
