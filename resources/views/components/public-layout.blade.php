@props(['title' => config('app.name', 'PustakaKu')])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ config('app.name', 'PustakaKu') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-gray-50 font-sans antialiased text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Navigasi publik">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-bold text-gray-900">
                <span class="grid size-9 place-items-center rounded-xl bg-gray-900 text-sm text-white">P</span>
                <span>PustakaKu</span>
            </a>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 sm:inline-flex">Beranda</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">Masuk</a>
                    <x-ui.button href="{{ route('register') }}" size="sm">Daftar</x-ui.button>
                @endauth
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} PustakaKu. Ruang untuk membaca dan bertumbuh.</p>
            <a href="{{ route('home') }}" class="font-medium text-gray-600 hover:text-gray-900">Kembali ke beranda</a>
        </div>
    </footer>
</body>
</html>
