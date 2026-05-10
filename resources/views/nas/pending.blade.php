<x-app-layout>
    <x-slot name="title">NAS en attente d'approbation</x-slot>

    @if($nasList->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-check-circle display-4 d-block mb-2 text-success opacity-50"></i>
                Aucun NAS en attente d'approbation.
            </div>
        </div>
    @else
        @foreach($nasList as $nas)
            @php $snapshot = $nas->latestSnapshot; @endphp
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-semibold">{{ $nas->name }}</span>
                        <span class="text-muted small ms-2">{{ $nas->model }}</span>
                        <span class="badge bg-warning text-dark ms-2">En attente</span>
                    </div>
                    <small class="text-muted">Reçu {{ $nas->created_at->diffForHumans() }}</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <dl class="small mb-0">
                                <dt>N° Série</dt>
                                <dd class="font-monospace">{{ $nas->serial }}</dd>
                                <dt>Version DSM</dt>
                                <dd>{{ $nas->dsm_version ?? '—' }}</dd>
                                @if($snapshot)
                                    <dt>APIs disponibles</dt>
                                    <dd>{{ $nas->availableApis->count() }}</dd>
                                @endif
                            </dl>
                        </div>

                        <div class="col-md-8">
                            <form method="POST" action="{{ route('nas.approve', $nas) }}" class="d-flex flex-wrap gap-2 align-items-end">
                                @csrf

                                <div style="min-width:160px">
                                    <label class="form-label small fw-medium mb-1">Modèle API</label>
                                    <select name="api_model_id" class="form-select form-select-sm">
                                        <option value="">— aucun —</option>
                                        @foreach($allApiModels as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="min-width:160px">
                                    <label class="form-label small fw-medium mb-1">Décodeur JSON</label>
                                    <select name="decoder_model_id" class="form-select form-select-sm">
                                        <option value="">— aucun —</option>
                                        @foreach($allDecoderModels as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div style="min-width:110px">
                                    <label class="form-label small fw-medium mb-1">Fréquence (min)</label>
                                    <input type="number" name="collection_frequency" class="form-control form-control-sm"
                                           value="60" min="1" max="10080">
                                </div>

                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle me-1"></i>Approuver
                                </button>
                            </form>

                            <form method="POST" action="{{ route('nas.reject', $nas) }}" class="mt-2 d-inline"
                                  onsubmit="return confirm('Rejeter ce NAS ?')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>Rejeter
                                </button>
                            </form>

                            @if($snapshot)
                                <a href="{{ route('snapshots.show', $snapshot) }}" class="btn btn-outline-secondary btn-sm ms-1">
                                    <i class="bi bi-code-slash me-1"></i>Voir snapshot
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</x-app-layout>
