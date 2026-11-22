<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    public function __construct(
        private readonly array $headers,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (mixed $value): mixed => $this->safeCell($value),
                $row,
            ),
            $this->rows,
        );
    }

    public function headings(): array
    {
        return $this->headers;
    }

    private function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || preg_match('/^[\s\x00-\x1F]*[=+\-@]/', $value) !== 1) {
            return $value;
        }

        return "'".$value;
    }
}
