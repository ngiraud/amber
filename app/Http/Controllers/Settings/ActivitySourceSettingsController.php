<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateActivitySourceSettings;
use App\Enums\ActivityEventSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateActivitySourceSettingsRequest;
use App\Settings\ActivitySourceSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ActivitySourceSettingsController extends Controller
{
    public function edit(ActivitySourceSettings $settings): Response
    {
        return Inertia::render('settings/Edit', [
            'tab' => 'sources',
            'activitySourceSettings' => [
                'git' => $settings->git->toArray(),
                'github' => $settings->github->toArray(),
                'claude_code' => $settings->claude_code->toArray(),
                'fswatch' => $settings->fswatch->toArray(),
            ],
        ]);
    }

    public function update(UpdateActivitySourceSettingsRequest $request, UpdateActivitySourceSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'Source settings saved.');

        return redirect()->route('settings.sources');
    }

    public function test(ActivityEventSourceType $source): JsonResponse
    {
        if (! $source->isEnabled()) {
            return response()->json(['available' => false, 'reason' => 'Source is disabled.']);
        }

        $sourceClass = $source->sourceClass();

        if ($sourceClass === null) {
            return response()->json(['available' => false, 'reason' => 'No source class found.']);
        }

        $available = app($sourceClass)->isAvailable();

        return response()->json(['available' => $available]);
    }
}
