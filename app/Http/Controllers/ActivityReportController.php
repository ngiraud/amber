<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivityReport\DeleteActivityReport;
use App\Actions\ActivityReport\GenerateActivityReport;
use App\Data\ActivityReportData;
use App\Enums\ActivityReportStatus;
use App\Enums\ActivityReportStep;
use App\Http\Requests\ActivityReport\StoreActivityReportRequest;
use App\Http\Resources\ActivityReportResource;
use App\Http\Resources\ClientResource;
use App\Models\ActivityReport;
use App\Models\Client;
use App\Settings\AiSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityReportController extends Controller
{
    public function index(AiSettings $aiSettings): Response
    {
        return Inertia::render('report/Index', [
            'reports' => ActivityReportResource::collection(
                ActivityReport::query()
                    ->select('activity_reports.*')
                    ->with('client')
                    ->withCount('lines')
                    ->join('clients', 'clients.id', '=', 'activity_reports.client_id')
                    ->orderBy('clients.name')
                    ->orderByDesc('activity_reports.year')
                    ->orderByDesc('activity_reports.month')
                    ->get()
            ),
            'clients' => ClientResource::collection(Client::query()->orderBy('name')->get()),
            'aiSettings' => $aiSettings->toArray(),
        ]);
    }

    public function store(StoreActivityReportRequest $request, GenerateActivityReport $action): RedirectResponse
    {
        $report = $action->handle(ActivityReportData::fromArray($request->validated()));

        return redirect()->route('reports.show', $report);
    }

    public function show(ActivityReport $report, AiSettings $aiSettings): Response
    {
        return Inertia::render('report/Show', [
            'report' => fn () => ActivityReportResource::make(
                $report->load(['client', 'lines.project'])
            ),
            'aiSettings' => $aiSettings->toArray(),
            'reportSteps' => fn () => ActivityReportStep::options()->where('shouldDisplayStep', true),
            'reportStatuses' => fn () => ActivityReportStatus::options(),
        ]);
    }

    public function destroy(ActivityReport $report, DeleteActivityReport $action): RedirectResponse
    {
        $action->handle($report);

        return redirect()->route('reports.index');
    }
}
