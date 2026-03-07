<?php

declare(strict_types=1);

namespace App\Actions\ActivityReport;

use App\Actions\Action;
use App\Data\DayContext;

class BuildLineDescription extends Action
{
    public function handle(DayContext $context): string
    {
        return collect([])
            ->when(! empty($context->labels), fn ($c) => $c->push(implode(', ', array_unique($context->labels))))
            ->when(! empty($context->details), fn ($c) => $c->push(implode(' | ', $context->details)))
            ->implode(' | ');
    }
}
