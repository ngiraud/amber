<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Models\Session;

class DeleteSession extends Action
{
    public function handle(Session $session): void
    {
        $session->delete();
    }
}
