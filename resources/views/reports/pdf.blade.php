<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { margin: 0 0 5px; font-size: 18px; }
        p { margin: 0 0 16px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $report['title'] }}</h1>
    <p>Periode {{ $fromDate->format('d/m/Y') }}–{{ $toDate->format('d/m/Y') }}</p>
    <table>
        <thead>
            <tr>
                @foreach ($report['headers'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td>
                            @if (is_float($value) && str_contains(strtolower($report['headers'][$index]), '(rp)'))
                                Rp {{ number_format($value, 0, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($report['headers']) }}">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
