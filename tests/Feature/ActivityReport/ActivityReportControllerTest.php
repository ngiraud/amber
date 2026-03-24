<?php

declare(strict_types=1);

use App\Models\ActivityReport;
use App\Models\ActivityReportLine;
use App\Models\Client;

pest()->group('activity-report', 'controllers');

describe('index', function () {
    it('renders the index page with reports and clients', function () {
        $clients = Client::factory()->count(2)->create();
        ActivityReport::factory()->create(['client_id' => $clients->first()->id, 'month' => 1, 'year' => 2026]);
        ActivityReport::factory()->create(['client_id' => $clients->first()->id, 'month' => 2, 'year' => 2026]);
        ActivityReport::factory()->create(['client_id' => $clients->first()->id, 'month' => 3, 'year' => 2026]);

        $this->get(route('reports.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('report/Index')
                ->has('reports', 3)
                ->has('clients', 2)
            );
    });

    it('includes lines_count on each report', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->count(2)->create(['activity_report_id' => $report->id]);

        $this->get(route('reports.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('reports.0.lines_count', 2)
            );
    });
});

describe('show', function () {
    it('renders the show page with report, client and lines', function () {
        $report = ActivityReport::factory()->finalized()->create();
        ActivityReportLine::factory()->count(2)->create(['activity_report_id' => $report->id]);

        $this->get(route('reports.show', $report))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('report/Show')
                ->has('report.lines', 2)
                ->has('report.client')
                ->where('report.id', $report->id)
            );
    });

    it('includes status as value/label pair', function () {
        $report = ActivityReport::factory()->finalized()->create();

        $this->get(route('reports.show', $report))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('report.status.value')
                ->has('report.status.label')
            );
    });
});

describe('exception rendering', function () {
    it('redirects back with error flash when trying to delete a sent report', function () {
        $report = ActivityReport::factory()->sent()->create();

        $this->from(route('reports.show', $report))
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.show', $report));
    });
});
