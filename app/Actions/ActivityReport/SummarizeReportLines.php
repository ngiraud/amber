<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Ai\Agents\ReportSummarizer;
use App\Exceptions\AiSummarizationException;
use App\Models\ActivityReport;
use App\Models\ActivityReportLine;
use App\Settings\AiSettings;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Exceptions\AiException;
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

        $this->settings->syncConfigApiKey();

        $lines = $report->lines->whereNotNull('description');

        if ($lines->isEmpty()) {
            return;
        }

        $prompt = $lines->map(fn (ActivityReportLine $line) => sprintf(
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
        } catch (AiException $e) {
            throw AiSummarizationException::fromAiException($e);
        } catch (Throwable $e) {
            throw AiSummarizationException::fromUnexpected($e);
        }

        /** @var array<int, array{id: string, summary: string}> $summaries */
        $summaries = $response['summaries'] ?? [];

        $linesById = $lines->keyBy('id');

        DB::transaction(function () use ($summaries, $linesById) {
            foreach ($summaries as $item) {
                $linesById->get($item['id'])?->update(['summary' => $item['summary']]);
            }
        });
    }
}
