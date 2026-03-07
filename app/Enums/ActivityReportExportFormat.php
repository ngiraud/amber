<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityReportExportFormat: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';
}
