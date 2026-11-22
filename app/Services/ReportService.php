<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const REPORT_TYPES = [
        'loans' => 'Peminjaman',
        'returns' => 'Pengembalian',
        'fines' => 'Denda',
        'popular' => 'Buku terpopuler',
        'inventory' => 'Inventaris',
    ];

    /**
     * @return array<string, string>
     */
    public function reportTypes(): array
    {
        return self::REPORT_TYPES;
    }

    /**
     * @return array{title: string, headers: list<string>, rows: list<list<mixed>>}
     */
    public function build(string $type, CarbonInterface $from, CarbonInterface $to): array
    {
        $definition = match ($type) {
            'loans' => [
                'headers' => ['Tanggal pinjam', 'No. anggota', 'Nama anggota', 'Buku', 'Barcode', 'Jatuh tempo', 'Status'],
                'rows' => $this->loanRows($from, $to),
            ],
            'returns' => [
                'headers' => ['Tanggal kembali', 'No. anggota', 'Nama anggota', 'Buku', 'Barcode', 'Tanggal pinjam'],
                'rows' => $this->returnRows($from, $to),
            ],
            'fines' => [
                'headers' => ['Tanggal denda', 'No. anggota', 'Nama anggota', 'Buku', 'Barcode', 'Hari terlambat', 'Denda (Rp)', 'Status', 'Dibayar pada'],
                'rows' => $this->fineRows($from, $to),
            ],
            'popular' => [
                'headers' => ['Buku', 'Jumlah peminjaman'],
                'rows' => $this->popularBookRows($from, $to),
            ],
            'inventory' => [
                'headers' => ['Buku', 'ISBN', 'Kategori', 'Tahun', 'Total eksemplar', 'Tersedia', 'Dipinjam', 'Rusak', 'Hilang', 'Harga (Rp)'],
                'rows' => $this->inventoryRows(),
            ],
            default => throw new \InvalidArgumentException('Jenis laporan tidak dikenal.'),
        };

        return [
            'title' => self::REPORT_TYPES[$type],
            'headers' => $definition['headers'],
            'rows' => $definition['rows'],
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function loanRows(CarbonInterface $from, CarbonInterface $to): array
    {
        return Loan::query()
            ->with([
                'member:id,name,membership_number',
                'bookCopy:id,book_id,barcode',
                'bookCopy.book:id,title',
            ])
            ->whereBetween('loan_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('loan_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Loan $loan): array => [
                $loan->loan_date->toDateString(),
                $loan->member->membership_number,
                $loan->member->name,
                $loan->bookCopy->book->title,
                $loan->bookCopy->barcode,
                $loan->due_date->toDateString(),
                match ($loan->status) {
                    'overdue' => 'Terlambat',
                    'returned' => 'Dikembalikan',
                    default => 'Aktif',
                },
            ])
            ->all();
    }

    /**
     * @return list<list<mixed>>
     */
    private function returnRows(CarbonInterface $from, CarbonInterface $to): array
    {
        return Loan::query()
            ->with([
                'member:id,name,membership_number',
                'bookCopy:id,book_id,barcode',
                'bookCopy.book:id,title',
            ])
            ->whereNotNull('return_date')
            ->whereBetween('return_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('return_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Loan $loan): array => [
                $loan->return_date->toDateString(),
                $loan->member->membership_number,
                $loan->member->name,
                $loan->bookCopy->book->title,
                $loan->bookCopy->barcode,
                $loan->loan_date->toDateString(),
            ])
            ->all();
    }

    /**
     * @return list<list<mixed>>
     */
    private function fineRows(CarbonInterface $from, CarbonInterface $to): array
    {
        return Fine::query()
            ->with([
                'loan:id,member_id,book_copy_id',
                'loan.member:id,name,membership_number',
                'loan.bookCopy:id,book_id,barcode',
                'loan.bookCopy.book:id,title',
            ])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Fine $fine): array => [
                $fine->created_at->toDateString(),
                $fine->loan->member->membership_number,
                $fine->loan->member->name,
                $fine->loan->bookCopy->book->title,
                $fine->loan->bookCopy->barcode,
                $fine->days_late,
                (float) $fine->amount,
                $fine->status === 'paid' ? 'Lunas' : 'Belum lunas',
                $fine->paid_at?->toDateString() ?? '—',
            ])
            ->all();
    }

    /**
     * @return list<list<mixed>>
     */
    private function popularBookRows(CarbonInterface $from, CarbonInterface $to): array
    {
        return DB::table('books')
            ->join('book_copies', 'books.id', '=', 'book_copies.book_id')
            ->join('loans', 'book_copies.id', '=', 'loans.book_copy_id')
            ->whereBetween('loans.loan_date', [$from->toDateString(), $to->toDateString()])
            ->select('books.title')
            ->selectRaw('COUNT(loans.id) as loan_count')
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('loan_count')
            ->orderBy('books.title')
            ->get()
            ->map(fn (object $book): array => [$book->title, (int) $book->loan_count])
            ->all();
    }

    /**
     * @return list<list<mixed>>
     */
    private function inventoryRows(): array
    {
        return Book::query()
            ->with(['category:id,name'])
            ->withCount([
                'copies',
                'copies as available_copies_count' => fn ($query) => $query->where('status', 'available'),
                'copies as borrowed_copies_count' => fn ($query) => $query->where('status', 'borrowed'),
                'copies as damaged_copies_count' => fn ($query) => $query->where('status', 'damaged'),
                'copies as lost_copies_count' => fn ($query) => $query->where('status', 'lost'),
            ])
            ->orderBy('title')
            ->get()
            ->map(fn (Book $book): array => [
                $book->title,
                $book->isbn ?? '—',
                $book->category->name,
                $book->year,
                $book->copies_count,
                $book->available_copies_count,
                $book->borrowed_copies_count,
                $book->damaged_copies_count,
                $book->lost_copies_count,
                (float) $book->price,
            ])
            ->all();
    }
}
