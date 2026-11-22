<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Services\FineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FineController extends Controller
{
    public function __construct(private readonly FineService $fines) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:unpaid,paid'],
        ]);
        $status = $filters['status'] ?? '';
        $fines = Fine::query()
            ->with(['loan.member', 'loan.bookCopy.book'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'unpaid' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('fines.index', compact('fines', 'status'));
    }

    public function pay(Fine $fine): RedirectResponse
    {
        $this->fines->pay($fine);

        return to_route('admin.fines.index')->with('success', 'Pembayaran denda berhasil dicatat.');
    }

    public function receipt(Fine $fine): Response
    {
        abort_unless($fine->status === 'paid' && $fine->paid_at !== null, 404);
        $fine->load(['loan.member', 'loan.bookCopy.book']);

        return Pdf::loadView('fines.receipt', compact('fine'))
            ->setPaper([0, 0, 226.77, 360])
            ->download('struk-denda-'.$fine->id.'.pdf');
    }
}
