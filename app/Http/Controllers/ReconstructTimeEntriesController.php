<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TimeEntry\ReconstructDayEntries;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReconstructTimeEntriesController extends Controller
{
    public function __invoke(Request $request, ReconstructDayEntries $action): RedirectResponse
    {
        $date = $request->filled('date')
            ? CarbonImmutable::parse($request->string('date')->toString())
            : CarbonImmutable::today();

        $action->handle($date);

        return redirect()->back();
    }
}
