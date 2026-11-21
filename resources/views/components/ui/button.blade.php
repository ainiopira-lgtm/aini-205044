@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'border border-transparent bg-gray-900 text-white hover:bg-gray-700 focus-visible:ring-gray-500',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus-visible:ring-gray-400',
        'danger' => 'border border-transparent bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500',
        'ghost' => 'border border-transparent bg-transparent text-gray-700 hover:bg-gray-100 focus-visible:ring-gray-400',
    ];
    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-sm',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
