<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Session;
use RuntimeException;

class SessionAlreadyActiveException extends RuntimeException
{
    public function __construct(public readonly Session $session)
    {
        parent::__construct('A session is already active.');
    }
}
