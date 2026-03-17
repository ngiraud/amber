<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\TestAiConnection;
use App\Actions\Settings\UpdateAiSettings;
use App\Enums\AiProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAiSettingsRequest;
use App\Settings\AiSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function edit(AiSettings $settings): Response
    {
        return Inertia::render('settings/Ai', [
            'aiSettings' => $settings->toArray(),
            'providers' => AiProvider::options(),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request, UpdateAiSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'AI settings saved.');

        return back();
    }

    public function test(TestAiConnection $action): JsonResponse
    {
        $action->handle();

        return response()->json(['success' => true]);
    }
}
