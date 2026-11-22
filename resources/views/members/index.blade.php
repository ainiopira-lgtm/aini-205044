<x-admin-layout title="Anggota">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen sirkulasi</p>
            <h1 class="text-xl font-semibold text-gray-900">Anggota</h1>
        </div>
    </x-slot>

    <x-ui.card>
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <form method="GET" action="{{ route('admin.members.index') }}" class="flex flex-1 flex-wrap items-end gap-3">
                <div class="w-full max-w-md">
                    <x-ui.input name="q" label="Pencarian" :value="$search" placeholder="Nama, email, atau nomor anggota" />
                </div>
                <div>
                    <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="block rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500">
                        <option value="">Semua status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <x-ui.button type="submit">Terapkan</x-ui.button>
            </form>
            <x-ui.button :href="route('admin.members.create')">Tambah anggota</x-ui.button>
        </div>

        <x-ui.table>
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-5 py-3">Nomor anggota</th>
                    <th scope="col" class="px-5 py-3">Nama</th>
                    <th scope="col" class="px-5 py-3">Email</th>
                    <th scope="col" class="px-5 py-3">Telepon</th>
                    <th scope="col" class="px-5 py-3">Status</th>
                    <th scope="col" class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </x-slot:head>
            @forelse ($members as $member)
                <tr>
                    <td class="whitespace-nowrap px-5 py-3.5 font-medium text-gray-900">{{ $member->membership_number }}</td>
                    <td class="px-5 py-3.5">{{ $member->name }}</td>
                    <td class="px-5 py-3.5">{{ $member->email }}</td>
                    <td class="px-5 py-3.5">{{ $member->phone ?: '—' }}</td>
                    <td class="px-5 py-3.5">
                        <x-ui.status-badge :status="$member->status">{{ $member->status === 'active' ? 'Aktif' : 'Nonaktif' }}</x-ui.status-badge>
                    </td>
                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                        <a href="{{ route('admin.members.show', $member) }}" class="font-semibold text-gray-800 hover:underline">Detail</a>
                        <a href="{{ route('admin.members.edit', $member) }}" class="ms-3 font-semibold text-gray-600 hover:underline">Ubah</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-gray-500">Data anggota belum tersedia.</td>
                </tr>
            @endforelse
        </x-ui.table>
        <x-ui.pagination :paginator="$members" />
    </x-ui.card>
</x-admin-layout>
