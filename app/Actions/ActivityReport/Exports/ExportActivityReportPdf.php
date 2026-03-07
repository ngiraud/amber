<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport\Exports;

use App\Actions\Action;
use App\Actions\ActivityReport\Exports\Contracts\ExportActivityReport;
use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use Spatie\LaravelPdf\Facades\Pdf;

class ExportActivityReportPdf extends Action implements ExportActivityReport
{
    public function handle(ActivityReport $report): string
    {
        $report->loadMissing(['client', 'lines.project']);

        $path = ActivityReportExportFormat::Pdf->pathFor($report);

        Pdf::view('pdf.activity-report', [
            'report' => $report,
            'client' => $report->client,
            'lines' => $report->lines->sortBy('date'),
        ])
            ->format('a4')
            ->disk(config('activity.reports.disk'))
            ->save($path);

        $report->update(['pdf_path' => $path]);

        return $path;
    }
}
