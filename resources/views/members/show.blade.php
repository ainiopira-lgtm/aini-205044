<x-admin-layout :title="$member->name">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Detail anggota</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $member->name }}</h1>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <x-ui.card title="Informasi anggota">
            <div class="flex flex-col gap-5 sm:flex-row">
                @if ($member->photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->photo) }}" alt="Foto {{ $member->name }}" class="size-28 rounded-xl object-cover">
                @else
                    <div class="grid size-28 shrink-0 place-items-center rounded-xl bg-gray-100 text-3xl font-semibold text-gray-500">{{ mb_substr($member->name, 0, 1) }}</div>
                @endif
                <dl class="grid flex-1 gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Nomor anggota</dt><dd class="mt-1 font-semibold">{{ $member->membership_number }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd class="mt-1"><x-ui.status-badge :status="$member->status">{{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}</x-ui.status-badge></dd></div>
                    <div><dt class="text-sm text-gray-500">Email</dt><dd class="mt-1">{{ $member->email }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Telepon</dt><dd class="mt-1">{{ $member->phone ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Tanggal bergabung</dt><dd class="mt-1">{{ $member->joined_at?->format('d M Y') ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Alamat</dt><dd class="mt-1">{{ $member->address ?: '—' }}</dd></div>
                </dl>
            </div>
            <div class="mt-6 flex flex-wrap gap-3 border-t border-gray-100 pt-5">
                <x-ui.button :href="route('admin.members.edit', $member)" variant="secondary">Ubah data</x-ui.button>
                <x-ui.button :href="route('admin.members.card', $member)" variant="secondary">Cetak kartu PDF</x-ui.button>
                <form method="POST" action="{{ route('admin.members.destroy', $member) }}" onsubmit="return confirm('Hapus data anggota ini?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Hapus anggota</x-ui.button>
                </form>
            </div>
        </x-ui.card>

        <x-ui.card title="Total denda">
            <p class="text-3xl font-semibold text-gray-900">Rp {{ number_format((float) $totalFine, 0, ',', '.') }}</p>
            <p class="mt-2 text-sm text-gray-500">Akumulasi seluruh denda pinjaman anggota.</p>
        </x-ui.card>
    </div>

    <x-ui.card title="Riwayat peminjaman">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Buku</th>
                    <th scope="col" class="px-5 py-3">Tanggal pinjam</th>
                    <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                    <th scope="col" class="px-5 py-3">Tanggal kembali</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                </tr>
            </x-slot:head>
            @forelse ($member->loans as $loan)
                <tr>
                    <td class="px-5 py-3.5">{{ $loan->bookCopy?->book?->title ?? 'Buku tidak tersedia' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->loan_date?->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->due_date?->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->return_date?->format('d M Y') ?: '—' }}</td>
                    <td class="px-5 py-3.5">{{ ucfirst($loan->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">Belum ada riwayat peminjaman.</td></tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</x-admin-layout>
