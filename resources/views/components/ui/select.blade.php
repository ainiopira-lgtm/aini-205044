@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Pilih opsi',
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class(['block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
