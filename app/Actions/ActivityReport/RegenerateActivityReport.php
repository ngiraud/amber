<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportCannotBeModifiedException;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;

class RegenerateActivityReport extends Action
{
    public function handle(ActivityReport $report): ActivityReport
    {
        if (! $report->canBeDeleted()) {
            throw new ActivityReportCannotBeModifiedException;
        }

        $report->deleteFiles();
        $report->lines()->delete();

        $report->update([
            'status' => ActivityReportStatus::Generating,
            'total_minutes' => 0,
            'total_days' => 0,
            'total_amount_ht' => null,
            'generated_at' => null,
        ]);

        GenerateActivityReportJob::dispatch($report);

        return $report;
    }
}
