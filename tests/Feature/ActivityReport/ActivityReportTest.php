<?php

declare(strict_types=1);

use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;

pest()->group('activity-report', 'models');

beforeEach(function () {
    $this->disk = config('activity.reports.disk');
    Storage::fake($this->disk);
});

describe('canBeDeleted', function () {
    it('returns true for draft, failed, and finalized reports', function (ActivityReport $report) {
        expect($report->canBeDeleted())->toBeTrue();
    })->with([
        'draft' => fn () => ActivityReport::factory()->draft()->create(),
        'failed' => fn () => ActivityReport::factory()->failed()->create(),
        'finalized' => fn () => ActivityReport::factory()->finalized()->create(),
    ]);

    it('returns false for generating and sent reports', function (ActivityReport $report) {
        expect($report->canBeDeleted())->toBeFalse();
    })->with([
        'generating' => fn () => ActivityReport::factory()->generating()->create(),
        'sent' => fn () => ActivityReport::factory()->sent()->create(),
    ]);
});

describe('fileExists', function () {
    it('returns true when the file exists on disk', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $path = ActivityReportExportFormat::Pdf->pathFor($report);
        Storage::disk($this->disk)->put($path, 'pdf content');

        expect($report->fileExists(ActivityReportExportFormat::Pdf))->toBeTrue();
    });

    it('returns false when the file does not exist on disk', function () {
        $report = ActivityReport::factory()->finalized()->create();

        expect($report->fileExists(ActivityReportExportFormat::Pdf))->toBeFalse();
    });
});

describe('deleteFiles', function () {
    it('deletes all format files from disk', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $pdfPath = ActivityReportExportFormat::Pdf->pathFor($report);
        $csvPath = ActivityReportExportFormat::Csv->pathFor($report);
        Storage::disk($this->disk)->put($pdfPath, 'pdf');
        Storage::disk($this->disk)->put($csvPath, 'csv');

        $report->deleteFiles();

        Storage::disk($this->disk)->assertMissing($pdfPath);
        Storage::disk($this->disk)->assertMissing($csvPath);
    });

    it('does not fail when files do not exist on disk', function () {
        $report = ActivityReport::factory()->finalized()->create();

        expect(fn () => $report->deleteFiles())->not->toThrow(Exception::class);
    });

    it('nulls pdf_path and csv_path in database when saved', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'pdf_path' => 'reports/old.pdf',
            'csv_path' => 'reports/old.csv',
        ]);

        $report->deleteFiles(shouldSave: true);

        expect($report->fresh())
            ->pdf_path->toBeNull()
            ->csv_path->toBeNull();
    });

    it('does not persist nulled paths without shouldSave', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'pdf_path' => 'reports/old.pdf',
            'csv_path' => 'reports/old.csv',
        ]);

        $report->deleteFiles();

        expect($report->fresh())
            ->pdf_path->toBe('reports/old.pdf')
            ->csv_path->toBe('reports/old.csv');
    });

    it('does persist nulled paths without shouldSave but saving after', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'pdf_path' => 'reports/old.pdf',
            'csv_path' => 'reports/old.csv',
        ]);

        $report->deleteFiles();

        expect($report->fresh())
            ->pdf_path->toBe('reports/old.pdf')
            ->csv_path->toBe('reports/old.csv');

        $report->update([
            'total_minutes' => 123,
        ]);

        expect($report->fresh())
            ->pdf_path->toBeNull()
            ->csv_path->toBeNull();
    });
});
