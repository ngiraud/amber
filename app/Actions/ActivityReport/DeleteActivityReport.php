<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportAlreadyFinalizedException;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;

class DeleteActivityReport extends Action
{
    public function handle(ActivityReport $report): void
    {
        if (in_array($report->status, [ActivityReportStatus::Finalized, ActivityReportStatus::Sent], true)) {
            throw new ActivityReportAlreadyFinalizedException;
        }

        if ($report->pdf_path && Storage::exists($report->pdf_path)) {
            Storage::delete($report->pdf_path);
        }

        if ($report->csv_path && Storage::exists($report->csv_path)) {
            Storage::delete($report->csv_path);
        }

        $report->delete();
    }
}
