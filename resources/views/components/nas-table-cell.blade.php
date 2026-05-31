@props(['nas', 'column', 'customFieldDefs'])

@if($column->source === 'device')
    @switch($column->field_key)
        @case('name')
            <a href="{{ route('nas.show', $nas) }}" class="text-decoration-none fw-medium">{{ $nas->name }}</a>
            @break
        @case('model')
            {{ $nas->model ?? '—' }}
            @break
        @case('dsm_version')
            {{ $nas->dsm_version ?? '—' }}
            @break
        @case('serial')
            <code class="small">{{ $nas->serial }}</code>
            @break
        @case('online_status')
            @if(!$nas->last_contact_at)
                <span class="badge bg-secondary">Jamais</span>
            @elseif($nas->isOnline())
                <span class="badge bg-success">OK</span>
            @else
                <span class="badge bg-danger">Erreur</span>
            @endif
            @break
        @case('nas_status')
            @include('components.status-badge', ['status' => $nas->status])
            @break
        @case('last_contact_at')
            @if($nas->last_contact_at)
                <span title="{{ $nas->last_contact_at->format('d/m/Y H:i:s') }}">
                    {{ $nas->last_contact_at->diffForHumans() }}
                </span>
            @else
                <span class="text-muted">—</span>
            @endif
            @break
        @case('collection_frequency')
            {{ $nas->collection_frequency }} min
            @break
        @case('created_at')
            {{ $nas->created_at->format('d/m/Y') }}
            @break
        @default
            <span class="text-muted">—</span>
    @endswitch

@elseif($column->source === 'custom_field')
    @php
        $defId   = (int) $column->field_key;
        $cfValue = $nas->customFieldValues->firstWhere('definition_id', $defId);
        $val     = $cfValue?->value;
        $def     = $customFieldDefs->firstWhere('id', $defId);
    @endphp
    @if($def && $val !== null && $val !== '')
        @if($def->type === 'boolean')
            @if($val === '1')
                <i class="bi bi-check-circle-fill text-success"></i>
            @else
                <i class="bi bi-x-circle text-danger"></i>
            @endif
        @elseif($def->type === 'date')
            {{ \Carbon\Carbon::parse($val)->format('d/m/Y') }}
        @else
            {{ $val }}
        @endif
    @else
        <span class="text-muted">—</span>
    @endif
@endif
