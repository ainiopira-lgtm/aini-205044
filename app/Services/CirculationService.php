<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CirculationService
{
    public function __construct(private readonly LibrarySettingsService $settings) {}

    public function searchMembers(string $search): Collection
    {
        $search = trim($search);
        if ($search === '') {
            return new Collection;
        }

        return Member::query()
            ->where(fn ($query) => $query
                ->where('membership_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    /**
     * @return array{
     *     member: Member,
     *     active_loan_count: int,
     *     max_active_loans: int,
     *     remaining_quota: int,
     *     has_unpaid_fines: bool,
     *     unpaid_fine_total: float
     * }
     */
    public function memberSummary(int $memberId, int $draftCount = 0): array
    {
        $member = Member::query()->findOrFail($memberId);
        $activeLoanCount = $member->loans()
            ->whereIn('status', ['active', 'overdue'])
            ->whereNull('return_date')
            ->count();
        $maxActiveLoans = $this->settings->getInt('max_active_loans');
        $unpaidFines = Fine::query()
            ->where('status', 'unpaid')
            ->whereHas('loan', fn ($query) => $query->where('member_id', $member->id));
        $unpaidFineTotal = (float) (clone $unpaidFines)->sum('amount');

        return [
            'member' => $member,
            'active_loan_count' => $activeLoanCount,
            'max_active_loans' => $maxActiveLoans,
            'remaining_quota' => max(0, $maxActiveLoans - $activeLoanCount - $draftCount),
            'has_unpaid_fines' => $unpaidFines->exists(),
            'unpaid_fine_total' => $unpaidFineTotal,
        ];
    }

    public function addCopyToDraft(int $memberId, string $barcode, array $draftCopyIds = []): BookCopy
    {
        $summary = $this->memberSummary($memberId);
        $this->assertMemberCanBorrow($summary['member']);
        $this->assertNoUnpaidFines($summary['member']);

        if (count(array_unique($draftCopyIds)) !== count($draftCopyIds)) {
            $this->reject('barcode', 'Eksemplar tersebut sudah ada di daftar sementara.');
        }

        if ($summary['max_active_loans'] <= $summary['active_loan_count'] + count($draftCopyIds)) {
            $this->reject('barcode', 'Kuota peminjaman anggota sudah terpenuhi.');
        }

        if (BookCopy::query()->whereIn('id', $draftCopyIds)->where('barcode', $barcode)->exists()) {
            $this->reject('barcode', 'Eksemplar tersebut sudah ada di daftar sementara.');
        }

        $copy = BookCopy::query()->with('book')->where('barcode', trim($barcode))->first();
        if (! $copy || $copy->status !== 'available') {
            $this->reject('barcode', 'Barcode tidak ditemukan atau eksemplar tidak tersedia.');
        }

        return $copy;
    }

    public function draftCopies(array $copyIds): Collection
    {
        if ($copyIds === []) {
            return new Collection;
        }

        return BookCopy::query()
            ->with('book')
            ->whereIn('id', $copyIds)
            ->orderBy('id')
            ->get();
    }

    public function checkout(int $memberId, array $copyIds, int $userId): Collection
    {
        return DB::transaction(function () use ($memberId, $copyIds, $userId): Collection {
            if ($copyIds === []) {
                $this->reject('copies', 'Tambahkan minimal satu eksemplar sebelum menyimpan peminjaman.');
            }

            if (count(array_unique($copyIds)) !== count($copyIds)) {
                $this->reject('copies', 'Daftar peminjaman berisi eksemplar yang sama lebih dari sekali.');
            }

            $member = Member::query()->whereKey($memberId)->lockForUpdate()->firstOrFail();
            $this->assertMemberCanBorrow($member);
            $this->assertNoUnpaidFines($member);

            $copies = BookCopy::query()
                ->whereIn('id', $copyIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($copies->count() !== count($copyIds)) {
                $this->reject('copies', 'Satu atau beberapa eksemplar tidak ditemukan.');
            }

            if ($copies->contains(fn (BookCopy $copy) => $copy->status !== 'available')) {
                $this->reject('copies', 'Semua eksemplar harus berstatus tersedia.');
            }

            $activeLoanCount = $member->loans()
                ->whereIn('status', ['active', 'overdue'])
                ->whereNull('return_date')
                ->count();
            $maxActiveLoans = $this->settings->getInt('max_active_loans');
            if ($activeLoanCount + $copies->count() > $maxActiveLoans) {
                $this->reject('copies', 'Peminjaman melebihi kuota anggota.');
            }

            $today = today();
            $dueDate = $today->copy()->addDays($this->settings->getInt('loan_duration_days'));
            $loans = new Collection;

            foreach ($copies as $copy) {
                $loans->push($member->loans()->create([
                    'book_copy_id' => $copy->id,
                    'user_id' => $userId,
                    'loan_date' => $today,
                    'due_date' => $dueDate,
                    'status' => 'active',
                ]));
                $copy->update(['status' => 'borrowed']);
            }

            return $loans;
        });
    }

    /**
     * @return array{loan: Loan, fine: Fine|null, days_late: int}
     */
    public function returnByBarcode(string $barcode): array
    {
        return DB::transaction(function () use ($barcode): array {
            $copyCandidate = BookCopy::query()
                ->where('barcode', trim($barcode))
                ->first();
            if (! $copyCandidate) {
                $this->reject('return_barcode', 'Barcode tidak ditemukan atau eksemplar tidak sedang dipinjam.');
            }

            $loanCandidate = Loan::query()
                ->where('book_copy_id', $copyCandidate->id)
                ->whereIn('status', ['active', 'overdue'])
                ->whereNull('return_date')
                ->latest('id')
                ->first();
            if (! $loanCandidate) {
                $this->reject('return_barcode', 'Tidak ada peminjaman aktif untuk eksemplar tersebut.');
            }

            Member::query()->whereKey($loanCandidate->member_id)->lockForUpdate()->firstOrFail();
            $copy = BookCopy::query()->whereKey($copyCandidate->id)->lockForUpdate()->firstOrFail();
            if ($copy->status !== 'borrowed') {
                $this->reject('return_barcode', 'Barcode tidak ditemukan atau eksemplar tidak sedang dipinjam.');
            }

            $loan = Loan::query()
                ->where('book_copy_id', $copy->id)
                ->whereIn('status', ['active', 'overdue'])
                ->whereNull('return_date')
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if (! $loan) {
                $this->reject('return_barcode', 'Tidak ada peminjaman aktif untuk eksemplar tersebut.');
            }

            $returnedAt = today();
            $daysLate = $loan->due_date->lt($returnedAt)
                ? (int) $loan->due_date->diffInDays($returnedAt)
                : 0;
            $fine = null;
            if ($daysLate > 0) {
                $fine = $loan->fines()->create([
                    'amount' => $daysLate * $this->settings->getInt('fine_per_day'),
                    'days_late' => $daysLate,
                    'status' => 'unpaid',
                ]);
            }

            $loan->update([
                'return_date' => $returnedAt,
                'status' => 'returned',
            ]);
            $copy->update(['status' => 'available']);

            return ['loan' => $loan, 'fine' => $fine, 'days_late' => $daysLate];
        });
    }

    public function renewByBarcode(string $barcode): Loan
    {
        return DB::transaction(function () use ($barcode): Loan {
            $copy = BookCopy::query()
                ->where('barcode', trim($barcode))
                ->lockForUpdate()
                ->first();
            if (! $copy || $copy->status !== 'borrowed') {
                $this->reject('renew_barcode', 'Barcode tidak ditemukan atau eksemplar tidak sedang dipinjam.');
            }

            $loan = Loan::query()
                ->where('book_copy_id', $copy->id)
                ->whereIn('status', ['active', 'overdue'])
                ->whereNull('return_date')
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if (! $loan) {
                $this->reject('renew_barcode', 'Tidak ada peminjaman aktif untuk eksemplar tersebut.');
            }

            if ($loan->extension_count >= $this->settings->getInt('max_renewals')) {
                $this->reject('renew_barcode', 'Batas perpanjangan peminjaman telah tercapai.');
            }

            if ($loan->status === 'overdue' || $loan->due_date->lt(today())) {
                $this->reject('renew_barcode', 'Peminjaman yang sudah terlambat tidak dapat diperpanjang.');
            }

            Book::query()->whereKey($copy->book_id)->lockForUpdate()->firstOrFail();
            $hasReservation = Reservation::query()
                ->where('book_id', $copy->book_id)
                ->where('status', 'pending')
                ->whereNull('fulfilled_at')
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
            if ($hasReservation) {
                $this->reject('renew_barcode', 'Buku memiliki reservasi aktif dan tidak dapat diperpanjang.');
            }

            $loan->update([
                'due_date' => $loan->due_date->copy()->addDays($this->settings->getInt('loan_duration_days')),
                'extension_count' => $loan->extension_count + 1,
            ]);

            return $loan->refresh();
        });
    }

    public function activeLoans(): Collection
    {
        return Loan::query()
            ->with(['member', 'bookCopy.book'])
            ->whereIn('status', ['active', 'overdue'])
            ->whereNull('return_date')
            ->orderBy('due_date')
            ->limit(10)
            ->get();
    }

    private function assertMemberCanBorrow(Member $member): void
    {
        if ($member->status !== 'active') {
            $this->reject('member', 'Anggota berstatus nonaktif tidak dapat meminjam buku.');
        }
    }

    private function assertNoUnpaidFines(Member $member): void
    {
        $hasUnpaidFine = Fine::query()
            ->where('status', 'unpaid')
            ->whereHas('loan', fn ($query) => $query->where('member_id', $member->id))
            ->exists();

        if ($hasUnpaidFine) {
            $this->reject('member', 'Anggota memiliki denda yang belum lunas.');
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
