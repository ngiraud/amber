<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport\Exports;

use App\Actions\Action;
use App\Models\ActivityReport;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class ExportActivityReportPdf extends Action
{
    public function handle(ActivityReport $report): string
    {
        $report->load(['client', 'lines.project']);

        $clientSlug = Str::slug($report->client->name);
        $path = "reports/cra-{$clientSlug}-{$report->year}-{$report->month}.pdf";

        Pdf::view('pdf.activity-report', [
            'report' => $report,
            'client' => $report->client,
            'lines' => $report->lines->sortBy('date'),
        ])
            ->format('a4')
            ->save(storage_path("app/private/{$path}"));

        $report->update(['pdf_path' => $path]);

        return $path;
    }
}
