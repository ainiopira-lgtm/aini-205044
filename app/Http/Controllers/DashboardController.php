<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->hasRole('anggota') && ! $user->hasAnyRole(['admin', 'pustakawan'])) {
            return to_route('member.dashboard');
        }

        $today = today();
        $chartStart = $today->copy()->subDays(29);
        $dailyLoans = Loan::query()
            ->whereBetween('loan_date', [$chartStart->toDateString(), $today->toDateString()])
            ->selectRaw('loan_date, COUNT(*) as loan_count')
            ->groupBy('loan_date')
            ->pluck('loan_count', 'loan_date');
        $loanChartLabels = [];
        $loanChartValues = [];

        foreach (CarbonPeriod::create($chartStart, $today) as $date) {
            $dateKey = $date->toDateString();
            $loanChartLabels[] = $date->format('d/m');
            $loanChartValues[] = (int) $dailyLoans->get($dateKey, 0);
        }

        $popularBooks = Book::query()
            ->join('book_copies', 'books.id', '=', 'book_copies.book_id')
            ->join('loans', 'book_copies.id', '=', 'loans.book_copy_id')
            ->whereBetween('loans.loan_date', [$chartStart->toDateString(), $today->toDateString()])
            ->select('books.id', 'books.title')
            ->selectRaw('COUNT(loans.id) as loan_count')
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('loan_count')
            ->orderBy('books.title')
            ->limit(5)
            ->get();

        $dueTodayLoans = Loan::query()
            ->with([
                'member:id,name,membership_number',
                'bookCopy:id,book_id,barcode',
                'bookCopy.book:id,title',
            ])
            ->whereNull('return_date')
            ->whereDate('due_date', $today)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return view('dashboard', [
            'totalBooks' => Book::query()->count(),
            'activeMembers' => Member::query()->where('status', 'active')->count(),
            'currentLoans' => Loan::query()
                ->where('status', 'active')
                ->whereNull('return_date')
                ->whereDate('due_date', '>=', $today)
                ->count(),
            'overdueLoans' => Loan::query()
                ->whereNull('return_date')
                ->whereDate('due_date', '<', $today)
                ->count(),
            'finesThisMonth' => (float) Fine::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'loanChartLabels' => $loanChartLabels,
            'loanChartValues' => $loanChartValues,
            'popularBooks' => $popularBooks,
            'dueTodayLoans' => $dueTodayLoans,
        ]);
    }
}
