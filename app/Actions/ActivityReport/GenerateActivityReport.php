<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\ActivityReportData;
use App\Enums\ActivityReportStatus;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;

class GenerateActivityReport extends Action
{
    public function __construct(protected DeleteActivityReport $deleteReport) {}

    public function handle(ActivityReportData $data): ActivityReport
    {
        $existing = ActivityReport::query()
            ->where('client_id', $data->clientId)
            ->forPeriod($data->month, $data->year)
            ->first();

        if ($existing !== null) {
            $this->deleteReport->handle($existing);
        }

        $report = ActivityReport::query()->create([
            'client_id' => $data->clientId,
            'month' => $data->month,
            'year' => $data->year,
            'status' => ActivityReportStatus::Generating,
            'notes' => $data->notes,
        ]);

        GenerateActivityReportJob::dispatch($report);

        return $report;
    }
}
