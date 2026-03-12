<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateActivitySettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateActivitySettingsRequest;
use App\Settings\ActivitySettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivitySettingsController extends Controller
{
    public function edit(ActivitySettings $settings): Response
    {
        return Inertia::render('settings/Activity', [
            'activitySettings' => $settings->toArray(),
        ]);
    }

    public function update(UpdateActivitySettingsRequest $request, UpdateActivitySettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'Activity settings saved.');

        return back();
    }
}
