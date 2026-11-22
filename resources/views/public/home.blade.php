<x-public-layout title="Selamat datang">
    <section class="overflow-hidden bg-slate-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)] lg:items-center lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">Perpustakaan PustakaKu</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-bold tracking-tight sm:text-6xl">Satu buku bisa membuka dunia baru.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Jelajahi koleksi dan temukan bacaan yang cocok untuk perjalananmu berikutnya.</p>
                <form method="GET" action="{{ route('catalog.index') }}" class="mt-8 flex max-w-2xl flex-col gap-3 rounded-2xl bg-white p-2 sm:flex-row">
                    <label class="sr-only" for="home-search">Cari buku</label>
                    <input id="home-search" name="q" type="search" placeholder="Cari judul, penulis, atau ISBN..." class="min-w-0 flex-1 border-0 text-gray-900 shadow-none focus:ring-0">
                    <x-ui.button type="submit">Cari buku</x-ui.button>
                </form>
                <div class="mt-5 flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('catalog.index') }}" variant="secondary">Jelajahi katalog</x-ui.button>
                    @guest
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10">Masuk anggota</a>
                    @endguest
                </div>
            </div>
            <div class="relative hidden min-h-72 items-center justify-center lg:flex">
                <div class="absolute size-64 rounded-full bg-amber-400/20 blur-3xl"></div>
                <div class="relative grid size-56 place-items-center rounded-[2rem] border border-white/15 bg-white/10 shadow-2xl backdrop-blur">
                    <div class="text-center">
                        <svg class="mx-auto size-16 text-amber-300" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                            <path d="M12 14h18a7 7 0 0 1 7 7v31a7 7 0 0 0-7-7H12V14Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M52 14H34a7 7 0 0 0-7 7v31a7 7 0 0 1 7-7h18V14Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                            <path d="M17 23h10m-10 7h10m20-7h-8m8 7h-8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                        </svg>
                        <p class="mt-4 text-sm font-medium tracking-wide text-slate-200">Ruang untuk membaca<br>dan bertumbuh</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">Pilihan terbaru</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">Buku yang baru ditambahkan</h2>
            </div>
            <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-gray-700 hover:underline">Lihat semua katalog</a>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($books as $book)
                <a href="{{ route('catalog.show', $book) }}" class="group overflow-hidden rounded-xl border border-gray-200 bg-white transition hover:-translate-y-0.5 hover:shadow-lg">
                    @if ($book->cover_url)
                        <img src="{{ $book->cover_url }}" alt="Sampul {{ $book->title }}" class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="grid aspect-[4/3] place-items-center bg-slate-100 text-4xl text-slate-400" aria-hidden="true">▤</div>
                    @endif
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $book->category->name }}</p>
                        <h3 class="mt-2 line-clamp-2 font-semibold text-gray-900 group-hover:underline">{{ $book->title }}</h3>
                        <p class="mt-1 truncate text-sm text-gray-500">{{ $book->authors->pluck('name')->join(', ') ?: 'Penulis tidak diketahui' }}</p>
                        <p class="mt-4 text-xs text-gray-500">{{ $book->available_copies_count }} eksemplar tersedia</p>
                    </div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-sm text-gray-500 sm:col-span-2 lg:col-span-4">Koleksi buku belum tersedia.</div>
            @endforelse
        </div>
    </section>
</x-public-layout>
