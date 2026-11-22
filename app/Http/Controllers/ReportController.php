<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        [$type, $from, $to] = $this->filters($request);
        $report = $this->reports->build($type, $from, $to);

        return view('reports.index', [
            'report' => $report,
            'reportType' => $type,
            'reportTypes' => $this->reports->reportTypes(),
            'fromDate' => $from,
            'toDate' => $to,
        ]);
    }

    public function export(Request $request, string $format): BinaryFileResponse|Response
    {
        [$type, $from, $to] = $this->filters($request);
        $report = $this->reports->build($type, $from, $to);
        $filename = sprintf('%s-%s-%s', $type, $from->format('Ymd'), $to->format('Ymd'));

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', [
                'report' => $report,
                'fromDate' => $from,
                'toDate' => $to,
            ])->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }

        return Excel::download(new ReportExport($report['headers'], $report['rows']), $filename.'.xlsx');
    }

    /**
     * @return array{string, CarbonImmutable, CarbonImmutable}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'report' => ['nullable', 'in:'.implode(',', array_keys($this->reports->reportTypes()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? today()->toDateString())->endOfDay();
        if ($from->gt($to)) {
            throw ValidationException::withMessages(['to' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.']);
        }

        return [
            $validated['report'] ?? 'loans',
            $from,
            $to,
        ];
    }
}
