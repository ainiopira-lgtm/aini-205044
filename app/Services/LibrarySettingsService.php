<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;

class LibrarySettingsService
{
    private const CACHE_TTL_MINUTES = 60;

    /**
     * @var list<string>
     */
    private const CIRCULATION_KEYS = [
        'loan_duration_days',
        'max_active_loans',
        'max_renewals',
        'fine_per_day',
    ];

    /**
     * @return array<string, int>
     */
    public function circulationValues(): array
    {
        $values = [];
        foreach (self::CIRCULATION_KEYS as $key) {
            $values[$key] = $this->getInt($key);
        }

        return $values;
    }

    public function getInt(string $key): int
    {
        $value = Cache::remember(
            $this->cacheKey($key),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Setting::query()->where('key', $key)->value('value'),
        );

        if (! is_string($value) || ! ctype_digit($value)) {
            throw new LogicException("Pengaturan [{$key}] belum diatur dengan nilai bilangan bulat.");
        }

        $number = (int) $value;
        $minimum = in_array($key, ['fine_per_day', 'max_renewals'], true) ? 0 : 1;
        if ($number < $minimum) {
            throw new LogicException("Pengaturan [{$key}] berada di luar rentang yang diizinkan.");
        }

        return $number;
    }

    /**
     * @param  array<string, int|string>  $values
     */
    public function updateCirculationValues(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value],
                );
            }
        });

        foreach ($values as $key => $value) {
            Cache::put(
                $this->cacheKey($key),
                (string) $value,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
            );
        }
    }

    private function cacheKey(string $key): string
    {
        return 'pustakuku.settings.'.$key;
    }
}
