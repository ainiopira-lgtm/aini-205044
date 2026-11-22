<x-admin-layout :title="$title">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        </div>
    </x-slot>

    <x-ui.card class="max-w-3xl" :title="$title">
        <form method="POST" action="{{ $item ? route('admin.'.$resource.'.update', $item) : route('admin.'.$resource.'.store') }}" class="space-y-5">
            @csrf
            @if ($item)
                @method('PUT')
            @endif

            @foreach ($fields as $field)
                @php($fieldValue = old($field['name'], data_get($item, $field['name'])))
                @if ($field['type'] === 'textarea')
                    <div>
                        <label for="{{ $field['name'] }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $field['label'] }}</label>
                        <textarea
                            id="{{ $field['name'] }}"
                            name="{{ $field['name'] }}"
                            rows="5"
                            @required($field['required'] ?? false)
                            @error($field['name']) aria-invalid="true" aria-describedby="{{ $field['name'] }}-error" @enderror
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500"
                        >{{ $fieldValue }}</textarea>
                        @error($field['name'])
                            <p id="{{ $field['name'] }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <x-ui.input
                        :name="$field['name']"
                        :label="$field['label']"
                        :type="$field['type']"
                        :value="$fieldValue"
                        :required="$field['required'] ?? false"
                    />
                @endif
            @endforeach

            <div class="flex flex-wrap gap-3 pt-2">
                <x-ui.button type="submit">Simpan</x-ui.button>
                <x-ui.button :href="$item ? route('admin.'.$resource.'.show', $item) : route('admin.'.$resource.'.index')" variant="secondary">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
