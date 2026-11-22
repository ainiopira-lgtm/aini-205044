<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublisherRequest;
use App\Http\Requests\UpdatePublisherRequest;
use App\Models\Publisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublisherController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $publishers = Publisher::query()
            ->withCount('books')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.catalog.index', [
            'title' => 'Penerbit',
            'resource' => 'publishers',
            'items' => $publishers,
            'search' => $search,
            'columns' => ['Nama' => 'name', 'Email' => 'email', 'Telepon' => 'phone', 'Jumlah buku' => 'books_count'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Publisher::class);

        return view('admin.catalog.form', [
            'title' => 'Tambah penerbit',
            'resource' => 'publishers',
            'item' => null,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama penerbit', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
            ],
        ]);
    }

    public function store(StorePublisherRequest $request): RedirectResponse
    {
        $publisher = Publisher::create($request->validated());

        return to_route('admin.publishers.show', $publisher)->with('success', 'Penerbit berhasil ditambahkan.');
    }

    public function show(Publisher $publisher): View
    {
        $this->authorize('view', $publisher);
        $publisher->loadCount('books');

        return view('admin.catalog.show', [
            'title' => $publisher->name,
            'resource' => 'publishers',
            'item' => $publisher,
            'fields' => ['Nama penerbit' => 'name', 'Email' => 'email', 'Telepon' => 'phone', 'Alamat' => 'address', 'Jumlah buku' => 'books_count'],
            'canDelete' => $publisher->books_count === 0,
            'deleteMessage' => 'Penerbit yang masih digunakan oleh buku tidak dapat dihapus.',
        ]);
    }

    public function edit(Publisher $publisher): View
    {
        $this->authorize('update', $publisher);

        return view('admin.catalog.form', [
            'title' => 'Ubah penerbit',
            'resource' => 'publishers',
            'item' => $publisher,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama penerbit', 'type' => 'text', 'required' => true],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
            ],
        ]);
    }

    public function update(UpdatePublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        $publisher->update($request->validated());

        return to_route('admin.publishers.show', $publisher)->with('success', 'Penerbit berhasil diperbarui.');
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        $this->authorize('delete', $publisher);

        if ($publisher->books()->exists()) {
            return back()->with('error', 'Penerbit yang masih digunakan oleh buku tidak dapat dihapus.');
        }

        $publisher->delete();

        return to_route('admin.publishers.index')->with('success', 'Penerbit berhasil dihapus.');
    }
}
