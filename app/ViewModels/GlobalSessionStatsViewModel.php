<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Models\Session;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;

class GlobalSessionStatsViewModel implements ProvidesInertiaProperty
{
    /**
     * @return array{total_minutes: int, session_count: int, avg_session_minutes: int, first_started_at: string|null, last_ended_at: string|null}
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        $stats = Session::query()
            ->whereNotNull('ended_at')
            ->selectRaw('COUNT(*) as session_count, SUM(rounded_minutes) as total_minutes, MIN(started_at) as first_started_at, MAX(ended_at) as last_ended_at')
            ->first();

        $count = (int) ($stats?->session_count ?? 0);
        $total = (int) ($stats?->total_minutes ?? 0);

        return [
            'total_minutes' => $total,
            'session_count' => $count,
            'avg_session_minutes' => $count > 0 ? (int) round($total / $count) : 0,
            'first_started_at' => $stats?->first_started_at,
            'last_ended_at' => $stats?->last_ended_at,
        ];
    }
}
