<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Events\SessionAlreadyActiveAttempted;
use App\Models\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

class SessionAlreadyActiveException extends RuntimeException
{
    public function __construct(public readonly Session $session)
    {
        parent::__construct('A session is already active.');
    }

    public function render(Request $request): RedirectResponse
    {
        SessionAlreadyActiveAttempted::dispatch($this->session);

        Inertia::flash('error', $this->getMessage());

        return back();
    }
}
