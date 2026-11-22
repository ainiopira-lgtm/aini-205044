<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    /**
     * @var array<class-string<Model>, list<string>>
     */
    private const TRACKED_FIELDS = [
        Book::class => ['title', 'isbn', 'category_id', 'publisher_id', 'shelf_id', 'year', 'synopsis', 'cover', 'price'],
        Member::class => ['user_id', 'membership_number', 'name', 'email', 'phone', 'address', 'photo', 'joined_at', 'status'],
        Loan::class => ['member_id', 'book_copy_id', 'user_id', 'loan_date', 'due_date', 'return_date', 'reminder_sent_at', 'extension_count', 'status'],
        BookCopy::class => ['book_id', 'barcode', 'status', 'condition'],
        Fine::class => ['loan_id', 'amount', 'days_late', 'status', 'paid_at'],
        Reservation::class => ['member_id', 'book_id', 'reserved_at', 'expires_at', 'fulfilled_at', 'status'],
    ];

    private const SENSITIVE_FIELDS = ['user_id', 'email', 'phone', 'address', 'photo'];

    public function created(Model $model): void
    {
        $this->record($model, 'created', $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated', $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', $model->getAttributes());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(Model $model, string $event, array $attributes): void
    {
        $fields = self::TRACKED_FIELDS[$model::class] ?? [];
        $properties = array_intersect_key($attributes, array_flip($fields));
        if ($event === 'updated' && $properties === []) {
            return;
        }
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $properties)) {
                $properties[$field] = '[redacted]';
            }
        }

        $entity = strtolower(class_basename($model));
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'action' => "{$entity}.{$event}",
            'description' => ucfirst($entity).' '.$event,
            'properties' => $properties,
        ]);
    }
}
