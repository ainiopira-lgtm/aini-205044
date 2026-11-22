<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'book_copy_id',
        'user_id',
        'loan_date',
        'due_date',
        'return_date',
        'reminder_sent_at',
        'extension_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'reminder_sent_at' => 'datetime',
            'extension_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Member, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /** @return BelongsTo<BookCopy, $this> */
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Fine, $this> */
    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }
}
