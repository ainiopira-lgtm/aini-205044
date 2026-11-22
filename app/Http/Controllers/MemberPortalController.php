<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\Loan;
use App\Models\Reservation;
use App\Services\CirculationService;
use App\Services\LibrarySettingsService;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberPortalController extends Controller
{
    public function __construct(
        private readonly CirculationService $circulation,
        private readonly LibrarySettingsService $settings,
        private readonly ReservationService $reservations,
    ) {}

    public function index(Request $request): View
    {
        $member = $request->user()->memberProfile;
        if (! $member) {
            return view('members.dashboard', [
                'member' => null,
                'activeLoans' => collect(),
                'history' => null,
                'fines' => collect(),
                'reservations' => collect(),
                'unpaidFineTotal' => 0,
                'maxRenewals' => $this->settings->getInt('max_renewals'),
            ]);
        }

        $this->reservations->refreshMemberQueue($member);
        $activeLoans = $member->loans()
            ->with('bookCopy.book')
            ->whereIn('status', ['active', 'overdue'])
            ->whereNull('return_date')
            ->orderBy('due_date')
            ->get();
        $history = $member->loans()
            ->with('bookCopy.book')
            ->latest('loan_date')
            ->paginate(10);
        $fines = Fine::query()
            ->with('loan.bookCopy.book')
            ->whereHas('loan', fn ($query) => $query->where('member_id', $member->id))
            ->latest()
            ->get();
        $reservations = Reservation::query()
            ->with('book')
            ->where('member_id', $member->id)
            ->orderByDesc('reserved_at')
            ->get();
        $unpaidFineTotal = (float) $fines->where('status', 'unpaid')->sum('amount');
        $maxRenewals = $this->settings->getInt('max_renewals');

        return view('members.dashboard', compact('member', 'activeLoans', 'history', 'fines', 'reservations', 'unpaidFineTotal', 'maxRenewals'));
    }

    public function renew(Request $request, Loan $loan): RedirectResponse
    {
        $member = $request->user()->memberProfile;
        abort_unless($member, 403);

        $this->circulation->renewMemberLoan($member->id, $loan->id);

        return to_route('member.dashboard')->with('success', 'Peminjaman berhasil diperpanjang.');
    }
}
