<x-admin-layout :title="$title">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        </div>
    </x-slot>

    <x-ui.card :title="$title">
        <dl class="divide-y divide-gray-100">
            @foreach ($fields as $label => $attribute)
                <div class="grid gap-1 py-4 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ $label }}</dt>
                    <dd class="whitespace-pre-line text-sm text-gray-900 sm:col-span-2">{{ data_get($item, $attribute) ?: '—' }}</dd>
                </div>
            @endforeach
        </dl>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-ui.button :href="route('admin.'.$resource.'.edit', $item)">Ubah</x-ui.button>
            <x-ui.button :href="route('admin.'.$resource.'.index')" variant="secondary">Kembali</x-ui.button>
            @if ($canDelete)
                <x-ui.confirmation-modal
                    :name="$resource.'-'.$item->getKey().'-delete'"
                    title="Hapus data?"
                    :message="$deleteMessage"
                    confirm-text="Ya, hapus"
                    :action="route('admin.'.$resource.'.destroy', $item)"
                >
                    <x-slot:trigger>
                        <x-ui.button variant="danger">Hapus</x-ui.button>
                    </x-slot:trigger>
                </x-ui.confirmation-modal>
            @else
                <p class="text-sm text-gray-500">{{ $deleteMessage }}</p>
            @endif
        </div>
    </x-ui.card>
</x-admin-layout>
