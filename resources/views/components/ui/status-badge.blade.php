@props([
    'status',
    'label' => null,
])

@php
    $statusClasses = [
        'available' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'borrowed' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'active' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'returned' => 'bg-gray-100 text-gray-700 ring-gray-600/20',
        'overdue' => 'bg-red-50 text-red-700 ring-red-600/20',
        'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'unpaid' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'damaged' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'lost' => 'bg-red-50 text-red-700 ring-red-600/20',
        'pending' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    ];
    $normalizedStatus = strtolower((string) $status);
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.($statusClasses[$normalizedStatus] ?? 'bg-gray-100 text-gray-700 ring-gray-600/20')]) }}>
    {{ $label ?? str($status)->replace('_', ' ')->ucfirst() }}
</span>
