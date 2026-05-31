<x-app-layout>
    <x-slot name="title">Décodeur — {{ $decoderModel->name }}</x-slot>

    @push('styles')
    <style>
        .hint         { font-size:.75rem; color:#6c757d; }
        .edit-row td  { background:#fffbf0; }
        .drag-handle  { cursor:grab; color:#adb5bd; }
        .drag-handle:hover { color:#6c757d; }
        .sortable-ghost    { opacity:.4; }
        .level-element  { border-left:3px solid #0d6efd22; }
        .level-column   { border-left:3px solid #19875422; }
        .level-subcol   { border-left:3px solid #ffc10722; }
        .add-form-row td { background:#f8f9fa; }
    </style>
    @endpush

    {{-- Header: model name / description --}}
    <form method="POST" action="{{ route('decoder-models.update', $decoderModel) }}" class="mb-4">
        @csrf @method('PUT')
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-medium small">Nom *</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="{{ old('name', $decoderModel->name) }}" required>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-medium small">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm"
                               value="{{ old('description', $decoderModel->description) }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-save me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Blocks --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-layers me-1"></i>Blocs d'affichage</h6>
        <button type="button" class="btn btn-sm btn-outline-info py-0 px-2"
                data-bs-toggle="modal" data-bs-target="#transformerHelpModal">
            <i class="bi bi-magic me-1"></i>Guide transformateurs
        </button>
    </div>

    <div id="sortable-blocks">
        @foreach($decoderModel->blocks as $block)
        <div class="card border-0 shadow-sm mb-3" data-block-id="{{ $block->id }}">
            {{-- Block header --}}
            <div class="card-header bg-white d-flex align-items-center gap-2 py-2 px-3">
                <span class="drag-handle block-drag-handle me-1"><i class="bi bi-grip-vertical"></i></span>
                <i class="bi {{ $block->icon ?? 'bi-box' }} text-primary"></i>
                <strong class="flex-grow-1">{{ $block->title }}</strong>
                @if($block->description)
                    <span class="text-muted small">{{ $block->description }}</span>
                @endif
                <button class="btn btn-sm btn-outline-secondary py-0 px-1"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#block-{{ $block->id }}">
                    <i class="bi bi-chevron-down"></i>
                </button>
                {{-- Edit block --}}
                <div x-data="{ open: false }">
                    <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1"
                            @click="open = !open" title="Modifier le bloc">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <div x-show="open" x-cloak class="position-absolute bg-white border rounded shadow p-3 mt-1" style="z-index:100;min-width:320px;right:0">
                        <form method="POST"
                              action="{{ route('decoder-models.updateBlock', [$decoderModel, $block]) }}">
                            @csrf @method('PATCH')
                            <div class="mb-2">
                                <label class="form-label small mb-1">Titre *</label>
                                <input type="text" name="title" class="form-control form-control-sm"
                                       value="{{ $block->title }}" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Description</label>
                                <input type="text" name="description" class="form-control form-control-sm"
                                       value="{{ $block->description }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Icône Bootstrap</label>
                                <input type="text" name="icon" class="form-control form-control-sm"
                                       value="{{ $block->icon }}" placeholder="bi-cpu">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-warning flex-grow-1">Enregistrer</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="open=false">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
                {{-- Delete block --}}
                <form method="POST"
                      action="{{ route('decoder-models.destroyBlock', [$decoderModel, $block]) }}"
                      onsubmit="return confirm('Supprimer ce bloc et tous ses éléments ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>

            {{-- Block body (collapsible) --}}
            <div class="collapse show" id="block-{{ $block->id }}">
                <div class="card-body p-0">

                    {{-- Elements list --}}
                    <div class="sortable-elements" data-block-id="{{ $block->id }}"
                         data-reorder-url="{{ route('decoder-models.reorderElements', [$decoderModel, $block]) }}">

                        @foreach($block->elements as $element)
                        <div class="level-element ps-3 py-2 border-bottom" data-element-id="{{ $element->id }}">

                            {{-- Element row --}}
                            <div x-data="{ editOpen: false, colOpen: false }">
                                <div class="d-flex align-items-center gap-2 pe-3">
                                    <span class="drag-handle element-drag-handle"><i class="bi bi-grip-vertical small"></i></span>
                                    @if($element->type === 'simple')
                                        <span class="badge bg-primary">valeur</span>
                                    @else
                                        <span class="badge bg-success">boucle</span>
                                    @endif
                                    <span class="fw-medium small">{{ $element->label }}</span>
                                    @if($element->api_name)
                                        <code class="small text-muted">{{ $element->api_name }}</code>
                                    @endif
                                    @if($element->json_path)
                                        <code class="small text-secondary">{{ implode(' › ', $element->json_path) }}</code>
                                    @endif
                                    @if($element->transformer)
                                        <span class="badge bg-secondary">{{ $element->transformer }}</span>
                                    @endif
                                    <div class="ms-auto d-flex gap-1">
                                        @if($element->type === 'loop')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-success py-0 px-1"
                                                @click="colOpen = !colOpen" title="Colonnes">
                                            <i class="bi bi-table"></i>
                                            <span class="badge bg-success">{{ $element->columns->count() }}</span>
                                        </button>
                                        @endif
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning py-0 px-1"
                                                @click="editOpen = !editOpen" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('decoder-models.destroyElement', [$decoderModel, $block, $element]) }}"
                                              onsubmit="return confirm('Supprimer cet élément ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Edit element form --}}
                                <div x-show="editOpen" x-cloak class="mt-2 p-2 bg-light rounded border">
                                    <form method="POST"
                                          action="{{ route('decoder-models.updateElement', [$decoderModel, $block, $element]) }}">
                                        @csrf @method('PATCH')
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <label class="form-label small mb-1">Type</label>
                                                <select name="type" class="form-select form-select-sm">
                                                    <option value="simple" @selected($element->type==='simple')>Valeur</option>
                                                    <option value="loop"   @selected($element->type==='loop')>Boucle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Label *</label>
                                                <input type="text" name="label" class="form-control form-control-sm"
                                                       value="{{ $element->label }}" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">
                                                    Scope API
                                                    <span class="text-muted fw-normal">(si renseigné, le chemin est relatif à cette réponse)</span>
                                                </label>
                                                <input type="text" name="api_name" class="form-control form-control-sm"
                                                       value="{{ $element->api_name }}"
                                                       placeholder="SYNO.Core.System">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small mb-1">Chemin JSON</label>
                                                <input type="text" name="json_path" class="form-control form-control-sm"
                                                       value="{{ implode(',', $element->json_path ?? []) }}"
                                                       placeholder="data,cpu_vendor">
                                                <div class="hint">
                                                    Clés séparées par des virgules.<br>
                                                    → Scope renseigné (<code>SYNO.Core.System</code>) : <code>data,cpu_vendor</code><br>
                                                    → Pas de scope : <code>responses,SYNO.Core.System,data,cpu_vendor</code>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Transformateur</label>
                                                <select name="transformer" class="form-select form-select-sm">
                                                    <option value="">—</option>
                                                    @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                                    <option value="{{ $t }}" @selected($element->transformer===$t)>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small mb-1">Config transformateur (JSON)</label>
                                                <input type="text" name="transformer_config" class="form-control form-control-sm font-monospace"
                                                       value="{{ $element->transformer_config ? json_encode($element->transformer_config) : '' }}"
                                                       placeholder='{"format":"d/m/Y"}'>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end gap-2">
                                                <button type="submit" class="btn btn-sm btn-warning flex-grow-1">Enregistrer</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="editOpen=false">✕</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- Global attribute mapping (simple elements only) --}}
                                @if($element->type === 'simple' && $globalAttributes->isNotEmpty())
                                @php $mappedAttrId = $existingMappings[$element->internal_key] ?? null; @endphp
                                <div class="mt-2 px-2 py-1 d-flex align-items-center gap-2 rounded"
                                     style="background:#f0f4ff;border:1px dashed #bcd">
                                    <i class="bi bi-diagram-2 text-primary small"></i>
                                    <span class="small text-muted">Attribut global :</span>
                                    @if($mappedAttrId)
                                        @php $mappedAttr = $globalAttributes->firstWhere('id', $mappedAttrId); @endphp
                                        <span class="badge bg-primary">{{ $mappedAttr?->name ?? '?' }}</span>
                                        <form method="POST"
                                              action="{{ route('decoder-models.global-map.destroy', [$decoderModel, $element]) }}"
                                              class="d-inline ms-1">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1"
                                                    title="Retirer le mapping">
                                                <i class="bi bi-x small"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('decoder-models.global-map.store', [$decoderModel, $element]) }}"
                                              class="d-inline d-flex gap-1 align-items-center">
                                            @csrf
                                            <select name="global_attribute_id" class="form-select form-select-sm py-0"
                                                    style="width:auto;font-size:.8rem">
                                                <option value="">— lier à un attribut —</option>
                                                @foreach($globalAttributes as $ga)
                                                    <option value="{{ $ga->id }}">
                                                        {{ $ga->name }}{{ $ga->unit ? ' ('.$ga->unit.')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-2"
                                                    style="font-size:.8rem">Lier</button>
                                        </form>
                                    @endif
                                </div>
                                @endif

                                {{-- Columns (loop elements only) --}}
                                @if($element->type === 'loop')
                                <div x-show="colOpen" x-cloak class="mt-2 ms-3">
                                    <div class="sortable-columns"
                                         data-element-id="{{ $element->id }}"
                                         data-reorder-url="{{ route('decoder-models.reorderColumns', [$decoderModel, $block, $element]) }}">

                                        @foreach($element->columns as $column)
                                        <div class="level-column ps-2 py-1 border-bottom" data-column-id="{{ $column->id }}">
                                            <div x-data="{ editCol: false, subOpen: false }">
                                                <div class="d-flex align-items-center gap-2 pe-2">
                                                    <span class="drag-handle column-drag-handle"><i class="bi bi-grip-vertical small"></i></span>
                                                    @if($column->type === 'value')
                                                        <span class="badge bg-primary">valeur</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">sous-boucle</span>
                                                    @endif
                                                    <span class="small">{{ $column->label }}</span>
                                                    @if($column->json_path)
                                                        <code class="small text-secondary">{{ implode(' › ', $column->json_path) }}</code>
                                                    @endif
                                                    @if($column->transformer)
                                                        <span class="badge bg-secondary small">{{ $column->transformer }}</span>
                                                    @endif
                                                    <div class="ms-auto d-flex gap-1">
                                                        @if($column->type === 'loop')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning py-0 px-1"
                                                                @click="subOpen = !subOpen" title="Sous-colonnes">
                                                            <i class="bi bi-diagram-3"></i>
                                                            <span class="badge bg-warning text-dark">{{ $column->subColumns->count() }}</span>
                                                        </button>
                                                        @endif
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-warning py-0 px-1"
                                                                @click="editCol = !editCol">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST"
                                                              action="{{ route('decoder-models.destroyColumn', [$decoderModel, $block, $element, $column]) }}"
                                                              onsubmit="return confirm('Supprimer cette colonne ?')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>

                                                {{-- Edit column --}}
                                                <div x-show="editCol" x-cloak class="mt-1 p-2 bg-light rounded border">
                                                    <form method="POST"
                                                          action="{{ route('decoder-models.updateColumn', [$decoderModel, $block, $element, $column]) }}">
                                                        @csrf @method('PATCH')
                                                        <div class="row g-2">
                                                            <div class="col-md-2">
                                                                <label class="form-label small mb-1">Type</label>
                                                                <select name="type" class="form-select form-select-sm">
                                                                    <option value="value" @selected($column->type==='value')>Valeur</option>
                                                                    <option value="loop"  @selected($column->type==='loop')>Sous-boucle</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small mb-1">Label *</label>
                                                                <input type="text" name="label" class="form-control form-control-sm"
                                                                       value="{{ $column->label }}" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small mb-1">Chemin JSON</label>
                                                                <input type="text" name="json_path" class="form-control form-control-sm"
                                                                       value="{{ implode(',', $column->json_path ?? []) }}"
                                                                       placeholder="name">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <label class="form-label small mb-1">Transformer</label>
                                                                <select name="transformer" class="form-select form-select-sm">
                                                                    <option value="">—</option>
                                                                    @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                                                    <option value="{{ $t }}" @selected($column->transformer===$t)>{{ $t }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small mb-1">Config (JSON)</label>
                                                                <input type="text" name="transformer_config" class="form-control form-control-sm font-monospace"
                                                                       value="{{ $column->transformer_config ? json_encode($column->transformer_config) : '' }}">
                                                            </div>
                                                            <div class="col-md-2 d-flex align-items-end gap-1">
                                                                <button type="submit" class="btn btn-sm btn-warning flex-grow-1">OK</button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="editCol=false">✕</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>

                                                {{-- Sub-columns (loop columns only) --}}
                                                @if($column->type === 'loop')
                                                <div x-show="subOpen" x-cloak class="mt-1 ms-3">
                                                    <div class="sortable-subcolumns"
                                                         data-column-id="{{ $column->id }}"
                                                         data-reorder-url="{{ route('decoder-models.reorderSubColumns', [$decoderModel, $block, $element, $column]) }}">
                                                        @foreach($column->subColumns as $sub)
                                                        <div class="level-subcol ps-2 py-1 border-bottom" data-subcol-id="{{ $sub->id }}">
                                                            <div x-data="{ editSub: false }">
                                                                <div class="d-flex align-items-center gap-2 pe-2">
                                                                    <span class="drag-handle subcol-drag-handle"><i class="bi bi-grip-vertical small"></i></span>
                                                                    <span class="small">{{ $sub->label }}</span>
                                                                    @if($sub->json_path)
                                                                        <code class="small text-secondary">{{ implode(' › ', $sub->json_path) }}</code>
                                                                    @endif
                                                                    @if($sub->transformer)
                                                                        <span class="badge bg-secondary small">{{ $sub->transformer }}</span>
                                                                    @endif
                                                                    <div class="ms-auto d-flex gap-1">
                                                                        <button type="button"
                                                                                class="btn btn-sm btn-outline-warning py-0 px-1"
                                                                                @click="editSub = !editSub">
                                                                            <i class="bi bi-pencil"></i>
                                                                        </button>
                                                                        <form method="POST"
                                                                              action="{{ route('decoder-models.destroySubColumn', [$decoderModel, $block, $element, $column, $sub]) }}"
                                                                              onsubmit="return confirm('Supprimer ?')">
                                                                            @csrf @method('DELETE')
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                                <div x-show="editSub" x-cloak class="mt-1 p-2 bg-light rounded border">
                                                                    <form method="POST"
                                                                          action="{{ route('decoder-models.updateSubColumn', [$decoderModel, $block, $element, $column, $sub]) }}">
                                                                        @csrf @method('PATCH')
                                                                        <div class="row g-2">
                                                                            <div class="col-md-3">
                                                                                <input type="text" name="label" class="form-control form-control-sm"
                                                                                       value="{{ $sub->label }}" placeholder="Label *" required>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <input type="text" name="json_path" class="form-control form-control-sm"
                                                                                       value="{{ implode(',', $sub->json_path ?? []) }}" placeholder="Chemin JSON">
                                                                            </div>
                                                                            <div class="col-md-2">
                                                                                <select name="transformer" class="form-select form-select-sm">
                                                                                    <option value="">—</option>
                                                                                    @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                                                                    <option value="{{ $t }}" @selected($sub->transformer===$t)>{{ $t }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-2">
                                                                                <input type="text" name="transformer_config" class="form-control form-control-sm font-monospace"
                                                                                       value="{{ $sub->transformer_config ? json_encode($sub->transformer_config) : '' }}"
                                                                                       placeholder='{"format":"…"}'>
                                                                            </div>
                                                                            <div class="col-md-2 d-flex gap-1">
                                                                                <button type="submit" class="btn btn-sm btn-warning flex-grow-1">OK</button>
                                                                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="editSub=false">✕</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>

                                                    {{-- Add sub-column --}}
                                                    <form method="POST"
                                                          action="{{ route('decoder-models.storeSubColumn', [$decoderModel, $block, $element, $column]) }}"
                                                          class="mt-1 p-2 bg-warning bg-opacity-10 rounded">
                                                        @csrf
                                                        <div class="row g-2">
                                                            <div class="col-md-3">
                                                                <input type="text" name="label" class="form-control form-control-sm"
                                                                       placeholder="Label sous-colonne *" required>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input type="text" name="json_path" class="form-control form-control-sm"
                                                                       placeholder="Chemin JSON (ex: name)">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <select name="transformer" class="form-select form-select-sm">
                                                                    <option value="">—</option>
                                                                    @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                                                    <option value="{{ $t }}">{{ $t }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <input type="text" name="transformer_config" class="form-control form-control-sm font-monospace"
                                                                       placeholder='{"format":"…"}'>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="submit" class="btn btn-sm btn-warning w-100">
                                                                    <i class="bi bi-plus"></i> Sous-colonne
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>

                                    {{-- Add column --}}
                                    <form method="POST"
                                          action="{{ route('decoder-models.storeColumn', [$decoderModel, $block, $element]) }}"
                                          class="mt-2 p-2 bg-success bg-opacity-10 rounded">
                                        @csrf
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <select name="type" class="form-select form-select-sm">
                                                    <option value="value">Valeur</option>
                                                    <option value="loop">Sous-boucle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="label" class="form-control form-control-sm"
                                                       placeholder="Label colonne *" required>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" name="json_path" class="form-control form-control-sm"
                                                       placeholder="Chemin JSON (ex: name,status)">
                                            </div>
                                            <div class="col-md-2">
                                                <select name="transformer" class="form-select form-select-sm">
                                                    <option value="">— transformer —</option>
                                                    @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                                    <option value="{{ $t }}">{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-sm btn-success w-100">
                                                    <i class="bi bi-plus"></i> Colonne
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Add element --}}
                    <div class="p-3 border-top bg-light">
                        <form method="POST"
                              action="{{ route('decoder-models.storeElement', [$decoderModel, $block]) }}">
                            @csrf
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Type</label>
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="simple">Valeur simple</option>
                                        <option value="loop">Boucle (tableau)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Label *</label>
                                    <input type="text" name="label" class="form-control form-control-sm"
                                           placeholder="Ex: Nom du serveur" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">
                                        Scope API
                                        <span class="text-muted fw-normal">(rend le chemin relatif)</span>
                                    </label>
                                    <input type="text" name="api_name" class="form-control form-control-sm"
                                           placeholder="SYNO.Core.System">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Chemin JSON</label>
                                    <input type="text" name="json_path" class="form-control form-control-sm"
                                           placeholder="data,cpu_vendor">
                                    <div class="hint">
                                        → Scope renseigné (<code>SYNO.Core.System</code>) : <code>data,cpu_vendor</code><br>
                                        → Pas de scope : <code>responses,SYNO.Core.System,data,cpu_vendor</code>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select name="transformer" class="form-select form-select-sm">
                                        <option value="">— transformer —</option>
                                        @foreach(['date','timestamp','bytes','megabytes','duration','uptime','boolean','badge_map','color_if'] as $t)
                                        <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-plus"></i> Élément
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Add block --}}
    <div class="card border-dashed shadow-sm mt-3" style="border:2px dashed #dee2e6!important">
        <div class="card-body" x-data="{ open: false }">
            <button type="button" class="btn btn-sm btn-outline-primary" @click="open = !open">
                <i class="bi bi-plus-circle me-1"></i>Ajouter un bloc
            </button>
            <div x-show="open" x-cloak class="mt-3">
                <form method="POST" action="{{ route('decoder-models.storeBlock', $decoderModel) }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Titre *</label>
                            <input type="text" name="title" class="form-control form-control-sm"
                                   placeholder="Ex: Informations système" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm"
                                   placeholder="Description courte">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Icône Bootstrap</label>
                            <input type="text" name="icon" class="form-control form-control-sm"
                                   placeholder="bi-cpu">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-plus"></i> Créer le bloc
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══ Transformer Help Modal ═══════════════════════════════════════════════ --}}
    <div class="modal fade" id="transformerHelpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold">
                        <i class="bi bi-magic me-2 text-primary"></i>Guide des transformateurs
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="font-size:.875rem">
                    <div class="row g-3">

                        {{-- Sans config --}}
                        <div class="col-12">
                            <h6 class="fw-semibold border-bottom pb-1 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Sans configuration requise</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">bytes</code>
                                            <p class="mb-1 text-muted small">Convertit des <strong>octets</strong> en unité lisible.</p>
                                            <div class="font-monospace small text-body-secondary">1073741824 → <strong class="text-body">1 GB</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">megabytes</code>
                                            <p class="mb-1 text-muted small">Convertit des <strong>mégaoctets</strong> en unité lisible (utile pour la RAM Synology).</p>
                                            <div class="font-monospace small text-body-secondary">20480 → <strong class="text-body">20 GB</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">duration</code>
                                            <p class="mb-1 text-muted small">Convertit des <strong>secondes</strong> (entier) en durée lisible.</p>
                                            <div class="font-monospace small text-body-secondary">3661 → <strong class="text-body">1h 1min</strong></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">uptime</code>
                                            <p class="mb-1 text-muted small">Parse le format <code>H:MM:SS</code> de l'API Synology et affiche jours/heures/minutes.</p>
                                            <div class="font-monospace small text-body-secondary">"1073:10:13" → <strong class="text-body">44j 17h 10min</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Dates --}}
                        <div class="col-12">
                            <h6 class="fw-semibold border-bottom pb-1 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Formatage de date</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">date</code>
                                            <p class="mb-1 text-muted small">Parse une date ISO (Carbon). Format PHP par défaut : <code>d/m/Y H:i</code></p>
                                            <div class="font-monospace small text-body-secondary">"2024-01-15T10:30:00Z" → <strong class="text-body">15/01/2024 10:30</strong></div>
                                            <div class="mt-1"><code class="small bg-white px-1 rounded">{"format":"d/m/Y H:i"}</code></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">timestamp</code>
                                            <p class="mb-1 text-muted small">Timestamp Unix (secondes). Même config que <code>date</code>.</p>
                                            <div class="font-monospace small text-body-secondary">1705312200 → <strong class="text-body">15/01/2024 10:30</strong></div>
                                            <div class="mt-1"><code class="small bg-white px-1 rounded">{"format":"d/m/Y H:i"}</code></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Booléens --}}
                        <div class="col-12">
                            <h6 class="fw-semibold border-bottom pb-1 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Booléens</h6>
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">boolean</code>
                                            <p class="mb-1 text-muted small">Retourne un libellé texte pour <code>true</code> / <code>false</code>. Sans config : <em>Oui / Non</em></p>
                                            <div class="mt-1"><code class="small bg-white px-1 rounded">{"true":"Actif","false":"Inactif"}</code></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="card border-0 border-start border-4 border-info bg-info bg-opacity-10 h-100">
                                        <div class="card-body py-2 px-3">
                                            <strong class="small"><i class="bi bi-info-circle me-1"></i>Sans transformateur</strong>
                                            <p class="mb-0 text-muted small mt-1">Les booléens JSON (<code>true</code> / <code>false</code>) sont automatiquement affichés en badge
                                                <span class="badge bg-success">Oui</span> / <span class="badge bg-secondary">Non</span>
                                                sans configuration. Utilisez <code>badge_map</code> si vous souhaitez des labels ou couleurs personnalisés.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- badge_map / color_if --}}
                        <div class="col-12">
                            <h6 class="fw-semibold border-bottom pb-1 text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Badges et couleurs conditionnels</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">badge_map</code>
                                            <p class="mb-1 text-muted small">Badge Bootstrap coloré selon la valeur. Évaluation en ordre, première règle gagnante.</p>
                                            <pre class="small mb-0 bg-white rounded p-2" style="font-size:.73rem;line-height:1.45">{
  "default_color": "secondary",
  "rules": [
    {"op":"==","value":"running","color":"success","label":"En cours"},
    {"op":"==","value":"stopped","color":"danger", "label":"Arrêté"},
    {"op":">", "value":90,      "color":"warning","label":"Chargé"}
  ]
}</pre>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 bg-light h-100">
                                        <div class="card-body py-2 px-3">
                                            <code class="text-primary fw-bold">color_if</code>
                                            <p class="mb-1 text-muted small">Même logique que <code>badge_map</code> mais affiche du <strong>texte coloré</strong> (pas un badge pill).</p>
                                            <pre class="small mb-0 bg-white rounded p-2" style="font-size:.73rem;line-height:1.45">{
  "default_color": "secondary",
  "rules": [
    {"op":"==","value":true, "color":"success","label":"Activé"},
    {"op":"==","value":false,"color":"danger", "label":"Désactivé"}
  ]
}</pre>
                                            <p class="mb-0 text-muted small mt-1">Pour les booléens JSON, écrivez <code>true</code> / <code>false</code> <em>sans guillemets</em>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Référence op + colors --}}
                            <div class="row g-2 mt-1">
                                <div class="col-md-5">
                                    <div class="p-2 border rounded bg-white">
                                        <strong class="small d-block mb-1">Opérateurs (<code>op</code>)</strong>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach(['==','!=','>','>=','<','<='] as $op)
                                                <code class="bg-light border rounded px-2 py-0">{{ $op }}</code>
                                            @endforeach
                                        </div>
                                        <p class="mb-0 text-muted mt-1" style="font-size:.75rem">
                                            Comparaison lâche PHP (<code>==</code>). Les comparaisons <code>&gt;</code> <code>&lt;</code> nécessitent une valeur numérique.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="p-2 border rounded bg-white">
                                        <strong class="small d-block mb-1">Couleurs disponibles (<code>color</code>)</strong>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach(['primary','secondary','success','danger','warning','info','dark'] as $c)
                                                <span class="badge bg-{{ $c }}">{{ $c }}</span>
                                            @endforeach
                                            <span class="badge bg-light text-dark border">light</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Reorder utility
        function initSortable(containerSelector, dragHandleClass, dataAttr, reorderUrlAttr) {
            document.querySelectorAll(containerSelector).forEach(function (container) {
                const url = container.dataset[reorderUrlAttr.replace('data-', '').replace(/-([a-z])/g, (_, c) => c.toUpperCase())];
                if (!url || !window.Sortable) return;
                Sortable.create(container, {
                    handle: '.' + dragHandleClass,
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        const ids = [...container.querySelectorAll('[' + dataAttr + ']')]
                            .map(el => el.getAttribute(dataAttr));
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ ids }),
                        });
                    }
                });
            });
        }

        // Blocks
        if (window.Sortable) {
            const blocksContainer = document.getElementById('sortable-blocks');
            if (blocksContainer) {
                Sortable.create(blocksContainer, {
                    handle: '.block-drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function () {
                        const ids = [...blocksContainer.querySelectorAll('[data-block-id]')]
                            .map(el => el.getAttribute('data-block-id'));
                        fetch('{{ route('decoder-models.reorderBlocks', $decoderModel) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({ ids }),
                        });
                    }
                });
            }
        }

        // Elements
        document.querySelectorAll('.sortable-elements').forEach(function (container) {
            if (!window.Sortable) return;
            const url = container.dataset.reorderUrl;
            Sortable.create(container, {
                handle: '.element-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const ids = [...container.querySelectorAll('[data-element-id]')]
                        .map(el => el.getAttribute('data-element-id'));
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ ids }),
                    });
                }
            });
        });

        // Columns
        document.querySelectorAll('.sortable-columns').forEach(function (container) {
            if (!window.Sortable) return;
            const url = container.dataset.reorderUrl;
            Sortable.create(container, {
                handle: '.column-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const ids = [...container.querySelectorAll('[data-column-id]')]
                        .map(el => el.getAttribute('data-column-id'));
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ ids }),
                    });
                }
            });
        });

        // Sub-columns
        document.querySelectorAll('.sortable-subcolumns').forEach(function (container) {
            if (!window.Sortable) return;
            const url = container.dataset.reorderUrl;
            Sortable.create(container, {
                handle: '.subcol-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const ids = [...container.querySelectorAll('[data-subcol-id]')]
                        .map(el => el.getAttribute('data-subcol-id'));
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ ids }),
                    });
                }
            });
        });
    });
    </script>
    @endpush

</x-app-layout>
