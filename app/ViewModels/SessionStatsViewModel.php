<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Session;
use Illuminate\Support\Collection;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;

class SessionStatsViewModel implements ProvidesInertiaProperty
{
    /**
     * @param  Collection<int, Session>  $sessions  Finished sessions only (ended_at not null)
     */
    public function __construct(private readonly Collection $sessions) {}

    /**
     * @return array{total_minutes: int, session_count: int, avg_session_minutes: int, first_started_at: string|null, last_ended_at: string|null}
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        $count = $this->sessions->count();
        $total = (int) $this->sessions->sum('rounded_minutes');

        return [
            'total_minutes' => $total,
            'session_count' => $count,
            'avg_session_minutes' => $count > 0 ? (int) round($total / $count) : 0,
            'first_started_at' => $this->sessions->min('started_at'),
            'last_ended_at' => $this->sessions->max('ended_at'),
        ];
    }
}
