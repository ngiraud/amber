<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport\Exports;

use App\Actions\Action;
use App\Actions\ActivityReport\Exports\Contracts\ExportActivityReport;
use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;

class ExportActivityReportCsv extends Action implements ExportActivityReport
{
    public function handle(ActivityReport $report): string
    {
        $report->loadMissing(['client', 'lines.project']);

        $lines = $report->lines->sortBy('date');

        $rows = [];
        $rows[] = [__('Date'), __('Project'), __('Hours'), __('Days'), __('Description')];

        foreach ($lines as $line) {
            $rows[] = [
                $line->date->format('Y-m-d'),
                $line->project->name ?? '',
                number_format($line->minutes / 60, 2),
                number_format((float) $line->days, 2),
                $line->summary ?? $line->description ?? '',
            ];
        }

        $rows[] = [
            __('TOTAL'),
            '',
            number_format($report->total_minutes / 60, 2),
            number_format((float) $report->total_days, 2),
            '',
        ];

        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv(stream: $handle, fields: $row, separator: config('activity.reports.csv.delimiter'), escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $path = ActivityReportExportFormat::Csv->pathFor($report);

        Storage::disk(config('activity.reports.disk'))->put($path, (string) $csv);

        $report->update(['csv_path' => $path]);

        return $path;
    }
}
