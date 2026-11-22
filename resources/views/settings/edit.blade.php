<x-admin-layout title="Pengaturan">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Administrasi perpustakaan</p>
            <h1 class="text-xl font-semibold text-gray-900">Pengaturan sirkulasi</h1>
        </div>
    </x-slot>

    <x-ui.card class="max-w-3xl" title="Aturan peminjaman">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <x-ui.input
                name="loan_duration_days"
                label="Lama pinjam (hari)"
                type="number"
                min="1"
                :value="old('loan_duration_days', $settings['loan_duration_days'])"
                required
            />
            <x-ui.input
                name="max_active_loans"
                label="Maksimal buku per anggota"
                type="number"
                min="1"
                :value="old('max_active_loans', $settings['max_active_loans'])"
                required
            />
            <x-ui.input
                name="max_renewals"
                label="Maksimal perpanjangan"
                type="number"
                min="0"
                :value="old('max_renewals', $settings['max_renewals'])"
                required
            />
            <x-ui.input
                name="fine_per_day"
                label="Tarif denda per hari (Rp)"
                type="number"
                min="0"
                :value="old('fine_per_day', $settings['fine_per_day'])"
                required
            />
            <p class="text-sm text-gray-500">Perubahan disimpan ke tabel pengaturan dan langsung diterapkan setelah cache diperbarui.</p>

            <x-ui.button type="submit">Simpan pengaturan</x-ui.button>
        </form>
    </x-ui.card>
</x-admin-layout>
