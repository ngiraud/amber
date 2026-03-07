<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivityReport\Exports\ExportActivityReportCsv;
use App\Actions\ActivityReport\Exports\ExportActivityReportPdf;
use App\Enums\ActivityReportExportFormat;
use App\Models\ActivityReport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityReportExportController extends Controller
{
    public function __invoke(
        ActivityReport $report,
        string $format,
        ExportActivityReportPdf $exportPdf,
        ExportActivityReportCsv $exportCsv,
    ): BinaryFileResponse {
        $exportFormat = ActivityReportExportFormat::from($format);

        $path = match ($exportFormat) {
            ActivityReportExportFormat::Pdf => $exportPdf->handle($report),
            ActivityReportExportFormat::Csv => $exportCsv->handle($report),
        };

        return response()->download(storage_path("app/private/{$path}"));
    }
}
