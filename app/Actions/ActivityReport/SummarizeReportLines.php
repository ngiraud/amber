<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Ai\Agents\ReportSummarizer;
use App\Models\ActivityReport;
use App\Settings\AiSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

class SummarizeReportLines extends Action
{
    public function __construct(
        protected readonly AiSettings $settings,
        protected readonly ReportSummarizer $agent,
    ) {}

    public function handle(ActivityReport $report): void
    {
        if (! $this->settings->enabled) {
            return;
        }

        $lines = $report->lines()->whereNotNull('description')->get();

        if ($lines->isEmpty()) {
            return;
        }

        $prompt = $lines->map(fn ($line) => sprintf(
            'ID: %s | Date: %s | Duration: %dmin | Context: %s',
            $line->id,
            $line->date->toDateString(),
            $line->minutes,
            $line->description,
        ))->implode("\n");

        try {
            $response = $this->agent->prompt(
                $prompt,
                provider: $this->settings->provider?->value,
            );

            /** @var array<int, array{id: string, summary: string}> $summaries */
            $summaries = $response['summaries'] ?? [];

            foreach ($summaries as $item) {
                $lines->firstWhere('id', $item['id'])?->update(['summary' => $item['summary']]);
            }
        } catch (Throwable $e) {
            Log::warning('AI summarization failed, using raw descriptions.', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
