<?php

declare(strict_types=1);

use App\Actions\Session\ReconstructDailySessions;
use App\Enums\SessionReconstructMode;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

pest()->group('timeline', 'controllers');

describe('timeline index', function () {
    it('renders the timeline index page with month days', function () {
        $response = $this->get(route('timeline.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('timeline/Index')
                ->has('year')
                ->has('month')
                ->has('days')
            );
    });

    it('accepts year and month query params', function () {
        $response = $this->get(route('timeline.index', ['year' => 2026, 'month' => 1]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('year', 2026)
                ->where('month', 1)
                ->has('days', 31)
            );
    });

    it('includes session totals per day', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 90,
            'ended_at' => now(),
        ]);

        $this->get(route('timeline.index', [
            'year' => $today->year,
            'month' => $today->month,
        ]))->assertOk()->assertInertia(fn ($page) => $page->component('timeline/Index'));
    });
});

describe('timeline index stats', function () {
    it('includes stats and weeks in the response', function () {
        $response = $this->get(route('timeline.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('stats')
                ->has('stats.month_total_minutes')
                ->has('stats.month_worked_days')
                ->has('stats.month_avg_minutes_per_day')
                ->has('stats.month_project_breakdown')
                ->has('weeks')
            );
    });

    it('calculates correct month totals from sessions', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 120,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);

        $this->get(route('timeline.index', ['year' => $today->year, 'month' => $today->month]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.month_total_minutes', 180)
                ->where('stats.month_worked_days', 1)
                ->where('stats.month_avg_minutes_per_day', 180)
            );
    });

    it('calculates correct project breakdown percentages', function () {
        $projectA = Project::factory()->create(['color' => '#ff0000']);
        $projectB = Project::factory()->create(['color' => '#0000ff']);
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $projectA->id,
            'date' => $today,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $projectB->id,
            'date' => $today,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);

        $this->get(route('timeline.index', ['year' => $today->year, 'month' => $today->month]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.month_project_breakdown.0.percentage', 50)
                ->where('stats.month_project_breakdown.1.percentage', 50)
            );
    });

    it('returns zero stats for an empty month', function () {
        $this->get(route('timeline.index', ['year' => 2020, 'month' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.month_total_minutes', 0)
                ->where('stats.month_worked_days', 0)
                ->where('stats.month_avg_minutes_per_day', 0)
                ->where('stats.month_project_breakdown', [])
            );
    });

    it('includes current_week_total_minutes when viewing the current month', function () {
        $today = CarbonImmutable::today();

        $this->get(route('timeline.index', ['year' => $today->year, 'month' => $today->month]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->whereType('stats.current_week_total_minutes', 'integer')
            );
    });

    it('returns null current_week_total_minutes for a past month', function () {
        $this->get(route('timeline.index', ['year' => 2020, 'month' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.current_week_total_minutes', null)
            );
    });

    it('segments sessions into weeks', function () {
        $project = Project::factory()->create();
        $firstDayOfMonth = CarbonImmutable::create(2026, 1, 1);

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $firstDayOfMonth,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $firstDayOfMonth->addDays(7),
            'rounded_minutes' => 90,
            'ended_at' => now(),
        ]);

        $this->get(route('timeline.index', ['year' => 2026, 'month' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('weeks')
                ->where('weeks.0.total_minutes', 60)
            );
    });
});

describe('timeline day show', function () {
    it('renders the timeline show page for a date', function () {
        $date = CarbonImmutable::today()->toDateString();

        $response = $this->get(route('timeline.show', $date));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('timeline/Show')
                ->where('date', $date)
                ->has('sessions')
                ->has('session_stats')
                ->has('previous_date')
                ->has('next_date')
            );
    });

    it('returns sessions for the given date', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today->subDay(),
            'ended_at' => now()->subDay(),
        ]);

        $this->get(route('timeline.show', $today->toDateString()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sessions', 1)
            );
    });

    it('returns correct previous and next dates', function () {
        $date = CarbonImmutable::parse('2026-03-15');

        $this->get(route('timeline.show', $date->toDateString()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('previous_date', '2026-03-14')
                ->where('next_date', '2026-03-16')
            );
    });

    it('calculates session_stats from finished sessions only', function () {
        $project = Project::factory()->create();
        $today = CarbonImmutable::today();

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 60,
            'ended_at' => now(),
        ]);
        Session::factory()->create([
            'project_id' => $project->id,
            'date' => $today,
            'rounded_minutes' => 30,
            'ended_at' => null, // active — excluded
        ]);

        $this->get(route('timeline.show', $today->toDateString()))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('session_stats.total_minutes', 60)
                ->where('session_stats.session_count', 1)
            );
    });

    it('returns zero session_stats for a day with no finished sessions', function () {
        $this->get(route('timeline.show', '2020-01-01'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('session_stats.total_minutes', 0)
                ->where('session_stats.session_count', 0)
            );
    });
});

describe('reconstruct sessions controller', function () {
    beforeEach(fn () => Event::fake());

    it('delegates to ReconstructDailySessions and redirects back', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn(collect());

        $this->post(route('sessions.reconstruct'))
            ->assertRedirect();
    });

    it('uses the provided date', function () {
        ReconstructDailySessions::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn (CarbonImmutable $date) => $date->toDateString() === '2026-02-20'),
                null,
                SessionReconstructMode::Gaps,
            )
            ->andReturn(collect());

        $this->post(route('sessions.reconstruct'), ['date' => '2026-02-20'])
            ->assertRedirect();
    });
})->group('controllers');
