<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportAlreadyFinalizedException;
use App\Models\ActivityReport;

class DeleteActivityReport extends Action
{
    public function handle(ActivityReport $report): void
    {
        if (in_array($report->status, [ActivityReportStatus::Finalized, ActivityReportStatus::Sent], true)) {
            throw new ActivityReportAlreadyFinalizedException;
        }

        $report->deleteFiles();
        $report->lines()->delete();
        $report->delete();
    }
}
