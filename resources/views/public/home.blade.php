<x-public-layout title="Selamat datang">
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-gray-500">Perpustakaan digital</p>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-gray-950 sm:text-6xl">Temukan bacaan berikutnya.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-gray-600">Jelajahi koleksi, kelola peminjaman, dan temukan ruang untuk membaca dan bertumbuh.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-ui.button href="{{ route('login') }}">Masuk ke perpustakaan</x-ui.button>
                <x-ui.button href="{{ route('register') }}" variant="secondary">Buat akun</x-ui.button>
            </div>
        </div>
    </section>
</x-public-layout>
