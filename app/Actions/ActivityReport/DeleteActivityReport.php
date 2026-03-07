<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Exceptions\ActivityReportCannotBeModifiedException;
use App\Models\ActivityReport;

class DeleteActivityReport extends Action
{
    public function handle(ActivityReport $report): void
    {
        if (! $report->canBeDeleted()) {
            throw new ActivityReportCannotBeModifiedException;
        }

        $report->deleteFiles();
        $report->lines()->delete();
        $report->delete();
    }
}
