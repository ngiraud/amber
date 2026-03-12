<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\ResetDatabase;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ResetDatabaseController extends Controller
{
    public function __invoke(ResetDatabase $action): RedirectResponse
    {
        $action->handle();

        Inertia::flash('success', 'All data has been reset successfully.');

        return redirect()->route('home');
    }
}
