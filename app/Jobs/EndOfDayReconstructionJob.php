<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Session\ReconstructDailySessions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EndOfDayReconstructionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ?string $date = null) {}

    public function handle(ReconstructDailySessions $action): void
    {
        $day = $this->date !== null
            ? CarbonImmutable::parse($this->date)
            : CarbonImmutable::yesterday();

        $action->handle($day);
    }
}
