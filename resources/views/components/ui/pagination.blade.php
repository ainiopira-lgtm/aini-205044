@props(['paginator'])

@if ($paginator && $paginator->hasPages())
    <nav class="pt-4" aria-label="Navigasi halaman">
        {{ $paginator->onEachSide(1)->links() }}
    </nav>
@endif
