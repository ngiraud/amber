<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Settings\UpdateSettings;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Edit', [
            'settings' => AppSetting::pluck('value', 'key')->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'Settings saved.');

        return redirect()->route('settings.edit');
    }
}
