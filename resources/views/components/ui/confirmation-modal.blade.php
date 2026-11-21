@props([
    'name',
    'title' => 'Konfirmasi tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan?',
    'confirmText' => 'Ya, lanjutkan',
    'cancelText' => 'Batal',
    'action' => null,
    'method' => 'DELETE',
])

<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <span @click="open = true">{{ $trigger ?? '' }}</span>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-gray-950/50 p-4"
        @click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $name }}-title"
    >
        <section x-show="open" x-transition class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 id="{{ $name }}-title" class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $message }}</p>
            <div class="mt-6 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <x-ui.button variant="secondary" @click="open = false">{{ $cancelText }}</x-ui.button>
                @if ($action)
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if (strtoupper($method) !== 'POST')
                            @method($method)
                        @endif
                        <x-ui.button type="submit" variant="danger" class="w-full sm:w-auto">{{ $confirmText }}</x-ui.button>
                    </form>
                @else
                    <x-ui.button variant="danger" @click="$dispatch('confirmed'); open = false">{{ $confirmText }}</x-ui.button>
                @endif
            </div>
        </section>
    </div>
</div>
