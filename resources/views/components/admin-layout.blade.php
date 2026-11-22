@props(['title' => 'Dashboard'])

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
<body class="font-sans antialiased text-gray-900">
    <div
        x-data="{ sidebarOpen: false, collapsed: false, userMenuOpen: false }"
        @keydown.escape.window="sidebarOpen = false; userMenuOpen = false"
        class="min-h-screen bg-gray-50"
    >
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-950/40 md:hidden"
            aria-hidden="true"
        ></div>

        <aside
            class="fixed inset-y-0 start-0 z-40 flex w-64 -translate-x-full flex-col border-e border-gray-200 bg-white transition-all duration-200 md:translate-x-0"
            :class="{ 'translate-x-0': sidebarOpen, 'md:w-20': collapsed, 'md:w-64': !collapsed }"
            aria-label="Navigasi utama"
        >
            <div class="flex h-16 items-center justify-between border-b border-gray-100 px-5">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 font-bold text-gray-900">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-gray-900 text-sm text-white">P</span>
                    <span x-show="!collapsed" x-transition.opacity class="truncate">PustakaKu</span>
                </a>
                <button
                    type="button"
                    @click="collapsed = !collapsed"
                    class="hidden rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 md:inline-flex"
                    :aria-expanded="(!collapsed).toString()"
                    aria-label="Ciutkan atau lebarkan sidebar"
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M9 5 2 12l7 7M22 12H3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <p x-show="!collapsed" class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Menu</p>

                @can('view-admin-dashboard')
                    <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg bg-gray-100 px-3 py-2.5 text-sm font-semibold text-gray-900" :title="collapsed ? 'Dashboard' : null">
                        <svg class="size-5 shrink-0 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M3 13h8V3H3v10Zm0 8h8v-5H3v5Zm10 0h8V11h-8v10Zm0-18v5h8V3h-8Z" stroke-width="1.7" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Dashboard</span>
                    </a>
                @endcan

                @can('manage-books')
                    <a href="{{ route('admin.books.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Katalog buku' : null">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 0V21m4-14h8m-8 4h8" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Katalog buku</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Kategori' : null">
                        <span class="grid size-5 shrink-0 place-items-center text-[10px] font-bold text-gray-400">K</span>
                        <span x-show="!collapsed">Kategori</span>
                    </a>
                    <a href="{{ route('admin.authors.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Penulis' : null">
                        <span class="grid size-5 shrink-0 place-items-center text-[10px] font-bold text-gray-400">P</span>
                        <span x-show="!collapsed">Penulis</span>
                    </a>
                    <a href="{{ route('admin.publishers.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Penerbit' : null">
                        <span class="grid size-5 shrink-0 place-items-center text-[10px] font-bold text-gray-400">T</span>
                        <span x-show="!collapsed">Penerbit</span>
                    </a>
                    <a href="{{ route('admin.shelves.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Rak' : null">
                        <span class="grid size-5 shrink-0 place-items-center text-[10px] font-bold text-gray-400">R</span>
                        <span x-show="!collapsed">Rak</span>
                    </a>
                @endcan

                @can('manage-circulation')
                    <a href="{{ route('admin.circulation.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Sirkulasi' : null">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M5 7h14m-3-3 3 3-3 3M19 17H5m3 3-3-3 3-3" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Sirkulasi</span>
                    </a>
                    <a href="{{ route('admin.fines.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Denda' : null">
                        <span class="grid size-5 shrink-0 place-items-center text-[10px] font-bold text-gray-400">D</span>
                        <span x-show="!collapsed">Denda</span>
                    </a>
                @endcan

                @can('manage-members')
                    <a href="{{ route('admin.members.index') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Anggota' : null">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Anggota</span>
                    </a>
                @endcan

                @can('manage-settings')
                    <a href="{{ route('admin.settings.edit') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Pengaturan' : null">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/><path d="m19.4 15 .1.1a1.8 1.8 0 1 1-2.5 2.5l-.1-.1a1.8 1.8 0 0 0-3 .9v.2a1.8 1.8 0 1 1-3.6 0v-.2a1.8 1.8 0 0 0-3-.9l-.1.1a1.8 1.8 0 1 1-2.5-2.5l.1-.1a1.8 1.8 0 0 0-.9-3H3.7a1.8 1.8 0 1 1 0-3.6h.2a1.8 1.8 0 0 0 .9-3l-.1-.1a1.8 1.8 0 1 1 2.5-2.5l.1.1a1.8 1.8 0 0 0 3-.9v-.2a1.8 1.8 0 1 1 3.6 0v.2a1.8 1.8 0 0 0 3 .9l.1-.1a1.8 1.8 0 1 1 2.5 2.5l-.1.1a1.8 1.8 0 0 0 .9 3h.2a1.8 1.8 0 1 1 0 3.6h-.2a1.8 1.8 0 0 0-.9 3Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Pengaturan</span>
                    </a>
                @endcan

                @can('view-member-area')
                    <a href="{{ route('member.dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Layanan anggota' : null">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M5 4h14v16H5zM8 8h8m-8 4h8m-8 4h5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span x-show="!collapsed">Layanan anggota</span>
                    </a>
                @endcan
            </nav>

            <div class="border-t border-gray-100 p-3">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900" :title="collapsed ? 'Profil' : null">
                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-gray-200 text-xs font-bold text-gray-700">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span x-show="!collapsed" class="truncate">Profil</span>
                </a>
            </div>
        </aside>

        <div :class="collapsed ? 'md:ms-20' : 'md:ms-64'" class="min-h-screen transition-[margin] duration-200">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 md:hidden" aria-label="Buka navigasi">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M4 6h16M4 12h16M4 18h16" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    @isset($header)
                        <div class="min-w-0">{{ $header }}</div>
                    @else
                        <h1 class="font-semibold text-gray-900">{{ $title }}</h1>
                    @endisset
                </div>

                <div class="relative" @click.outside="userMenuOpen = false">
                    <button type="button" @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-left hover:bg-gray-100" :aria-expanded="userMenuOpen.toString()">
                        <span class="hidden text-right sm:block">
                            <span class="block text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</span>
                            <span class="block text-xs text-gray-500">{{ auth()->user()->getRoleNames()->first() ?? 'Pengguna' }}</span>
                        </span>
                        <span class="grid size-9 place-items-center rounded-full bg-gray-900 text-sm font-semibold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <svg class="size-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                    </button>
                    <div x-cloak x-show="userMenuOpen" x-transition.origin.top.right class="absolute end-0 mt-2 w-52 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-gray-100">Profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Keluar</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    @foreach (['success', 'error', 'warning', 'info'] as $flashType)
                        <x-ui.flash-message :type="$flashType" />
                    @endforeach
                    @if ($errors->any())
                        <x-ui.flash-message type="error" :message="$errors->first()" />
                    @endif
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
