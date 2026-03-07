<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport\Exports;

use App\Actions\Action;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportActivityReportCsv extends Action
{
    public function handle(ActivityReport $report): string
    {
        $report->load(['client', 'lines.project']);

        $clientSlug = Str::slug($report->client->name);
        $path = "reports/cra-{$clientSlug}-{$report->year}-{$report->month}.csv";

        $lines = $report->lines->sortBy('date');

        $rows = [];
        $rows[] = ['Date', 'Project', 'Hours', 'Days', 'Description'];

        foreach ($lines as $line) {
            $rows[] = [
                $line->date->format('Y-m-d'),
                $line->project?->name ?? '',
                number_format($line->minutes / 60, 2),
                number_format((float) $line->days, 2),
                $line->description ?? '',
            ];
        }

        $rows[] = [
            'TOTAL',
            '',
            number_format($report->total_minutes / 60, 2),
            number_format((float) $report->total_days, 2),
            '',
        ];

        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::put("private/{$path}", (string) $csv);

        $report->update(['csv_path' => $path]);

        return $path;
    }
}
