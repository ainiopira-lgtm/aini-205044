<x-admin-layout title="Katalog buku">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">Katalog buku</h1>
        </div>
    </x-slot>

    <x-ui.card>
        <form method="GET" action="{{ route('admin.books.index') }}" class="mb-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(14rem,0.6fr)_auto] sm:items-end">
            <x-ui.input name="q" label="Judul, penulis, atau ISBN" :value="$search" placeholder="Cari buku..." />
            <x-ui.select name="category_id" label="Kategori" :options="$categories" :value="$categoryId" placeholder="Semua kategori" />
            <div class="flex gap-2">
                <x-ui.button type="submit">Cari</x-ui.button>
                <x-ui.button href="{{ route('admin.books.index') }}" variant="secondary">Reset</x-ui.button>
            </div>
        </form>

        <div class="mb-4 flex justify-end">
            <x-ui.button href="{{ route('admin.books.create') }}">Tambah buku</x-ui.button>
        </div>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Buku</th>
                    <th scope="col" class="px-5 py-3">Penulis</th>
                    <th scope="col" class="px-5 py-3">Kategori</th>
                    <th scope="col" class="px-5 py-3">ISBN</th>
                    <th scope="col" class="px-5 py-3">Eksemplar</th>
                    <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @forelse ($books as $book)
                <tr>
                    <td class="px-5 py-3.5">
                        <a href="{{ route('admin.books.show', $book) }}" class="font-semibold text-gray-900 hover:underline">{{ $book->title }}</a>
                        <p class="mt-1 text-xs text-gray-500">{{ $book->year }} · {{ $book->publisher->name }}</p>
                    </td>
                    <td class="px-5 py-3.5">{{ $book->authors->pluck('name')->join(', ') }}</td>
                    <td class="px-5 py-3.5">{{ $book->category->name }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $book->isbn ?: '—' }}</td>
                    <td class="px-5 py-3.5">{{ $book->copies_count }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                        <a href="{{ route('admin.books.edit', $book) }}" class="font-semibold text-gray-700 hover:underline">Ubah</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Buku tidak ditemukan.</td></tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$books" />
    </x-ui.card>
</x-admin-layout>