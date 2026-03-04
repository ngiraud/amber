<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\AvailableLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use App\Settings\GeneralSettings;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function edit(GeneralSettings $settings): Response
    {
        return Inertia::render('settings/Edit', [
            'tab' => 'general',
            'generalSettings' => $settings->toArray(),
            'timezones' => timezone_identifiers_list(),
            'locales' => collect(AvailableLocale::cases())
                ->map(fn (AvailableLocale $locale) => ['value' => $locale->value, 'label' => $locale->label()])
                ->all(),
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request, UpdateGeneralSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'General settings saved.');

        return redirect()->route('settings.general');
    }
}
