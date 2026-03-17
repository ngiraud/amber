<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ApplicationHotkey;
use App\Http\Resources\SessionResource;
use App\Models\Session;
use App\Settings\GeneralSettings;
use App\ViewModels\ActiveProjectsViewModel;
use App\ViewModels\CurrentActivityViewModel;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'display_timezone' => config('app.display_timezone'),
            'display_locale' => config('app.locale'),
            'generalSettings' => fn () => app(GeneralSettings::class)->toArray(),
            'activeSession' => fn () => ($s = Session::findActive(['project.client']))
                ? SessionResource::make($s)
                : null,
            'hotkeys' => ApplicationHotkey::options(),
            new ActiveProjectsViewModel,
            new CurrentActivityViewModel,
        ];
    }
}
