<x-admin-layout title="Dashboard">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Ringkasan perpustakaan</p>
            <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total buku', 'value' => number_format($totalBooks), 'color' => 'text-indigo-600'],
            ['label' => 'Anggota aktif', 'value' => number_format($activeMembers), 'color' => 'text-emerald-600'],
            ['label' => 'Sedang dipinjam', 'value' => number_format($currentLoans), 'color' => 'text-sky-600'],
            ['label' => 'Terlambat', 'value' => number_format($overdueLoans), 'color' => 'text-rose-600'],
            ['label' => 'Denda bulan ini', 'value' => 'Rp '.number_format($finesThisMonth, 0, ',', '.'), 'color' => 'text-amber-600'],
        ] as $stat)
            <x-ui.card>
                <p class="text-sm font-medium text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </x-ui.card>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <x-ui.card title="Peminjaman 30 hari terakhir" description="Jumlah transaksi peminjaman per hari.">
            <div class="h-72">
                <canvas
                    id="loans-chart"
                    data-labels='@json($loanChartLabels)'
                    data-values='@json($loanChartValues)'
                    role="img"
                    aria-label="Grafik jumlah peminjaman selama 30 hari terakhir"
                ></canvas>
            </div>
        </x-ui.card>

        <x-ui.card title="Buku terpopuler" description="Peminjaman terbanyak dalam 30 hari terakhir.">
            <ol class="divide-y divide-gray-100">
                @forelse ($popularBooks as $book)
                    <li class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <span class="font-medium text-gray-800">{{ $book->title }}</span>
                        <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            {{ number_format($book->loan_count) }} pinjam
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">Belum ada data peminjaman dalam 30 hari terakhir.</li>
                @endforelse
            </ol>
        </x-ui.card>
    </div>

    <x-ui.card title="Jatuh tempo hari ini" description="Pinjaman yang belum dikembalikan dan jatuh tempo pada hari ini.">
        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Anggota</th>
                    <th scope="col" class="px-5 py-3">No. anggota</th>
                    <th scope="col" class="px-5 py-3">Buku</th>
                    <th scope="col" class="px-5 py-3">Barcode</th>
                    <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                </tr>
            </x-slot:head>
            @forelse ($dueTodayLoans as $loan)
                <tr>
                    <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900">{{ $loan->member->name }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->member->membership_number }}</td>
                    <td class="px-5 py-3.5">{{ $loan->bookCopy->book->title }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->bookCopy->barcode }}</td>
                    <td class="whitespace-nowrap px-5 py-3.5">{{ $loan->due_date->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-gray-500">Tidak ada pinjaman yang jatuh tempo hari ini.</td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</x-admin-layout>
