<x-public-layout :title="$book->title">
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-gray-600 hover:underline">Kembali ke katalog</a>
        <div class="mt-5 grid gap-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8 lg:grid-cols-[18rem_1fr]">
            <div>
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="Sampul {{ $book->title }}" class="aspect-[3/4] w-full rounded-xl object-cover">
                @else
                    <div class="grid aspect-[3/4] place-items-center rounded-xl bg-slate-100 text-5xl font-semibold text-slate-400" aria-hidden="true">B</div>
                @endif
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-500">{{ $book->category->name }} · {{ $book->year }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-950">{{ $book->title }}</h1>
                <p class="mt-2 text-lg text-gray-600">{{ $book->authors->pluck('name')->join(', ') ?: 'Penulis tidak diketahui' }}</p>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">ISBN</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->isbn ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penerbit</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->publisher->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Eksemplar tersedia</dt><dd class="mt-1 text-lg font-semibold text-gray-900">{{ $book->available_copies_count }} dari {{ $book->copies_count }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rak</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->shelf->code }} · {{ $book->shelf->name }}</dd></div>
                </dl>

                @if ($book->available_copies_count > 0)
                    <p class="mt-5 inline-flex rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">Tersedia untuk dipinjam</p>
                @elseif ($book->copies_count === 0)
                    <p class="mt-5 inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-600">Belum ada eksemplar</p>
                @else
                    <p class="mt-5 inline-flex rounded-full bg-amber-50 px-3 py-1.5 text-sm font-semibold text-amber-700">Tidak tersedia saat ini</p>
                @endif

                @if ($canReserve)
                    <form method="POST" action="{{ route('catalog.reservations.store', $book) }}" class="mt-5">
                        @csrf
                        <x-ui.button type="submit">Reservasi buku</x-ui.button>
                        <p class="mt-2 text-xs text-gray-500">Jika buku kembali, reservasi pertama dalam antrean siap diambil selama 2 hari.</p>
                    </form>
                @elseif ($hasActiveReservation)
                    <p class="mt-5 text-sm font-medium text-indigo-700">Anda sudah berada dalam antrean reservasi buku ini.</p>
                @elseif (auth()->check() && auth()->user()->hasRole('anggota') && $book->copies_count > 0 && $borrowedCopiesCount === $book->copies_count)
                    <p class="mt-5 text-sm text-gray-500">Reservasi hanya dapat dibuat oleh anggota aktif yang belum memiliki reservasi untuk buku ini.</p>
                @elseif (! auth()->check() && $book->copies_count > 0 && $borrowedCopiesCount === $book->copies_count)
                    <a href="{{ route('login') }}" class="mt-5 inline-flex text-sm font-semibold text-gray-700 hover:underline">Masuk sebagai anggota untuk reservasi</a>
                @endif

                <div class="mt-8 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-900">Sinopsis</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $book->synopsis }}</p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
