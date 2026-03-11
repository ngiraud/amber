<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Onboarding\DismissOnboarding;
use Illuminate\Http\RedirectResponse;

class DismissOnboardingController extends Controller
{
    public function __invoke(DismissOnboarding $action): RedirectResponse
    {
        $action->handle();

        return back();
    }
}
