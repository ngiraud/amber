<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityReportExportFormat;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportNotReadyException;
use App\Models\ActivityReport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityReportExportController extends Controller
{
    public function __invoke(
        ActivityReport $report,
        ActivityReportExportFormat $format,
    ): StreamedResponse {
        if ($report->status === ActivityReportStatus::Generating) {
            throw new ActivityReportNotReadyException;
        }

        if (! $report->fileExists($format)) {
            throw new ActivityReportNotReadyException;
        }

        return Storage::disk(config('activity.reports.disk'))->download($format->pathFor($report));
    }
}
