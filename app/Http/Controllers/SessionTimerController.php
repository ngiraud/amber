<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Session\ReconstructDailySessions;
use App\Actions\Session\ReconstructSessionsFromDate;
use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Http\Requests\Session\ReconstructDailySessionsRequest;
use App\Http\Requests\Session\ReconstructFromDateRequest;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SessionTimerController extends Controller
{
    public function start(StoreSessionRequest $request, StartSession $action): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $action->handle($project, $request->validated('notes'));

        return redirect()->back();
    }

    public function stop(Session $session, StopSession $action): RedirectResponse
    {
        $action->handle($session);

        return redirect()->back();
    }

    public function reconstruct(ReconstructDailySessionsRequest $request, ReconstructDailySessions $action): RedirectResponse
    {
        $generated = $action->handle(date: $request->getDate(), mode: $request->getMode());

        $count = $generated->count();
        $message = $count > 0
            ? "Reconstructed {$count} session".($count !== 1 ? 's' : '').'.'
            : 'No new sessions to reconstruct.';

        Inertia::flash('success', $message);

        return back();
    }

    public function reconstructFrom(ReconstructFromDateRequest $request, ReconstructSessionsFromDate $action): RedirectResponse
    {
        $from = $request->getFromDate();

        $generated = $action->handle($from, $request->getMode());

        $count = $generated->count();
        $message = $count > 0
            ? "Reconstructed {$count} session".($count !== 1 ? 's' : '')." since {$from->toFormattedDateString()}."
            : "No new sessions to reconstruct since {$from->toFormattedDateString()}.";

        Inertia::flash('success', $message);

        return back();
    }
}
