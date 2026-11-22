<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShelfRequest;
use App\Http\Requests\UpdateShelfRequest;
use App\Models\Shelf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShelfController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $shelves = Shelf::query()
            ->withCount('books')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")))
            ->orderBy('code')
            ->paginate(12)
            ->withQueryString();

        return view('admin.catalog.index', [
            'title' => 'Rak',
            'resource' => 'shelves',
            'items' => $shelves,
            'search' => $search,
            'columns' => ['Kode' => 'code', 'Nama rak' => 'name', 'Lokasi' => 'location', 'Jumlah buku' => 'books_count'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Shelf::class);

        return view('admin.catalog.form', [
            'title' => 'Tambah rak',
            'resource' => 'shelves',
            'item' => null,
            'fields' => [
                ['name' => 'code', 'label' => 'Kode rak', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama rak', 'type' => 'text', 'required' => true],
                ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
            ],
        ]);
    }

    public function store(StoreShelfRequest $request): RedirectResponse
    {
        $shelf = Shelf::create($request->validated());

        return to_route('admin.shelves.show', $shelf)->with('success', 'Rak berhasil ditambahkan.');
    }

    public function show(Shelf $shelf): View
    {
        $this->authorize('view', $shelf);
        $shelf->loadCount('books');

        return view('admin.catalog.show', [
            'title' => $shelf->name,
            'resource' => 'shelves',
            'item' => $shelf,
            'fields' => ['Kode rak' => 'code', 'Nama rak' => 'name', 'Lokasi' => 'location', 'Jumlah buku' => 'books_count'],
            'canDelete' => $shelf->books_count === 0,
            'deleteMessage' => 'Rak yang masih digunakan oleh buku tidak dapat dihapus.',
        ]);
    }

    public function edit(Shelf $shelf): View
    {
        $this->authorize('update', $shelf);

        return view('admin.catalog.form', [
            'title' => 'Ubah rak',
            'resource' => 'shelves',
            'item' => $shelf,
            'fields' => [
                ['name' => 'code', 'label' => 'Kode rak', 'type' => 'text', 'required' => true],
                ['name' => 'name', 'label' => 'Nama rak', 'type' => 'text', 'required' => true],
                ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
            ],
        ]);
    }

    public function update(UpdateShelfRequest $request, Shelf $shelf): RedirectResponse
    {
        $shelf->update($request->validated());

        return to_route('admin.shelves.show', $shelf)->with('success', 'Rak berhasil diperbarui.');
    }

    public function destroy(Shelf $shelf): RedirectResponse
    {
        $this->authorize('delete', $shelf);

        if ($shelf->books()->exists()) {
            return back()->with('error', 'Rak yang masih digunakan oleh buku tidak dapat dihapus.');
        }

        $shelf->delete();

        return to_route('admin.shelves.index')->with('success', 'Rak berhasil dihapus.');
    }
}
