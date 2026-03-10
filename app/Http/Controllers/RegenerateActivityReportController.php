<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivityReport\RegenerateActivityReport;
use App\Http\Requests\ActivityReport\RegenerateActivityReportRequest;
use App\Models\ActivityReport;
use Illuminate\Http\RedirectResponse;

class RegenerateActivityReportController extends Controller
{
    public function __invoke(ActivityReport $report, RegenerateActivityReportRequest $request, RegenerateActivityReport $action): RedirectResponse
    {
        $action->handle(
            $report,
            notes: $request->input('notes'),
            useAiSummary: $request->boolean('use_ai_summary'),
        );

        return redirect()->route('reports.show', $report);
    }
}
