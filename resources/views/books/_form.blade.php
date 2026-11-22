@php
    $formAction = $book
        ? route('admin.books.update', $book)
        : route('admin.books.store');
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($book)
        @method('PUT')
    @endif

    <x-ui.card title="Informasi buku" description="Lengkapi detail buku dan pilih minimal satu penulis.">
        <div class="grid gap-5 md:grid-cols-2">
            <x-ui.input name="title" label="Judul" :value="$book?->title" required maxlength="255" />
            <x-ui.input name="isbn" label="ISBN" :value="$book?->isbn" maxlength="255" />
            <x-ui.select
                name="category_id"
                label="Kategori"
                :options="$categories->pluck('name', 'id')"
                :value="$book?->category_id"
                placeholder="Pilih kategori"
                required
            />
            <x-ui.select
                name="publisher_id"
                label="Penerbit"
                :options="$publishers->pluck('name', 'id')"
                :value="$book?->publisher_id"
                placeholder="Pilih penerbit"
                required
            />
            <x-ui.select
                name="shelf_id"
                label="Rak"
                :options="$shelves->mapWithKeys(fn ($shelf) => [$shelf->id => $shelf->code.' · '.$shelf->name])"
                :value="$book?->shelf_id"
                placeholder="Pilih rak"
                required
            />
            <x-ui.input name="year" label="Tahun terbit" type="number" :value="$book?->year" min="1000" max="{{ (int) date('Y') + 1 }}" required />
            <x-ui.input name="price" label="Harga" type="number" :value="$book?->price" min="0" step="0.01" required />
        </div>

        <div class="mt-5">
            <label for="authors" class="mb-1.5 block text-sm font-medium text-gray-700">Penulis</label>
            <select
                id="authors"
                name="authors[]"
                multiple
                required
                aria-describedby="authors-hint"
                class="block min-h-40 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500"
            >
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}" @selected(in_array($author->id, old('authors', $selectedAuthorIds), false))>{{ $author->name }}</option>
                @endforeach
            </select>
            <p id="authors-hint" class="mt-1.5 text-sm text-gray-500">Gunakan Ctrl atau Command untuk memilih lebih dari satu penulis.</p>
            @error('authors')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('authors.*')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-5">
            <label for="synopsis" class="mb-1.5 block text-sm font-medium text-gray-700">Sinopsis</label>
            <textarea
                id="synopsis"
                name="synopsis"
                rows="5"
                required
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-gray-500 focus:ring-gray-500"
            >{{ old('synopsis', $book?->synopsis) }}</textarea>
            @error('synopsis')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-5">
            <label for="cover" class="mb-1.5 block text-sm font-medium text-gray-700">Sampul {{ $book ? '(opsional jika tidak diganti)' : '' }}</label>
            <input id="cover" name="cover" type="file" accept=".jpg,.jpeg,.png,.webp" @required(!$book) class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-700 file:me-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2.5 file:font-medium">
            <p class="mt-1.5 text-sm text-gray-500">JPG, PNG, atau WebP, maksimal 2 MB.</p>
            @error('cover')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Sampul {{ $book->title }}" class="mt-4 h-40 rounded-lg border border-gray-200 object-cover">
            @endif
        </div>
    </x-ui.card>

    <div class="flex flex-wrap gap-3">
        <x-ui.button type="submit">Simpan buku</x-ui.button>
        <x-ui.button href="{{ $book ? route('admin.books.show', $book) : route('admin.books.index') }}" variant="secondary">Batal</x-ui.button>
    </div>
</form>