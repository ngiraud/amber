<?php

declare(strict_types=1);

use App\Actions\ActivityReport\Exports\ExportActivityReportPdf;
use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

pest()->group('activity-report', 'actions');

beforeEach(function () {
    $this->disk = config('activity.reports.disk');
    Pdf::fake();
});

describe('ExportActivityReportPdf action', function () {
    it('saves the PDF to the correct path', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $expectedPath = ActivityReportExportFormat::Pdf->pathFor($report);

        ExportActivityReportPdf::make()->handle($report);

        Pdf::assertSaved(function (PdfBuilder $pdf, string $path) use ($expectedPath) {
            return $path === $expectedPath;
        });
    });

    it('uses the activity-report view', function () {
        ActivityReport::factory()->finalized()->create();

        ExportActivityReportPdf::make()->handle(ActivityReport::first());

        Pdf::assertViewIs('pdf.activity-report');
    });

    it('saves the pdf_path on the report model', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $expectedPath = ActivityReportExportFormat::Pdf->pathFor($report);

        ExportActivityReportPdf::make()->handle($report);

        expect($report->fresh()->pdf_path)->toBe($expectedPath);
    });

    it('passes the report, client and lines to the view', function () {
        $report = ActivityReport::factory()->finalized()->create();

        ExportActivityReportPdf::make()->handle($report);

        Pdf::assertViewHas('report');
        Pdf::assertViewHas('client');
        Pdf::assertViewHas('lines');
    });
});
