<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $authors = Author::query()
            ->withCount('books')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('bio', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.catalog.index', [
            'title' => 'Penulis',
            'resource' => 'authors',
            'items' => $authors,
            'search' => $search,
            'columns' => ['Nama' => 'name', 'Biografi' => 'bio', 'Jumlah buku' => 'books_count'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Author::class);

        return view('admin.catalog.form', [
            'title' => 'Tambah penulis',
            'resource' => 'authors',
            'item' => null,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama penulis', 'type' => 'text', 'required' => true],
                ['name' => 'bio', 'label' => 'Biografi', 'type' => 'textarea'],
            ],
        ]);
    }

    public function store(StoreAuthorRequest $request): RedirectResponse
    {
        $author = Author::create($request->validated());

        return to_route('admin.authors.show', $author)->with('success', 'Penulis berhasil ditambahkan.');
    }

    public function show(Author $author): View
    {
        $this->authorize('view', $author);
        $author->loadCount('books');

        return view('admin.catalog.show', [
            'title' => $author->name,
            'resource' => 'authors',
            'item' => $author,
            'fields' => ['Nama penulis' => 'name', 'Biografi' => 'bio', 'Jumlah buku' => 'books_count'],
            'canDelete' => true,
            'deleteMessage' => 'Relasi buku dengan penulis ini akan dilepas.',
        ]);
    }

    public function edit(Author $author): View
    {
        $this->authorize('update', $author);

        return view('admin.catalog.form', [
            'title' => 'Ubah penulis',
            'resource' => 'authors',
            'item' => $author,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama penulis', 'type' => 'text', 'required' => true],
                ['name' => 'bio', 'label' => 'Biografi', 'type' => 'textarea'],
            ],
        ]);
    }

    public function update(UpdateAuthorRequest $request, Author $author): RedirectResponse
    {
        $author->update($request->validated());

        return to_route('admin.authors.show', $author)->with('success', 'Penulis berhasil diperbarui.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $this->authorize('delete', $author);
        $author->delete();

        return to_route('admin.authors.index')->with('success', 'Penulis berhasil dihapus.');
    }
}
