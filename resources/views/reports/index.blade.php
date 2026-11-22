<x-admin-layout title="Laporan">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Analisis dan ekspor data</p>
            <h1 class="text-xl font-semibold text-gray-900">Laporan</h1>
        </div>
    </x-slot>

    <x-ui.card title="Pilih laporan" description="Atur jenis laporan dan rentang tanggal untuk melihat atau mengunduh data.">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 md:grid-cols-4 md:items-end">
            <x-ui.select name="report" label="Jenis laporan" :options="$reportTypes" :value="$reportType" />
            <x-ui.input name="from" type="date" label="Dari tanggal" :value="$fromDate->toDateString()" />
            <x-ui.input name="to" type="date" label="Sampai tanggal" :value="$toDate->toDateString()" />
            <x-ui.button type="submit">Tampilkan</x-ui.button>
        </form>

        <p class="mt-4 text-sm text-gray-500">
            Rentang tanggal berlaku untuk laporan transaksi dan buku terpopuler. Inventaris menunjukkan kondisi eksemplar saat ini.
        </p>
    </x-ui.card>

    <x-ui.card :title="$report['title']" description="Periode {{ $fromDate->format('d/m/Y') }}–{{ $toDate->format('d/m/Y') }}">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('admin.reports.export', ['format' => 'pdf', 'report' => $reportType, 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >Unduh PDF</a>
                <a
                    href="{{ route('admin.reports.export', ['format' => 'xlsx', 'report' => $reportType, 'from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()]) }}"
                    class="inline-flex items-center rounded-lg bg-emerald-700 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-800"
                >Unduh Excel</a>
            </div>
        </x-slot:actions>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    @foreach ($report['headers'] as $heading)
                        <th scope="col" class="whitespace-nowrap px-5 py-3">{{ $heading }}</th>
                    @endforeach
                </tr>
            </x-slot:head>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td class="whitespace-nowrap px-5 py-3.5">
                            @if (is_float($value) && str_contains(strtolower($report['headers'][$index]), '(rp)'))
                                Rp {{ number_format($value, 0, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['headers']) }}" class="px-5 py-10 text-center text-gray-500">
                        Tidak ada data untuk laporan dan rentang tanggal ini.
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</x-admin-layout>
