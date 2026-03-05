<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\TestActivitySourceConnection;
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
        return Inertia::render('settings/Sources', [
            'activitySourceSettings' => [
                'git' => $settings->git->toArray(),
                'github' => $settings->github->toArray(),
                'claude_code' => $settings->claude_code->toArray(),
                'fswatch' => $settings->fswatch->toArray(),
            ],
            'sourceInfo' => collect(ActivityEventSourceType::cases())
                ->mapWithKeys(fn (ActivityEventSourceType $t) => [
                    $t->value => ['requirements' => $t->requirements()],
                ])
                ->all(),
        ]);
    }

    public function update(UpdateActivitySourceSettingsRequest $request, UpdateActivitySourceSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'Source settings saved.');

        return redirect()->route('settings.sources');
    }

    public function test(ActivityEventSourceType $source, TestActivitySourceConnection $action): JsonResponse
    {
        return response()->json(['available' => $action->handle($source)]);
    }
}
