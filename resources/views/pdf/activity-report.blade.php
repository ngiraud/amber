<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte Rendu d'Activité</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #1a1a1a; padding: 30px; }
        h1 { font-size: 18pt; font-weight: bold; margin-bottom: 4px; }
        h2 { font-size: 12pt; font-weight: bold; margin-bottom: 12px; color: #444; }
        .header { display: flex; justify-content: space-between; margin-bottom: 32px; }
        .header-left { flex: 1; }
        .header-right { flex: 1; text-align: right; }
        .label { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .value { font-size: 10pt; margin-bottom: 8px; }
        .period-box { background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; padding: 10px 16px; display: inline-block; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #1a1a1a; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-size: 9pt; font-weight: bold; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 7px 10px; font-size: 9pt; border-bottom: 1px solid #eee; vertical-align: top; }
        .totals-row { background: #eee !important; font-weight: bold; }
        .totals-row td { border-top: 2px solid #bbb; padding: 9px 10px; }
        .num { text-align: right; }
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px; display: flex; justify-content: space-between; }
        .signature-box { border: 1px solid #ccc; border-radius: 4px; padding: 12px 20px; min-width: 200px; }
        .signature-label { font-size: 8pt; color: #888; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>Compte Rendu d'Activité</h1>
            <div class="value" style="color:#888; margin-top:4px;">
                {{ \Carbon\Carbon::createFromDate($report->year, $report->month, 1)->translatedFormat('F Y') }}
            </div>
        </div>
        <div class="header-right">
            <div class="label">Client</div>
            <div class="value" style="font-weight:bold;">{{ $client->name }}</div>
            @if($client->address)
                @if($client->address['line_1'] ?? null)<div class="value" style="margin-bottom:2px;">{{ $client->address['line_1'] }}</div>@endif
                @if($client->address['line_2'] ?? null)<div class="value" style="margin-bottom:2px;">{{ $client->address['line_2'] }}</div>@endif
                @if(($client->address['zip'] ?? null) || ($client->address['city'] ?? null))
                    <div class="value" style="margin-bottom:2px;">{{ $client->address['zip'] ?? '' }} {{ $client->address['city'] ?? '' }}</div>
                @endif
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Projet</th>
                <th class="num">Heures</th>
                <th class="num">Jours</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $line)
                <tr>
                    <td style="white-space:nowrap;">{{ $line->date->format('d/m/Y') }}</td>
                    <td>{{ $line->project?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($line->minutes / 60, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->days, 2) }}</td>
                    <td>{{ $line->description ?? '' }}</td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="2" style="font-weight:bold;">TOTAL</td>
                <td class="num">{{ number_format($report->total_minutes / 60, 2) }}</td>
                <td class="num">{{ number_format((float) $report->total_days, 2) }}</td>
                <td>
                    @if($report->total_amount_ht !== null)
                        {{ number_format($report->total_amount_ht / 100, 2, ',', ' ') }} € HT
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @if($report->notes)
        <div style="margin-bottom: 24px;">
            <div class="label">Notes</div>
            <div class="value">{{ $report->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <div class="signature-box">
            <div class="signature-label">Signature prestataire</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Signature client</div>
        </div>
    </div>
</body>
</html>
