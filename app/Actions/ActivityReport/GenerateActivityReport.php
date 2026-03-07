<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\ActivityReportData;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportAlreadyFinalizedException;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;

class GenerateActivityReport extends Action
{
    public function handle(ActivityReportData $data): ActivityReport
    {
        $existing = ActivityReport::query()
            ->where('client_id', $data->clientId)
            ->forPeriod($data->month, $data->year)
            ->first();

        if ($existing !== null) {
            if (in_array($existing->status, [ActivityReportStatus::Finalized, ActivityReportStatus::Sent], true)) {
                throw new ActivityReportAlreadyFinalizedException;
            }

            $existing->lines()->delete();
            $existing->delete();
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
