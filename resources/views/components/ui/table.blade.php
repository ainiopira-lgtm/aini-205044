<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table {{ $attributes->class(['min-w-full divide-y divide-gray-200 text-left text-sm']) }}>
            @isset($head)
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                    {{ $head }}
                </thead>
            @endisset
            <tbody class="divide-y divide-gray-100 text-gray-700">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
