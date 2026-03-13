<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\TestActivitySourceConnection;
use App\Actions\Settings\UpdateActivitySourceSettings;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivitySourceCategory;
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
            'hasEnabledSources' => ActivityEventSourceType::collect()->some->isEnabled(),
            'enabledSourceTypes' => ActivityEventSourceType::collect()
                ->filter->isEnabled()
                ->map->toArray()
                ->values(),
            'categories' => ActivityEventSourceType::collect()
                ->map(fn (ActivityEventSourceType $type) => array_merge($type->toArray(), [
                    'config' => $settings->configFor($type)->toArray(),
                ]))
                ->groupBy(fn ($source) => $source['category']['value'])
                ->map(fn ($sources, $categoryValue) => [
                    'category' => ActivitySourceCategory::from($categoryValue)->toArray(),
                    'sources' => $sources->values()->all(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(ActivityEventSourceType $source, UpdateActivitySourceSettingsRequest $request, UpdateActivitySourceSettings $action, ActivitySourceSettings $settings): RedirectResponse
    {
        $wasEnabled = $settings->configFor($source)->isEnabled();

        $action->handle($source, $request->validated()[$source->value]);

        $isNowEnabled = $settings->configFor($source)->isEnabled();

        $message = 'Source settings saved.';

        if (! $wasEnabled && $isNowEnabled) {
            $message = "Source {$source->label()} enabled.";
        }

        Inertia::flash('success', $message);

        return back();
    }

    public function test(ActivityEventSourceType $source, TestActivitySourceConnection $action): JsonResponse
    {
        return response()->json(['available' => $action->handle($source)]);
    }
}
