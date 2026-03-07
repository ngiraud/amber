<?php

declare(strict_types=1);

use App\Actions\ActivityReport\Exports\ExportActivityReportCsv;
use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use App\Models\ActivityReportLine;
use Illuminate\Support\Facades\Storage;

pest()->group('activity-report', 'actions');

beforeEach(function () {
    $this->disk = config('activity.reports.disk');
    Storage::fake($this->disk);
});

describe('ExportActivityReportCsv action', function () {
    it('writes a CSV file to storage with the correct path', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'total_minutes' => 120,
            'total_days' => 0.25,
        ]);
        ActivityReportLine::factory()->create([
            'activity_report_id' => $report->id,
            'date' => '2026-03-15',
            'minutes' => 120,
            'days' => 0.25,
            'description' => 'Feature work',
        ]);

        $path = ExportActivityReportCsv::make()->handle($report);

        expect($path)->toBe(ActivityReportExportFormat::Csv->pathFor($report));
        Storage::disk($this->disk)->assertExists($path);
    });

    it('saves the CSV path on the report model', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'total_minutes' => 60,
            'total_days' => 0.13,
        ]);
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id]);

        ExportActivityReportCsv::make()->handle($report);

        expect($report->fresh()->csv_path)->toBe(ActivityReportExportFormat::Csv->pathFor($report));
    });

    it('includes a header row and a totals row in the CSV', function () {
        $report = ActivityReport::factory()->finalized()->create([
            'total_minutes' => 90,
            'total_days' => 0.19,
        ]);
        ActivityReportLine::factory()->create([
            'activity_report_id' => $report->id,
            'minutes' => 90,
            'days' => 0.19,
        ]);

        $path = ExportActivityReportCsv::make()->handle($report);
        $content = Storage::disk($this->disk)->get($path);

        expect($content)
            ->toContain('Date,Project,Hours,Days,Description')
            ->toContain('TOTAL');
    });
});
