<?php

declare(strict_types=1);

use App\Actions\ActivityReport\SummarizeReportLines;
use App\Ai\Agents\ReportSummarizer;
use App\Exceptions\AiSummarizationException;
use App\Models\ActivityReport;
use App\Models\ActivityReportLine;
use App\Settings\AiSettings;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;

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

    it('persists summaries for multiple lines in a single batch', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $lineA = ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Feature A', 'date' => '2026-01-01']);
        $lineB = ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Feature B', 'date' => '2026-01-02']);

        ReportSummarizer::fake(fn () => [
            'summaries' => [
                ['id' => $lineA->id, 'summary' => 'Summary A.'],
                ['id' => $lineB->id, 'summary' => 'Summary B.'],
            ],
        ]);

        SummarizeReportLines::make()->handle($report);

        expect($lineA->fresh()->summary)->toBe('Summary A.')
            ->and($lineB->fresh()->summary)->toBe('Summary B.');
    });

    it('ignores summaries for line ids not belonging to the report', function () {
        $report = ActivityReport::factory()->finalized()->create();
        $line = ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Work done']);

        ReportSummarizer::fake(fn () => [
            'summaries' => [
                ['id' => $line->id, 'summary' => 'Valid summary.'],
                ['id' => 'fake-id-not-in-report', 'summary' => 'Should be ignored.'],
            ],
        ]);

        SummarizeReportLines::make()->handle($report);

        expect($line->fresh()->summary)->toBe('Valid summary.');
    });

    it('throws AiSummarizationException when the agent raises a RateLimitedException', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Work done']);

        ReportSummarizer::fake(function () {
            throw RateLimitedException::forProvider('openai');
        });

        expect(fn () => SummarizeReportLines::make()->handle($report))
            ->toThrow(AiSummarizationException::class, 'API rate limit reached. Please try again later.');
    });

    it('throws AiSummarizationException when the agent raises a ProviderOverloadedException', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Work done']);

        ReportSummarizer::fake(function () {
            throw new ProviderOverloadedException('AI provider [openai] is overloaded.');
        });

        expect(fn () => SummarizeReportLines::make()->handle($report))
            ->toThrow(AiSummarizationException::class, 'The AI provider is currently overloaded. Please try again later.');
    });

    it('throws AiSummarizationException with invalid key message on 401 AiException', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Work done']);

        ReportSummarizer::fake(function () {
            throw new AiException('401 Unauthorized');
        });

        expect(fn () => SummarizeReportLines::make()->handle($report))
            ->toThrow(AiSummarizationException::class, 'Invalid API key. Please check your AI settings.');
    });

    it('throws AiSummarizationException with unexpected message on non-AI exception', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->create(['activity_report_id' => $report->id, 'description' => 'Work done']);

        ReportSummarizer::fake(function () {
            throw new RuntimeException('Connection refused');
        });

        expect(fn () => SummarizeReportLines::make()->handle($report))
            ->toThrow(AiSummarizationException::class, 'An unexpected error occurred while summarizing with AI.');
    });
});
