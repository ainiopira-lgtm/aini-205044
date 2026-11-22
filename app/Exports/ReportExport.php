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
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
