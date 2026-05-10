@php
    $map = [
        'pending'  => ['bg-warning', 'text-dark', 'En attente'],
        'approved' => ['bg-success', 'text-white', 'Approuvé'],
        'rejected' => ['bg-danger',  'text-white', 'Rejeté'],
    ];
    [$bg, $color, $label] = $map[$status] ?? ['bg-secondary', 'text-white', $status];
@endphp
<span class="badge {{ $bg }} {{ $color }}">{{ $label }}</span>
