<x-admin-layout :title="$book->title">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Katalog buku</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $book->title }}</h1>
        </div>
    </x-slot>

    <x-ui.card>
        <div class="grid gap-6 md:grid-cols-[12rem_1fr]">
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Sampul {{ $book->title }}" class="aspect-[3/4] w-full rounded-xl border border-gray-200 object-cover md:max-w-48">
            @else
                <div class="grid aspect-[3/4] w-full place-items-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 text-center text-sm text-gray-500 md:max-w-48">Sampul belum tersedia</div>
            @endif
            <div>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $book->title }}</h2>
                        <p class="mt-2 text-gray-600">{{ $book->authors->pluck('name')->join(', ') }}</p>
                    </div>
                    <x-ui.button :href="route('admin.books.edit', $book)" variant="secondary">Ubah buku</x-ui.button>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">ISBN</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->isbn ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kategori</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->category->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penerbit</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->publisher->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rak</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->shelf->code }} · {{ $book->shelf->name }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tahun</dt><dd class="mt-1 text-sm text-gray-900">{{ $book->year }}</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Harga</dt><dd class="mt-1 text-sm text-gray-900">Rp {{ number_format((float) $book->price, 2, ',', '.') }}</dd></div>
                </dl>
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-900">Sinopsis</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $book->synopsis }}</p>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Eksemplar" description="Barcode dibuat otomatis berdasarkan nomor eksemplar.">
        <div class="mb-5">
            <form method="POST" action="{{ route('admin.books.copies.store', $book) }}">
                @csrf
                <x-ui.button type="submit">Tambah eksemplar</x-ui.button>
            </form>
        </div>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Barcode</th>
                    <th scope="col" class="px-5 py-3">Kondisi</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @forelse ($copies as $copy)
                <tr>
                    <td class="whitespace-nowrap px-5 py-3.5 font-mono text-sm text-gray-900">{{ $copy->barcode }}</td>
                    <td class="px-5 py-3.5">{{ ucfirst($copy->condition) }}</td>
                    <td class="px-5 py-3.5"><x-ui.status-badge :status="$copy->status" /></td>
                    <td class="px-5 py-3.5">
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <form method="POST" action="{{ route('admin.books.copies.update', [$book, $copy]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="copy-status-{{ $copy->id }}" class="sr-only">Status {{ $copy->barcode }}</label>
                                <select id="copy-status-{{ $copy->id }}" name="status" class="rounded-lg border-gray-300 text-sm focus:border-gray-500 focus:ring-gray-500">
                                    @foreach (['available' => 'Tersedia', 'borrowed' => 'Dipinjam', 'damaged' => 'Rusak', 'lost' => 'Hilang'] as $status => $label)
                                        <option value="{{ $status }}" @selected($copy->status === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-ui.button type="submit" size="sm" variant="secondary">Ubah</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('admin.books.copies.destroy', [$book, $copy]) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" size="sm" variant="danger">Hapus</x-ui.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Belum ada eksemplar untuk buku ini.</td></tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$copies" />
    </x-ui.card>

    <div class="flex flex-wrap gap-3">
        <x-ui.button href="{{ route('admin.books.index') }}" variant="secondary">Kembali ke katalog</x-ui.button>
        @if ($book->copies()->doesntExist() && $book->reservations()->doesntExist())
            <x-ui.confirmation-modal
                name="book-{{ $book->id }}-delete"
                title="Hapus buku?"
                message="Buku dan relasi penulisnya akan dihapus."
                confirm-text="Ya, hapus"
                :action="route('admin.books.destroy', $book)"
            >
                <x-slot:trigger><x-ui.button variant="danger">Hapus buku</x-ui.button></x-slot:trigger>
            </x-ui.confirmation-modal>
        @else
            <p class="self-center text-sm text-gray-500">Buku dengan eksemplar atau reservasi tidak dapat dihapus.</p>
        @endif
    </div>
</x-admin-layout>