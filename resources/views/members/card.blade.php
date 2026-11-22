<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; color: #172033; }
        .card { width: 242.65pt; height: 153.07pt; box-sizing: border-box; padding: 14pt; background: #f8fafc; }
        .brand { margin: 0 0 10pt; font-size: 9pt; font-weight: bold; color: #334155; text-transform: uppercase; letter-spacing: 1pt; }
        .photo { width: 68pt; height: 82pt; object-fit: cover; border-radius: 4pt; }
        .placeholder { width: 68pt; height: 82pt; line-height: 82pt; text-align: center; background: #e2e8f0; font-size: 28pt; color: #475569; }
        .details { padding-left: 12pt; vertical-align: middle; }
        .name { margin: 0 0 6pt; font-size: 13pt; font-weight: bold; }
        .number { margin: 0; font-size: 9pt; color: #475569; }
        .qr { width: 70pt; height: 70pt; }
        .bottom { margin-top: 8pt; font-size: 7pt; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <p class="brand">Kartu Anggota · PustakaKu</p>
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="72pt" valign="middle">
                    @if ($photoDataUri)
                        <img class="photo" src="{{ $photoDataUri }}" alt="Foto anggota">
                    @else
                        <div class="placeholder">{{ mb_substr($member->name, 0, 1) }}</div>
                    @endif
                </td>
                <td class="details">
                    <p class="name">{{ $member->name }}</p>
                    <p class="number">{{ $member->membership_number }}</p>
                </td>
                <td width="72pt" align="right" valign="middle">
                    <img class="qr" src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR code nomor anggota">
                </td>
            </tr>
        </table>
        <p class="bottom">Tunjukkan kartu ini saat melakukan peminjaman.</p>
    </div>
</body>
</html>
