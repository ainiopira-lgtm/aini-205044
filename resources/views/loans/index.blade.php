<x-admin-layout title="Sirkulasi">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Peminjaman dan pengembalian</p>
            <h1 class="text-xl font-semibold text-gray-900">Sirkulasi</h1>
        </div>
    </x-slot>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <div class="space-y-6">
            <x-ui.card title="Peminjaman" description="Cari atau scan anggota, lalu scan barcode eksemplar yang akan dipinjam.">
                <form method="GET" action="{{ route('admin.circulation.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <x-ui.input name="member" label="Anggota" :value="$search" placeholder="Nomor anggota, nama, atau email" autofocus />
                    </div>
                    <x-ui.button type="submit">Cari anggota</x-ui.button>
                </form>

                @if ($search !== '')
                    <div class="mt-4 space-y-2">
                        @forelse ($members as $result)
                            <form method="POST" action="{{ route('admin.circulation.members.select') }}" class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-3">
                                @csrf
                                <input type="hidden" name="member_id" value="{{ $result->id }}">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $result->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $result->membership_number }} · {{ $result->email }}</p>
                                </div>
                                <x-ui.button type="submit" variant="secondary" size="sm">Pilih</x-ui.button>
                            </form>
                        @empty
                            <p class="rounded-lg bg-gray-50 p-3 text-sm text-gray-500">Anggota tidak ditemukan.</p>
                        @endforelse
                    </div>
                @endif

                @if ($selectedMember)
                    @php($member = $selectedMember['member'])
                    <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $member->name }}</p>
                                <p class="text-sm text-gray-500">{{ $member->membership_number }} · {{ $member->email }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                {{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <p>Sisa kuota setelah daftar ini: <strong>{{ $selectedMember['remaining_quota'] }}</strong> dari {{ $selectedMember['max_active_loans'] }}</p>
                            <p>
                                Denda:
                                @if ($selectedMember['has_unpaid_fines'])
                                    <strong class="text-red-700">Belum lunas (Rp {{ number_format($selectedMember['unpaid_fine_total'], 0, ',', '.') }})</strong>
                                @else
                                    <strong class="text-emerald-700">Tidak ada tunggakan</strong>
                                @endif
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.circulation.copies.add') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <x-ui.input name="barcode" label="Barcode eksemplar" placeholder="Scan barcode buku" autofocus />
                        </div>
                        <x-ui.button type="submit" variant="secondary">Tambah ke daftar</x-ui.button>
                    </form>
                    @error('barcode')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('member')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-5">
                        <h3 class="mb-3 text-sm font-semibold text-gray-800">Daftar sementara ({{ $draftCopies->count() }})</h3>
                        <x-ui.table>
                            <x-slot:head>
                                <tr>
                                    <th scope="col" class="px-4 py-3">Buku</th>
                                    <th scope="col" class="px-4 py-3">Barcode</th>
                                    <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </x-slot:head>
                            @forelse ($draftCopies as $copy)
                                <tr>
                                    <td class="px-4 py-3">{{ $copy->book->title }}</td>
                                    <td class="px-4 py-3">{{ $copy->barcode }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.circulation.copies.remove') }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="copy_id" value="{{ $copy->id }}">
                                            <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada eksemplar di daftar.</td></tr>
                            @endforelse
                        </x-ui.table>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.circulation.loans.store') }}">
                            @csrf
                            <x-ui.button type="submit" :disabled="$draftCopies->isEmpty() || $selectedMember['has_unpaid_fines'] || $member->status !== 'active'">Simpan peminjaman</x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('admin.circulation.draft.clear') }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="secondary">Batal</x-ui.button>
                        </form>
                    </div>
                    @error('copies')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @else
                    <p class="mt-5 rounded-lg bg-gray-50 p-4 text-sm text-gray-500">Pilih anggota untuk melihat kuota dan memulai peminjaman.</p>
                    @error('member')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Pengembalian" description="Scan barcode eksemplar untuk menyelesaikan peminjaman aktif.">
                <form method="POST" action="{{ route('admin.circulation.returns.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-ui.input name="return_barcode" label="Barcode eksemplar" placeholder="Scan barcode saat pengembalian" autofocus />
                    </div>
                    <x-ui.button type="submit">Proses pengembalian</x-ui.button>
                </form>
                @error('return_barcode')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </x-ui.card>

            <x-ui.card title="Perpanjangan" description="Perpanjang peminjaman aktif yang belum terlambat dan tidak memiliki reservasi.">
                <form method="POST" action="{{ route('admin.circulation.renewals.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <x-ui.input name="renew_barcode" label="Barcode eksemplar" placeholder="Scan barcode untuk perpanjangan" autofocus />
                    </div>
                    <x-ui.button type="submit" variant="secondary">Perpanjang</x-ui.button>
                </form>
                @error('renew_barcode')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </x-ui.card>

            <x-ui.card title="Peminjaman aktif" description="Daftar 10 pinjaman dengan jatuh tempo terdekat.">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-4 py-3">Anggota / buku</th>
                            <th scope="col" class="px-4 py-3">Barcode</th>
                            <th scope="col" class="px-4 py-3">Jatuh tempo</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($activeLoans as $loan)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $loan->member->name }}</p>
                                <p class="text-xs text-gray-500">{{ $loan->bookCopy->book->title }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $loan->bookCopy->barcode }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $loan->due_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada peminjaman aktif.</td></tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>