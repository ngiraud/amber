<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport\Exports\Contracts;

use App\Models\ActivityReport;

interface ExportActivityReport
{
    public static function make(): static;

    /**
     * @return string The path to the exported file
     */
    public function handle(ActivityReport $report): string;
}
