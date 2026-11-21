@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->class(['overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || $description || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="p-5 sm:p-6">{{ $slot }}</div>
</section>
