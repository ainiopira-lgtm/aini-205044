@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class(['block w-full rounded-lg border-gray-300 text-sm shadow-sm placeholder:text-gray-400 focus:border-gray-500 focus:ring-gray-500']) }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
