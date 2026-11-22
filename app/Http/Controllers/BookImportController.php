<?php

namespace App\Http\Controllers;

use App\Imports\BooksImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class BookImportController extends Controller
{
    public function create(): View
    {
        return view('books.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $import = new BooksImport;
        Excel::import($import, $validated['file']);

        return to_route('admin.books.import.create')->with('import_report', [
            'imported' => $import->importedCount(),
            'failures' => $import->failures(),
        ]);
    }
}
