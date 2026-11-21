@props([
    'type' => 'success',
    'message' => null,
])

@php
    $message = $message ?? session($type);
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'info' => 'border-blue-200 bg-blue-50 text-blue-900',
    ];
    $style = $styles[$type] ?? $styles['info'];
@endphp

@if ($message)
    <div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$style]) }}>
        <span class="font-semibold">{{ ucfirst($type) }}</span>
        <p>{{ $message }}</p>
    </div>
@endif
