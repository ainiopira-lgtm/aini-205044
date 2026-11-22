<?php

namespace App\Services;

use App\Models\Fine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FineService
{
    public function pay(Fine $fine): Fine
    {
        return DB::transaction(function () use ($fine): Fine {
            $fine = Fine::query()->whereKey($fine->id)->lockForUpdate()->firstOrFail();
            if ($fine->status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'fine' => 'Denda ini sudah dibayar.',
                ]);
            }

            $fine->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $fine->refresh();
        });
    }
}
