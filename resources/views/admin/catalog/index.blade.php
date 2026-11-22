<x-admin-layout :title="$title">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        </div>
    </x-slot>

    <x-ui.card>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <form method="GET" action="{{ route('admin.'.$resource.'.index') }}" class="flex flex-1 items-end gap-3">
                <div class="w-full max-w-md">
                    <x-ui.input name="q" label="Pencarian" :value="$search" placeholder="Cari {{ strtolower($title) }}..." />
                </div>
                <x-ui.button type="submit">Cari</x-ui.button>
            </form>
            <x-ui.button :href="route('admin.'.$resource.'.create')">Tambah {{ strtolower($title) }}</x-ui.button>
        </div>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    @foreach ($columns as $label => $attribute)
                        <th scope="col" class="px-5 py-3">{{ $label }}</th>
                    @endforeach
                    <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    @foreach ($columns as $attribute)
                        @php($value = data_get($item, $attribute))
                        <td class="max-w-sm px-5 py-3.5">
                            {{ $value === null || $value === '' ? '—' : $value }}
                        </td>
                    @endforeach
                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                        <a href="{{ route('admin.'.$resource.'.show', $item) }}" class="font-semibold text-gray-800 hover:underline">Detail</a>
                        <a href="{{ route('admin.'.$resource.'.edit', $item) }}" class="ms-3 font-semibold text-gray-600 hover:underline">Ubah</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="px-5 py-10 text-center text-gray-500">Data {{ strtolower($title) }} belum tersedia.</td>
                </tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$items" />
    </x-ui.card>
</x-admin-layout>
