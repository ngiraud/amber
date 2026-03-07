<?php

declare(strict_types=1);

namespace App\Actions\Session;

use App\Actions\Action;
use App\Enums\SessionReconstructMode;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReconstructSessionsFromDate extends Action
{
    public function __construct(
        private readonly ReconstructDailySessions $reconstructDailySessions,
    ) {}

    /**
     * @return Collection<int, Session>
     */
    public function handle(CarbonImmutable $from, SessionReconstructMode $mode = SessionReconstructMode::Gaps): Collection
    {
        $generated = collect();
        $date = $from;
        $today = CarbonImmutable::today();

        while ($date->lte($today)) {
            $sessions = $this->reconstructDailySessions->handle($date, null, $mode);
            $generated = $generated->merge($sessions);
            $date = $date->addDay();
        }

        return $generated;
    }
}
