<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ActivityReport\DeleteActivityReport;
use App\Actions\ActivityReport\GenerateActivityReport;
use App\Data\ActivityReportData;
use App\Http\Requests\ActivityReport\StoreActivityReportRequest;
use App\Http\Resources\ActivityReportResource;
use App\Http\Resources\ClientResource;
use App\Models\ActivityReport;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivityReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('report/Index', [
            'reports' => ActivityReportResource::collection(
                ActivityReport::query()
                    ->with('client')
                    ->withCount('lines')
                    ->latest()
                    ->paginate()
            ),
            'clients' => ClientResource::collection(Client::query()->orderBy('name')->get()),
        ]);
    }

    public function store(StoreActivityReportRequest $request, GenerateActivityReport $action): RedirectResponse
    {
        $report = $action->handle(ActivityReportData::fromArray($request->validated()));

        return redirect()->route('reports.show', $report);
    }

    public function show(ActivityReport $report): Response
    {
        return Inertia::render('report/Show', [
            'report' => fn () => ActivityReportResource::make(
                $report->load(['client', 'lines.project'])
            ),
        ]);
    }

    public function destroy(ActivityReport $report, DeleteActivityReport $action): RedirectResponse
    {
        $action->handle($report);

        return redirect()->route('reports.index');
    }
}
