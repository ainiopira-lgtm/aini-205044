<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $categories = Category::query()
            ->withCount('books')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.catalog.index', [
            'title' => 'Kategori',
            'resource' => 'categories',
            'items' => $categories,
            'search' => $search,
            'columns' => ['Nama' => 'name', 'Deskripsi' => 'description', 'Jumlah buku' => 'books_count'],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.catalog.form', [
            'title' => 'Tambah kategori',
            'resource' => 'categories',
            'item' => null,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama kategori', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = Category::create($request->validated());

        return to_route('admin.categories.show', $category)->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function show(Category $category): View
    {
        $this->authorize('view', $category);
        $category->loadCount('books');

        return view('admin.catalog.show', [
            'title' => $category->name,
            'resource' => 'categories',
            'item' => $category,
            'fields' => ['Nama kategori' => 'name', 'Deskripsi' => 'description', 'Jumlah buku' => 'books_count'],
            'canDelete' => $category->books_count === 0,
            'deleteMessage' => 'Kategori yang sudah digunakan oleh buku tidak dapat dihapus.',
        ]);
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.catalog.form', [
            'title' => 'Ubah kategori',
            'resource' => 'categories',
            'item' => $category,
            'fields' => [
                ['name' => 'name', 'label' => 'Nama kategori', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
            ],
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return to_route('admin.categories.show', $category)->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->books()->exists()) {
            return back()->with('error', 'Kategori yang sudah digunakan oleh buku tidak dapat dihapus.');
        }

        $category->delete();

        return to_route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
