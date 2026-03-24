<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateGeneralSettings;
use App\Enums\AvailableLocale;
use App\Enums\DateFormat;
use App\Enums\RoundingStrategy;
use App\Enums\TimeFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/General', [
            'timezones' => timezone_identifiers_list(),
            'dateFormats' => DateFormat::options(),
            'timeFormats' => TimeFormat::options(),
            'locales' => AvailableLocale::options(),
            'roundingStrategies' => RoundingStrategy::options(),
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request, UpdateGeneralSettings $action): RedirectResponse
    {
        $action->handle($request->validated());

        Inertia::flash('success', 'General settings saved.');

        return back();
    }
}
