<?php

declare(strict_types=1);

use App\Actions\ActivityReport\SummarizeReportLines;
use App\Ai\Agents\ReportSummarizer;
use App\Models\ActivityReport;
use App\Models\ActivityReportLine;
use App\Settings\AiSettings;

pest()->group('actions', 'activity-report');

describe('SummarizeReportLines', function () {
    beforeEach(function () {
        $settings = app(AiSettings::class);
        $settings->enabled = true;
        $settings->summary_language = 'en';
        $settings->save();
    });

    it('does nothing when AI is disabled', function () {
        $settings = app(AiSettings::class);
        $settings->enabled = false;
        $settings->save();

        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Fixed bug']);

        ReportSummarizer::fake();

        SummarizeReportLines::make()->handle($report);

        ReportSummarizer::assertNeverPrompted();
    });

    it('does nothing when no lines have descriptions', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => null]);

        ReportSummarizer::fake();

        SummarizeReportLines::make()->handle($report);

        ReportSummarizer::assertNeverPrompted();
    });

    it('persists summaries returned by the agent', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $line = ActivityReportLine::factory()->create([
            'activity_report_id' => $report->id,
            'description' => 'Implemented feature X',
        ]);

        ReportSummarizer::fake(fn () => ['summaries' => [['id' => $line->id, 'summary' => 'Delivered feature X.']]]);

        SummarizeReportLines::make()->handle($report);

        expect($line->fresh()->summary)->toBe('Delivered feature X.');
    });

    it('logs a warning and does not throw when the agent fails', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create([
            'activity_report_id' => $report->id,
            'description' => 'Refactored module',
        ]);

        ReportSummarizer::fake(function () {
            throw new RuntimeException('API error');
        });

        SummarizeReportLines::make()->handle($report);

        ReportSummarizer::assertPrompted(fn () => true);
    });
});
