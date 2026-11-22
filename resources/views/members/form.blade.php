<x-admin-layout :title="$member ? 'Ubah anggota' : 'Tambah anggota'">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen sirkulasi</p>
            <h1 class="text-xl font-semibold text-gray-900">{{ $member ? 'Ubah anggota' : 'Tambah anggota' }}</h1>
        </div>
    </x-slot>

    <x-ui.card class="max-w-3xl" title="{{ $member ? 'Ubah data anggota' : 'Data anggota baru' }}">
        <form
            method="POST"
            action="{{ $member ? route('admin.members.update', $member) : route('admin.members.store') }}"
            enctype="multipart/form-data"
            class="space-y-5"
        >
            @csrf
            @if ($member)
                @method('PUT')
            @endif

            <x-ui.input name="name" label="Nama lengkap" :value="old('name', $member?->name)" required />
            <x-ui.input name="email" label="Email (untuk akun anggota)" type="email" :value="old('email', $member?->email)" required />
            <x-ui.input name="phone" label="Telepon" :value="old('phone', $member?->phone)" />
            <div>
                <label for="address" class="mb-1.5 block text-sm font-medium text-gray-700">Alamat</label>
                <textarea id="address" name="address" rows="4" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">{{ old('address', $member?->address) }}</textarea>
                @error('address')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" required class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                    <option value="active" @selected(old('status', $member?->status ?? 'active') === 'active')>Aktif</option>
                    <option value="inactive" @selected(old('status', $member?->status) === 'inactive')>Nonaktif</option>
                </select>
                @error('status')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="photo" class="mb-1.5 block text-sm font-medium text-gray-700">Foto anggota</label>
                <input id="photo" name="photo" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:font-medium">
                <p class="mt-1 text-xs text-gray-500">Format gambar, maksimal 2 MB. Foto digunakan pada kartu anggota.</p>
                @error('photo')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @if ($member?->photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($member->photo) }}" alt="Foto {{ $member->name }}" class="mt-3 size-20 rounded-lg object-cover">
                @endif
            </div>

            @if ($member)
                <p class="text-sm text-gray-500">Nomor anggota: <span class="font-semibold text-gray-800">{{ $member->membership_number }}</span></p>
            @else
                <p class="text-sm text-gray-500">Nomor anggota dan akun role anggota dibuat otomatis saat data disimpan.</p>
            @endif

            <div class="flex flex-wrap gap-3 pt-2">
                <x-ui.button type="submit">Simpan</x-ui.button>
                <x-ui.button :href="$member ? route('admin.members.show', $member) : route('admin.members.index')" variant="secondary">Batal</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-admin-layout>
