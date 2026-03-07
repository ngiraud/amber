<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityReportStep: string
{
    case CollectingContext = 'collecting_context';
    case BuildingLines = 'building_lines';
    case GeneratingFiles = 'generating_files';
    case Completed = 'completed';
    case Failed = 'failed';
}
