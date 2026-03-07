<?php

declare(strict_types=1);

use App\Actions\ActivityReport\GenerateActivityReport;
use App\Data\ActivityReportData;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportAlreadyFinalizedException;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;
use App\Models\Client;

pest()->group('activity-report');

describe('generate activity report', function () {
    it('delegates to GenerateActivityReport action and redirects to show', function () {
        $client = Client::factory()->create();
        $report = ActivityReport::factory()->make(['id' => 'fake-id', 'client_id' => $client->id]);

        GenerateActivityReport::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn($report);

        $this->post(route('reports.store'), [
            'client_id' => $client->id,
            'month' => 1,
            'year' => 2026,
        ])->assertRedirectToRoute('reports.show', $report);
    });

    it('validates required fields', function () {
        $this->post(route('reports.store'), [])
            ->assertInvalid(['client_id', 'month', 'year']);
    });

    it('validates month is between 1 and 12', function () {
        $client = Client::factory()->create();

        $this->post(route('reports.store'), [
            'client_id' => $client->id,
            'month' => 13,
            'year' => 2026,
        ])->assertInvalid(['month']);
    });

    it('validates year is at least 2020', function () {
        $client = Client::factory()->create();

        $this->post(route('reports.store'), [
            'client_id' => $client->id,
            'month' => 1,
            'year' => 2019,
        ])->assertInvalid(['year']);
    });
})->group('controllers');

describe('GenerateActivityReport action', function () {
    it('creates an activity report and dispatches job', function () {
        Queue::fake();

        $client = Client::factory()->create();
        $data = new ActivityReportData(
            clientId: $client->id,
            month: 3,
            year: 2026,
        );

        $report = GenerateActivityReport::make()->handle($data);

        expect($report)->toBeInstanceOf(ActivityReport::class)
            ->and($report->status)->toBe(ActivityReportStatus::Generating)
            ->and($report->client_id)->toBe($client->id)
            ->and($report->month)->toBe(3)
            ->and($report->year)->toBe(2026);

        Queue::assertPushed(GenerateActivityReportJob::class);
        $this->assertDatabaseHas('activity_reports', ['client_id' => $client->id, 'month' => 3, 'year' => 2026]);
    });

    it('deletes existing draft report before regenerating', function () {
        Queue::fake();

        $client = Client::factory()->create();
        $existing = ActivityReport::factory()->draft()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $data = new ActivityReportData(clientId: $client->id, month: 3, year: 2026);
        GenerateActivityReport::make()->handle($data);

        $this->assertDatabaseMissing('activity_reports', ['id' => $existing->id]);
        $this->assertDatabaseCount('activity_reports', 1);
    });

    it('throws exception for finalized reports', function () {
        $client = Client::factory()->create();
        ActivityReport::factory()->finalized()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $data = new ActivityReportData(clientId: $client->id, month: 3, year: 2026);

        expect(fn () => GenerateActivityReport::make()->handle($data))
            ->toThrow(ActivityReportAlreadyFinalizedException::class);
    });
})->group('actions');

describe('delete activity report', function () {
    it('deletes draft reports', function () {
        $report = ActivityReport::factory()->draft()->create();

        $this->delete(route('reports.destroy', $report))
            ->assertRedirectToRoute('reports.index');

        $this->assertDatabaseMissing('activity_reports', ['id' => $report->id]);
    });
})->group('controllers');
