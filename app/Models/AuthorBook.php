<?php

namespace App\Models;

use Database\Factories\AuthorBookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AuthorBook extends Pivot
{
    /** @use HasFactory<AuthorBookFactory> */
    use HasFactory;

    protected $table = 'author_book';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['author_id', 'book_id'];

    /** @return BelongsTo<Author, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /** @return BelongsTo<Book, $this> */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
