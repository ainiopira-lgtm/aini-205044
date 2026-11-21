<x-admin-layout title="Dashboard">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Ringkasan perpustakaan</p>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card>
            <p class="text-sm font-medium text-gray-500">Judul buku</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($books->total()) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-gray-500">Kategori</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($categoryCount) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-gray-500">Eksemplar</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($copyCount) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm font-medium text-gray-500">Status katalog</p>
            <div class="mt-3"><x-ui.status-badge status="available" label="Aktif" /></div>
        </x-ui.card>
    </div>

    <x-ui.flash-message type="info" message="Contoh pesan flash. Pesan sesi success, error, warning, dan info juga ditampilkan otomatis." />

    <x-ui.card title="Komponen form" description="Contoh input, select, dan tombol dengan gaya yang konsisten.">
        <form class="grid gap-4 md:grid-cols-[1fr_1fr_auto]" @submit.prevent>
            <x-ui.input name="sample_title" label="Judul buku" placeholder="Cari judul buku" />
            <x-ui.select
                name="sample_category"
                label="Kategori"
                :options="$categories"
                placeholder="Semua kategori"
            />
            <div class="flex items-end gap-2">
                <x-ui.button type="submit">Terapkan</x-ui.button>
                <x-ui.button variant="secondary" type="reset">Reset</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card title="Koleksi terbaru" description="Contoh tabel, badge status, dan pagination.">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Judul</th>
                    <th scope="col" class="px-5 py-3">Penulis</th>
                    <th scope="col" class="px-5 py-3">Kategori</th>
                    <th scope="col" class="px-5 py-3">Eksemplar tersedia</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                </tr>
            </x-slot:head>
            @forelse ($books as $book)
                <tr>
                    <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900">{{ $book->title }}</td>
                    <td class="px-5 py-3.5">{{ $book->authors->pluck('name')->join(', ') ?: '—' }}</td>
                    <td class="px-5 py-3.5">{{ $book->category->name }}</td>
                    <td class="px-5 py-3.5">{{ $book->available_copies_count }}</td>
                    <td class="px-5 py-3.5">
                        <x-ui.status-badge
                            :status="$book->available_copies_count > 0 ? 'available' : 'borrowed'"
                            :label="$book->available_copies_count > 0 ? 'Tersedia' : 'Tidak tersedia'"
                        />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-gray-500">Belum ada data buku. Jalankan seeder untuk memuat contoh katalog.</td>
                </tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$books" />
    </x-ui.card>

    <x-ui.card title="Modal konfirmasi" description="Contoh dialog konfirmasi untuk tindakan yang berisiko.">
        <x-ui.confirmation-modal
            name="sample-delete"
            title="Hapus buku?"
            message="Tindakan ini tidak dapat dibatalkan. Ini hanya contoh dialog."
            confirm-text="Ya, hapus"
        >
            <x-slot:trigger>
                <x-ui.button variant="danger">Hapus buku</x-ui.button>
            </x-slot:trigger>
        </x-ui.confirmation-modal>
    </x-ui.card>
</x-admin-layout>
