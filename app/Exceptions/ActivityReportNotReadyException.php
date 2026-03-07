<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class ActivityReportNotReadyException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This activity report is not ready for download.');
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::flash('error', $this->getMessage());

        return back();
    }
}
