<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportAlreadyFinalizedException;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;

class RegenerateActivityReport extends Action
{
    public function handle(ActivityReport $report): ActivityReport
    {
        if (in_array($report->status, [ActivityReportStatus::Finalized, ActivityReportStatus::Sent], true)) {
            throw new ActivityReportAlreadyFinalizedException;
        }

        $report->lines()->delete();

        if ($report->pdf_path && Storage::exists($report->pdf_path)) {
            Storage::delete($report->pdf_path);
        }

        if ($report->csv_path && Storage::exists($report->csv_path)) {
            Storage::delete($report->csv_path);
        }

        $report->update([
            'status' => ActivityReportStatus::Generating,
            'total_minutes' => 0,
            'total_days' => 0,
            'total_amount_ht' => null,
            'generated_at' => null,
            'pdf_path' => null,
            'csv_path' => null,
        ]);

        GenerateActivityReportJob::dispatch($report);

        return $report;
    }
}
