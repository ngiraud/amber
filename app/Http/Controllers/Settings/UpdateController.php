<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Native\Desktop\Facades\AutoUpdater;

class UpdateController extends Controller
{
    public function check(): RedirectResponse
    {
        AutoUpdater::checkForUpdates();

        return back();
    }

    public function install(): RedirectResponse
    {
        AutoUpdater::quitAndInstall();

        return back();
    }
}
