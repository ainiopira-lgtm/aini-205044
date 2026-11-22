<x-admin-layout title="Ubah buku">
    <x-slot name="header">
        <div>
            <p class="text-sm text-gray-500">Manajemen katalog</p>
            <h1 class="text-xl font-semibold text-gray-900">Ubah buku</h1>
        </div>
    </x-slot>

    @include('books._form')
</x-admin-layout>