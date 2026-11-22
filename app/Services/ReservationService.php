<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Member;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function reserve(int $memberId, int $bookId): Reservation
    {
        return DB::transaction(function () use ($memberId, $bookId): Reservation {
            $member = Member::query()->whereKey($memberId)->lockForUpdate()->firstOrFail();
            if ($member->status !== 'active') {
                $this->reject('reservation', 'Anggota nonaktif tidak dapat membuat reservasi.');
            }

            $book = Book::query()->whereKey($bookId)->lockForUpdate()->firstOrFail();
            $this->promoteAvailableQueue($book->id);

            $copyCount = $book->copies()->count();
            $borrowedCount = $book->copies()->where('status', 'borrowed')->count();
            if ($copyCount === 0 || $borrowedCount !== $copyCount) {
                $this->reject('reservation', 'Reservasi hanya tersedia saat semua eksemplar sedang dipinjam.');
            }

            $alreadyReserved = Reservation::query()
                ->where('member_id', $member->id)
                ->where('book_id', $book->id)
                ->whereNull('fulfilled_at')
                ->where(fn ($query) => $query
                    ->where('status', 'pending')
                    ->orWhere(fn ($query) => $query
                        ->where('status', 'ready')
                        ->where('expires_at', '>', now())))
                ->exists();
            if ($alreadyReserved) {
                $this->reject('reservation', 'Anggota sudah memiliki reservasi aktif untuk buku ini.');
            }

            return Reservation::query()->create([
                'member_id' => $member->id,
                'book_id' => $book->id,
                'reserved_at' => now(),
                'status' => 'pending',
            ]);
        });
    }

    public function promoteAvailableQueue(int $bookId): void
    {
        Book::query()->whereKey($bookId)->lockForUpdate()->firstOrFail();
        $now = now();

        Reservation::query()
            ->where('book_id', $bookId)
            ->where('status', 'ready')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->update(['status' => 'expired']);

        $availableCopies = BookCopy::query()
            ->where('book_id', $bookId)
            ->where('status', 'available')
            ->count();
        $readyCount = Reservation::query()
            ->where('book_id', $bookId)
            ->where('status', 'ready')
            ->whereNull('fulfilled_at')
            ->where('expires_at', '>', $now)
            ->count();

        while ($readyCount < $availableCopies) {
            $next = Reservation::query()
                ->where('book_id', $bookId)
                ->where('status', 'pending')
                ->whereNull('fulfilled_at')
                ->orderBy('reserved_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                break;
            }

            $next->update([
                'status' => 'ready',
                'expires_at' => $now->copy()->addDays(2),
            ]);
            $readyCount++;
        }
    }

    public function readyForCheckout(int $bookId, int $memberId): ?Reservation
    {
        $this->promoteAvailableQueue($bookId);
        $reservation = Reservation::query()
            ->where('book_id', $bookId)
            ->where('status', 'ready')
            ->whereNull('fulfilled_at')
            ->where('expires_at', '>', now())
            ->orderBy('reserved_at')
            ->orderBy('id')
            ->first();

        if ($reservation && $reservation->member_id !== $memberId) {
            $this->reject('copies', 'Eksemplar ini sedang disiapkan untuk anggota yang memiliki reservasi.');
        }

        return $reservation;
    }

    public function hasActiveReservations(int $bookId): bool
    {
        return Reservation::query()
            ->where('book_id', $bookId)
            ->whereNull('fulfilled_at')
            ->where(fn ($query) => $query
                ->where('status', 'pending')
                ->orWhere(fn ($query) => $query
                    ->where('status', 'ready')
                    ->where('expires_at', '>', now())))
            ->exists();
    }

    public function fulfill(Reservation $reservation): void
    {
        $reservation->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
        ]);
    }

    public function refreshMemberQueue(Member $member): void
    {
        Reservation::query()
            ->where('member_id', $member->id)
            ->whereIn('status', ['pending', 'ready'])
            ->distinct()
            ->pluck('book_id')
            ->each(fn (int $bookId) => $this->promoteAvailableQueue($bookId));
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
