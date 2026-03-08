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

describe('timeline day show', function () {
    it('renders the timeline show page for a date', function () {
        $date = CarbonImmutable::today()->toDateString();

        $response = $this->get(route('timeline.show', $date));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('timeline/Show')
                ->where('date', $date)
                ->has('sessions')
                ->has('total_minutes')
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
