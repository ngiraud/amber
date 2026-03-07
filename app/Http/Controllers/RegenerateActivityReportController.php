<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivityReport\RegenerateActivityReport;
use App\Models\ActivityReport;
use Illuminate\Http\RedirectResponse;

class RegenerateActivityReportController extends Controller
{
    public function __invoke(ActivityReport $report, RegenerateActivityReport $action): RedirectResponse
    {
        $action->handle($report);

        return redirect()->route('reports.show', $report);
    }
}
