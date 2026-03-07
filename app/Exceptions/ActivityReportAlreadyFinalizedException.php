<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ActivityReportAlreadyFinalizedException extends Exception
{
    public function __construct()
    {
        parent::__construct('This activity report is already finalized and cannot be regenerated.');
    }
}
