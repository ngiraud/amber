<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ActivityReport\BuildLineDescription;
use App\Actions\ActivityReport\CollectDayContext;
use App\Actions\ActivityReport\Exports\ExportActivityReportCsv;
use App\Actions\ActivityReport\Exports\ExportActivityReportPdf;
use App\Enums\ActivityReportStatus;
use App\Enums\ActivityReportStep;
use App\Events\ActivityReportProgress;
use App\Models\ActivityReport;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateActivityReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ActivityReport $report) {}

    public function handle(
        CollectDayContext $collectDayContext,
        BuildLineDescription $buildLineDescription,
        ExportActivityReportPdf $exportPdf,
        ExportActivityReportCsv $exportCsv,
    ): void {
        $report = $this->report;

        event(new ActivityReportProgress($report->id, ActivityReportStep::CollectingContext));

        $report->load('client.projects');
        $projectIds = $report->client->projects->pluck('id')->all();

        $sessions = Session::query()
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('ended_at')
            ->whereYear('started_at', $report->year)
            ->whereMonth('started_at', $report->month)
            ->get();

        $groups = [];

        foreach ($sessions as $session) {
            $date = $session->date
                ? $session->date->format('Y-m-d')
                : CarbonImmutable::parse($session->started_at)->format('Y-m-d');

            $key = $session->project_id.'::'.$date;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'project_id' => $session->project_id,
                    'date' => $date,
                    'minutes' => 0,
                ];
            }

            $groups[$key]['minutes'] += $session->rounded_minutes ?? $session->duration_minutes ?? 0;
        }

        event(new ActivityReportProgress($report->id, ActivityReportStep::BuildingLines));

        $projectsById = $report->client->projects->keyBy('id');
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
            $dailyReferenceMinutes = ($project->daily_reference_hours ?? 8) * 60;
            $days = $dailyReferenceMinutes > 0
                ? round($minutes / $dailyReferenceMinutes, 2)
                : 0;

            $date = CarbonImmutable::parse($group['date']);
            $context = $collectDayContext->handle($project, $date);
            $description = $buildLineDescription->handle($context);

            $report->lines()->create([
                'project_id' => $group['project_id'],
                'date' => $group['date'],
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

        event(new ActivityReportProgress($report->id, ActivityReportStep::GeneratingFiles));

        $exportPdf->handle($report);
        $exportCsv->handle($report);

        $report->update([
            'status' => ActivityReportStatus::Draft,
            'total_minutes' => $totalMinutes,
            'total_days' => $totalDays,
            'total_amount_ht' => $hasAmount ? $totalAmountHt : null,
            'generated_at' => now(),
        ]);

        event(new ActivityReportProgress($report->id, ActivityReportStep::Completed));
    }

    public function failed(?Throwable $exception): void
    {
        $this->report->update(['status' => ActivityReportStatus::Draft]);

        event(new ActivityReportProgress(
            $this->report->id,
            ActivityReportStep::Failed,
            $exception?->getMessage(),
        ));
    }
}
