<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Session\ReconstructDailySessions;
use App\Actions\Session\StartSession;
use App\Actions\Session\StopSession;
use App\Http\Requests\Session\ReconstructDailySessionsRequest;
use App\Http\Requests\Session\StoreSessionRequest;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;

class SessionTimerController extends Controller
{
    public function start(StoreSessionRequest $request, StartSession $action): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('project_id'));

        $action->handle($project, $request->validated('notes'));

        return redirect()->route('sessions.index');
    }

    public function stop(Session $session, StopSession $action): RedirectResponse
    {
        $action->handle($session);

        return redirect()->route('sessions.index');
    }

    public function reconstruct(ReconstructDailySessionsRequest $request, ReconstructDailySessions $action): RedirectResponse
    {
        $action->handle($request->getDate());

        return back();
    }
}
