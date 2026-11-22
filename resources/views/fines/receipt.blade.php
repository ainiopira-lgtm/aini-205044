<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12pt; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9pt; }
        h1 { margin: 0 0 4pt; font-size: 14pt; text-align: center; }
        .subtitle { margin: 0 0 14pt; color: #64748b; text-align: center; }
        .line { border-top: 1px dashed #94a3b8; margin: 10pt 0; }
        table { width: 100%; }
        td { padding: 3pt 0; vertical-align: top; }
        .label { color: #64748b; }
        .amount { font-size: 13pt; font-weight: bold; }
        .footer { margin-top: 16pt; color: #64748b; font-size: 8pt; text-align: center; }
    </style>
</head>
<body>
    <h1>BUKTI PEMBAYARAN DENDA</h1>
    <p class="subtitle">PustakaKu · Struk #{{ $fine->id }}</p>
    <div class="line"></div>
    <table>
        <tr><td class="label">Anggota</td><td>{{ $fine->loan->member->name }}</td></tr>
        <tr><td class="label">Nomor anggota</td><td>{{ $fine->loan->member->membership_number }}</td></tr>
        <tr><td class="label">Buku</td><td>{{ $fine->loan->bookCopy->book->title }}</td></tr>
        <tr><td class="label">Hari terlambat</td><td>{{ $fine->days_late }} hari</td></tr>
        <tr><td class="label">Tanggal bayar</td><td>{{ $fine->paid_at->format('d M Y H:i') }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        <tr><td class="amount">Total dibayar</td><td class="amount" align="right">Rp {{ number_format((float) $fine->amount, 0, ',', '.') }}</td></tr>
    </table>
    <p class="footer">Terima kasih. Simpan struk ini sebagai bukti pembayaran.</p>
</body>
</html>
