<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ActivityReport\BuildLineDescription;
use App\Actions\ActivityReport\CollectDayContext;
use App\Actions\ActivityReport\SummarizeReportLines;
use App\Enums\ActivityReportExportFormat;
use App\Enums\ActivityReportStatus;
use App\Enums\ActivityReportStep;
use App\Events\ActivityReportProgress;
use App\Exceptions\AiSummarizationException;
use App\Models\ActivityReport;
use App\Models\Session;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateActivityReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ActivityReport $report,
        public readonly bool $useAiSummary = false,
    ) {}

    public function handle(
        CollectDayContext $collectDayContext,
        BuildLineDescription $buildLineDescription,
        SummarizeReportLines $summarizeReportLines,
        GeneralSettings $generalSettings,
    ): void {
        event(new ActivityReportProgress($this->report->id, ActivityReportStep::CollectingContext));

        $this->report->load('client.projects');
        $projectIds = $this->report->client->projects->pluck('id')->all();

        $dateFromYearAndMonth = CarbonImmutable::create($this->report->year, $this->report->month, 1);

        $sessions = Session::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('ended_at')
            ->whereBetween('started_at', [$dateFromYearAndMonth->startOfMonth(), $dateFromYearAndMonth->endOfMonth()])
            ->get();

        $groups = [];

        foreach ($sessions as $session) {
            $key = $session->project_id.'::'.$session->date->format('Y-m-d');

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'project_id' => $session->project_id,
                    'date' => $session->date,
                    'minutes' => 0,
                ];
            }

            $groups[$key]['minutes'] += $session->rounded_minutes ?? $session->duration_minutes ?? 0;
        }

        event(new ActivityReportProgress($this->report->id, ActivityReportStep::BuildingLines));

        $projectsById = $this->report->client->projects->keyBy('id');
        $totalMinutes = 0;
        $totalDays = 0.0;
        $totalAmountHt = 0;
        $hasAmount = false;

        foreach ($groups as $group) {
            $project = $projectsById->get($group['project_id']);

            if ($project === null) {
                continue;
            }

            $minutes = $group['minutes'];
            $dailyReferenceMinutes = ($project->daily_reference_hours ?? $generalSettings->default_daily_reference_hours) * 60;
            $days = $dailyReferenceMinutes > 0
                ? round($minutes / $dailyReferenceMinutes, 2)
                : 0;

            $context = $collectDayContext->handle($project, $group['date']);
            $description = $buildLineDescription->handle($context);

            $this->report->lines()->create([
                'project_id' => $group['project_id'],
                'date' => $group['date']->format('Y-m-d'),
                'minutes' => $minutes,
                'days' => $days,
                'description' => $description ?: null,
            ]);

            $totalMinutes += $minutes;
            $totalDays += $days;

            if ($project->daily_rate !== null) {
                $totalAmountHt += (int) round($days * $project->daily_rate * 100);
                $hasAmount = true;
            }
        }

        if ($this->useAiSummary) {
            event(new ActivityReportProgress($this->report->id, ActivityReportStep::Summarizing));

            try {
                $summarizeReportLines->handle($this->report);
            } catch (AiSummarizationException $e) {
                event(new ActivityReportProgress($this->report->id, ActivityReportStep::Summarizing, $e->getMessage()));
            }
        }

        event(new ActivityReportProgress($this->report->id, ActivityReportStep::GeneratingFiles));

        foreach (ActivityReportExportFormat::cases() as $format) {
            $format->generateFor($this->report);
        }

        $this->report->update([
            'status' => ActivityReportStatus::Finalized,
            'total_minutes' => $totalMinutes,
            'total_days' => $totalDays,
            'total_amount_ht' => $hasAmount ? $totalAmountHt : null,
            'generated_at' => now(),
        ]);

        event(new ActivityReportProgress($this->report->id, ActivityReportStep::Completed));
    }

    public function failed(?Throwable $exception): void
    {
        $this->report->update(['status' => ActivityReportStatus::Failed]);

        event(new ActivityReportProgress(
            $this->report->id,
            ActivityReportStep::Failed,
            $exception?->getMessage(),
        ));
    }
}
