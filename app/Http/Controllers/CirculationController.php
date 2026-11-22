<?php

namespace App\Http\Controllers;

use App\Services\CirculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CirculationController extends Controller
{
    public function __construct(private readonly CirculationService $circulation) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'member' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($filters['member'] ?? '');
        $members = $this->circulation->searchMembers($search);
        $memberId = $request->session()->get('circulation.member_id');
        $copyIds = $request->session()->get('circulation.copy_ids', []);
        $selectedMember = $memberId ? $this->circulation->memberSummary((int) $memberId, count($copyIds)) : null;
        $draftCopies = $this->circulation->draftCopies($copyIds);
        $activeLoans = $this->circulation->activeLoans();

        return view('loans.index', compact('search', 'members', 'selectedMember', 'draftCopies', 'activeLoans'));
    }

    public function selectMember(Request $request): RedirectResponse
    {
        $data = $request->validate(['member_id' => ['required', 'integer', 'exists:members,id']]);
        $memberId = (int) $data['member_id'];
        $this->circulation->memberSummary($memberId);

        if ((int) $request->session()->get('circulation.member_id') !== $memberId) {
            $request->session()->put('circulation.copy_ids', []);
        }
        $request->session()->put('circulation.member_id', $memberId);

        return to_route('admin.circulation.index');
    }

    public function addCopy(Request $request): RedirectResponse
    {
        $data = $request->validate(['barcode' => ['required', 'string', 'max:255']]);
        $memberId = $this->selectedMemberId($request);
        $copyIds = $request->session()->get('circulation.copy_ids', []);
        $copy = $this->circulation->addCopyToDraft($memberId, $data['barcode'], $copyIds);
        $copyIds[] = $copy->id;
        $request->session()->put('circulation.copy_ids', $copyIds);

        return to_route('admin.circulation.index')->with('success', "{$copy->book->title} ditambahkan ke daftar sementara.");
    }

    public function removeCopy(Request $request): RedirectResponse
    {
        $data = $request->validate(['copy_id' => ['required', 'integer']]);
        $copyIds = $request->session()->get('circulation.copy_ids', []);
        $request->session()->put('circulation.copy_ids', array_values(array_diff($copyIds, [(int) $data['copy_id']])));

        return to_route('admin.circulation.index');
    }

    public function clearDraft(Request $request): RedirectResponse
    {
        $request->session()->forget('circulation');

        return to_route('admin.circulation.index');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $memberId = $this->selectedMemberId($request);
        $copyIds = $request->session()->get('circulation.copy_ids', []);
        $loans = $this->circulation->checkout($memberId, $copyIds, (int) $request->user()->id);
        $request->session()->forget('circulation');

        return to_route('admin.circulation.index')
            ->with('success', $loans->count().' eksemplar berhasil dipinjam.');
    }

    public function returnCopy(Request $request): RedirectResponse
    {
        $data = $request->validate(['return_barcode' => ['required', 'string', 'max:255']]);
        $result = $this->circulation->returnByBarcode($data['return_barcode']);
        $message = 'Pengembalian berhasil diproses.';

        if ($result['fine']) {
            $message .= ' Terlambat '.$result['days_late'].' hari; denda Rp '.number_format((float) $result['fine']->amount, 0, ',', '.').'.';
        }

        return to_route('admin.circulation.index')->with('success', $message);
    }

    public function renew(Request $request): RedirectResponse
    {
        $data = $request->validate(['renew_barcode' => ['required', 'string', 'max:255']]);
        $loan = $this->circulation->renewByBarcode($data['renew_barcode']);

        return to_route('admin.circulation.index')
            ->with('success', 'Peminjaman berhasil diperpanjang sampai '.$loan->due_date->format('d M Y').'.');
    }

    private function selectedMemberId(Request $request): int
    {
        $memberId = $request->session()->get('circulation.member_id');
        if (! $memberId) {
            throw ValidationException::withMessages(['member' => 'Pilih anggota terlebih dahulu.']);
        }

        return (int) $memberId;
    }
}
