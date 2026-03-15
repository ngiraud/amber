<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\DayContext;

class BuildLineDescription extends Action
{
    public function handle(DayContext $context): string
    {
        $parts = [];

        if (! empty($context->labels)) {
            $parts[] = implode(', ', array_unique($context->labels));
        }

        if (! empty($context->details)) {
            $parts[] = implode(' | ', $context->details);
        }

        return implode(' | ', $parts);
    }
}
