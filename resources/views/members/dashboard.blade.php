<x-admin-layout title="Portal anggota">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Layanan anggota</p>
            <h1 class="text-xl font-semibold text-gray-900">Halo, {{ $member?->name ?? auth()->user()->name }}</h1>
        </div>
    </x-slot>

    @if (! $member)
        <x-ui.card title="Profil anggota belum tersedia">
            <p class="text-sm text-gray-600">Akun Anda belum terhubung ke data anggota. Silakan hubungi pustakawan untuk bantuan.</p>
        </x-ui.card>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            <x-ui.card>
                <p class="text-sm font-medium text-gray-500">Pinjaman aktif</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $activeLoans->count() }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm font-medium text-gray-500">Denda belum lunas</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">Rp {{ number_format($unpaidFineTotal, 0, ',', '.') }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm font-medium text-gray-500">Nomor anggota</p>
                <p class="mt-2 text-lg font-bold text-gray-900">{{ $member->membership_number }}</p>
            </x-ui.card>
        </div>

        <x-ui.card title="Pinjaman aktif" description="Periksa tanggal jatuh tempo dan ajukan perpanjangan jika memenuhi syarat.">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Buku</th>
                        <th scope="col" class="px-5 py-3">Dipinjam</th>
                        <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($activeLoans as $loan)
                    @php($isLate = $loan->status === 'overdue' || $loan->due_date->lt(today()))
                    <tr>
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-gray-900">{{ $loan->bookCopy->book->title }}</p>
                            <p class="text-xs text-gray-500">{{ $loan->bookCopy->barcode }}</p>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->loan_date->format('d M Y') }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->due_date->format('d M Y') }}</td>
                        <td class="px-5 py-3.5">
                            <x-ui.status-badge :status="$isLate ? 'overdue' : 'active'" :label="$isLate ? 'Terlambat' : ($loan->due_date->isToday() ? 'Jatuh tempo hari ini' : 'Dipinjam')" />
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @if (! $isLate && $loan->status === 'active' && $loan->extension_count < $maxRenewals)
                                <form method="POST" action="{{ route('member.loans.renew', $loan) }}">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" variant="secondary">Perpanjang</x-ui.button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">Tidak dapat diperpanjang</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">Tidak ada pinjaman aktif.</td></tr>
                @endforelse
            </x-ui.table>
            @error('loan')
                <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </x-ui.card>

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <x-ui.card title="Riwayat pinjam">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-4 py-3">Buku</th>
                            <th scope="col" class="px-4 py-3">Tanggal pinjam</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($history as $loan)
                        <tr>
                            <td class="px-4 py-3">{{ $loan->bookCopy->book->title }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $loan->loan_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ ucfirst($loan->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada riwayat pinjam.</td></tr>
                    @endforelse
                </x-ui.table>
                <div class="mt-4"><x-ui.pagination :paginator="$history" /></div>
            </x-ui.card>

            <x-ui.card title="Daftar denda">
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-4 py-3">Buku</th>
                            <th scope="col" class="px-4 py-3">Jumlah</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                        </tr>
                    </x-slot:head>
                    @forelse ($fines as $fine)
                        <tr>
                            <td class="px-4 py-3">{{ $fine->loan->bookCopy->book->title }}</td>
                            <td class="whitespace-nowrap px-4 py-3">Rp {{ number_format((float) $fine->amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><x-ui.status-badge :status="$fine->status" :label="$fine->status === 'paid' ? 'Lunas' : 'Belum lunas'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">Belum ada denda.</td></tr>
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>

        <x-ui.card title="Reservasi buku">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Buku</th>
                        <th scope="col" class="px-5 py-3">Tanggal reservasi</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Batas pengambilan</th>
                    </tr>
                </x-slot:head>
                @forelse ($reservations as $reservation)
                    @php($reservationStatus = $reservation->status === 'ready' && $reservation->expires_at?->isPast() ? 'expired' : $reservation->status)
                    <tr>
                        <td class="px-5 py-3.5">{{ $reservation->book->title }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $reservation->reserved_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-3.5">{{ ucfirst($reservationStatus) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">{{ $reservation->status === 'ready' ? $reservation->expires_at?->format('d M Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">Belum ada reservasi.</td></tr>
                @endforelse
            </x-ui.table>
            <div class="mt-4">
                <x-ui.button href="{{ route('catalog.index') }}" variant="secondary">Jelajahi katalog</x-ui.button>
            </div>
        </x-ui.card>
    @endif
</x-admin-layout>
