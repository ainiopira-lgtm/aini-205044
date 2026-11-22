<x-public-layout title="Katalog buku">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-7">
            <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">Jelajahi koleksi</p>
            <h1 class="mt-1 text-3xl font-bold text-gray-900">Katalog buku</h1>
        </div>

        <form method="GET" action="{{ route('catalog.index') }}" class="mb-8 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-[minmax(14rem,1.3fr)_repeat(3,minmax(10rem,1fr))_auto] lg:items-end">
            <div>
                <label for="q" class="mb-1.5 block text-sm font-medium text-gray-700">Pencarian</label>
                <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Judul, penulis, ISBN" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
            </div>
            <div>
                <label for="category_id" class="mb-1.5 block text-sm font-medium text-gray-700">Kategori</label>
                <select id="category_id" name="category_id" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="author_id" class="mb-1.5 block text-sm font-medium text-gray-700">Penulis</label>
                <select id="author_id" name="author_id" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">Semua penulis</option>
                    @foreach ($authors as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['author_id'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="year" class="mb-1.5 block text-sm font-medium text-gray-700">Tahun terbit</label>
                <select id="year" name="year" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">Semua tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <x-ui.button type="submit">Cari</x-ui.button>
                <x-ui.button href="{{ route('catalog.index') }}" variant="secondary">Reset</x-ui.button>
            </div>
        </form>

        <p class="mb-4 text-sm text-gray-500">{{ number_format($books->total()) }} buku ditemukan</p>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($books as $book)
                <a href="{{ route('catalog.show', $book) }}" class="group overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg">
                    @if ($book->cover_url)
                        <img src="{{ $book->cover_url }}" alt="Sampul {{ $book->title }}" class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="grid aspect-[4/3] place-items-center bg-slate-100 text-3xl font-semibold text-slate-400" aria-hidden="true">B</div>
                    @endif
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="line-clamp-2 font-semibold text-gray-900 group-hover:underline">{{ $book->title }}</h2>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">{{ $book->year }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-gray-500">{{ $book->authors->pluck('name')->join(', ') ?: 'Penulis tidak diketahui' }}</p>
                        <p class="mt-3 text-xs text-gray-500">{{ $book->category->name }} · {{ $book->available_copies_count }} tersedia</p>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 sm:col-span-2 lg:col-span-3 xl:col-span-4">Buku tidak ditemukan. Coba ubah kata kunci atau filter.</div>
            @endforelse
        </div>
        <div class="mt-8">
            <x-ui.pagination :paginator="$books" />
        </div>
    </section>
</x-public-layout>
