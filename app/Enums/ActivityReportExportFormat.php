<?php

declare(strict_types=1);

namespace App\Enums;

use App\Actions\ActivityReport\Exports\Contracts\ExportActivityReport;
use App\Actions\ActivityReport\Exports\ExportActivityReportCsv;
use App\Actions\ActivityReport\Exports\ExportActivityReportPdf;
use App\Models\ActivityReport;
use Illuminate\Support\Str;

enum ActivityReportExportFormat: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';

    public function pathFor(ActivityReport $report): string
    {
        $report->loadMissing('client');

        return sprintf('reports/report-%s-%d-%d.%s', Str::slug($report->client->name), $report->year, $report->month, $this->value);
    }

    public function exportAction(): ExportActivityReport
    {
        return match ($this) {
            self::Pdf => ExportActivityReportPdf::make(),
            self::Csv => ExportActivityReportCsv::make(),
        };
    }

    public function generateFor(ActivityReport $report): string
    {
        return $this->exportAction()->handle($report);
    }
}
