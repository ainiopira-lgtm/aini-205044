<x-admin-layout title="Impor buku">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">Impor buku</h1>
        </div>
    </x-slot>

    <x-ui.card title="Unggah file" description="Format yang didukung: Excel (.xlsx, .xls) dan CSV. Ukuran maksimal 5 MB.">
        <p class="mb-5 text-sm leading-6 text-gray-600">
            Gunakan baris judul kolom berikut: <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">title, isbn, category_id, publisher_id, shelf_id, year, synopsis, price, author_ids</code>.
            ID kategori, penerbit, rak, dan penulis harus sudah tersedia. Pisahkan beberapa ID penulis dengan tanda <code>|</code>; kolom harga dan ISBN boleh kosong.
        </p>

        <form method="POST" action="{{ route('admin.books.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-ui.input name="file" type="file" label="File Excel atau CSV" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">Impor buku</x-ui.button>
                <x-ui.button href="{{ route('admin.books.index') }}" variant="secondary">Kembali ke katalog</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @if (session('import_report'))
        @php($report = session('import_report'))
        <x-ui.card title="Hasil impor">
            <p class="mb-4 text-sm text-gray-700">
                {{ number_format($report['imported']) }} buku berhasil diimpor.
                {{ number_format(count($report['failures'])) }} baris gagal.
            </p>

            @if ($report['failures'] !== [])
                <x-ui.table>
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-5 py-3">Baris</th>
                            <th scope="col" class="px-5 py-3">Judul</th>
                            <th scope="col" class="px-5 py-3">ISBN</th>
                            <th scope="col" class="px-5 py-3">Kesalahan</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($report['failures'] as $failure)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3.5">{{ $failure['row'] }}</td>
                            <td class="px-5 py-3.5">{{ $failure['title'] ?: '—' }}</td>
                            <td class="px-5 py-3.5">{{ $failure['isbn'] ?: '—' }}</td>
                            <td class="px-5 py-3.5">
                                <ul class="list-inside list-disc">
                                    @foreach ($failure['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    @endif
</x-admin-layout>
