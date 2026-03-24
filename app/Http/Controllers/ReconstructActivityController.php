<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Session\ReconstructSessionsFromDate;
use App\Enums\SessionReconstructMode;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReconstructActivityController extends Controller
{
    public function __invoke(Request $request, ReconstructSessionsFromDate $reconstructSessionsFromDate): JsonResponse
    {
        $request->validate(['since' => ['required', 'date']]);

        $since = CarbonImmutable::parse($request->input('since'))->startOfDay();

        $sessions = $reconstructSessionsFromDate->handle($since, SessionReconstructMode::Replace);

        return response()->json(['sessions_count' => $sessions->count()]);
    }
}
