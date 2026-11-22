<x-admin-layout title="Denda">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen sirkulasi</p>
            <h1 class="text-xl font-semibold text-gray-900">Denda</h1>
        </div>
    </x-slot>

    <x-ui.card>
        <form method="GET" action="{{ route('admin.fines.index') }}" class="mb-5 flex flex-wrap items-end gap-3">
            <div>
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Status pembayaran</label>
                <select id="status" name="status" class="block rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="">Semua status</option>
                    <option value="unpaid" @selected($status === 'unpaid')>Belum dibayar</option>
                    <option value="paid" @selected($status === 'paid')>Lunas</option>
                </select>
            </div>
            <x-ui.button type="submit">Terapkan</x-ui.button>
        </form>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Anggota</th>
                    <th scope="col" class="px-5 py-3">Buku</th>
                    <th scope="col" class="px-5 py-3">Hari terlambat</th>
                    <th scope="col" class="px-5 py-3">Jumlah</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3">Dibayar pada</th>
                    <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @forelse ($fines as $fine)
                <tr>
                    <td class="px-5 py-3.5">
                        <p class="font-medium text-gray-900">{{ $fine->loan->member->name }}</p>
                        <p class="text-xs text-gray-500">{{ $fine->loan->member->membership_number }}</p>
                    </td>
                    <td class="px-5 py-3.5">{{ $fine->loan->bookCopy->book->title }}</td>
                    <td class="px-5 py-3.5">{{ $fine->days_late }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">Rp {{ number_format((float) $fine->amount, 0, ',', '.') }}</td>
                    <td class="px-5 py-3.5">
                        <x-ui.status-badge :status="$fine->status" :label="$fine->status === 'paid' ? 'Lunas' : 'Belum dibayar'" />
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $fine->paid_at?->format('d M Y H:i') ?: '—' }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                        @if ($fine->status === 'unpaid')
                            <form method="POST" action="{{ route('admin.fines.pay', $fine) }}" onsubmit="return confirm('Catat pembayaran denda ini?')">
                                @csrf
                                <x-ui.button type="submit" size="sm">Bayar</x-ui.button>
                            </form>
                        @else
                            <x-ui.button :href="route('admin.fines.receipt', $fine)" variant="secondary" size="sm">Cetak struk PDF</x-ui.button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-10 text-center text-gray-500">Belum ada data denda.</td></tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$fines" />
    </x-ui.card>
</x-admin-layout>
