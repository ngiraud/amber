<?php

declare(strict_types=1);

use App\Actions\ActivityReport\BuildLineDescription;
use App\Actions\ActivityReport\CollectDayContext;
use App\Actions\ActivityReport\DeleteActivityReport;
use App\Actions\ActivityReport\Exports\ExportActivityReportCsv;
use App\Actions\ActivityReport\Exports\ExportActivityReportPdf;
use App\Actions\ActivityReport\GenerateActivityReport;
use App\Actions\ActivityReport\RegenerateActivityReport;
use App\Data\ActivityReportData;
use App\Data\DayContext;
use App\Enums\ActivityReportExportFormat;
use App\Enums\ActivityReportStatus;
use App\Exceptions\ActivityReportCannotBeModifiedException;
use App\Jobs\GenerateActivityReportJob;
use App\Models\ActivityReport;
use App\Models\Client;
use App\Models\Project;
use App\Models\Session;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

pest()->group('activity-report');

beforeEach(function () {
    $this->disk = config('activity.reports.disk');
});

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
    beforeEach(fn () => Queue::fake());

    it('creates an activity report and dispatches job', function () {
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

    it('replaces an existing finalized report', function () {
        $client = Client::factory()->create();
        $existing = ActivityReport::factory()->finalized()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $data = new ActivityReportData(clientId: $client->id, month: 3, year: 2026);
        GenerateActivityReport::make()->handle($data);

        $this->assertDatabaseMissing('activity_reports', ['id' => $existing->id]);
        $this->assertDatabaseCount('activity_reports', 1);
    });

    it('throws exception when a generation is already in progress', function () {
        $client = Client::factory()->create();
        ActivityReport::factory()->generating()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $data = new ActivityReportData(clientId: $client->id, month: 3, year: 2026);

        expect(fn () => GenerateActivityReport::make()->handle($data))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });

    it('throws exception when report has been sent', function () {
        $client = Client::factory()->create();
        ActivityReport::factory()->sent()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $data = new ActivityReportData(clientId: $client->id, month: 3, year: 2026);

        expect(fn () => GenerateActivityReport::make()->handle($data))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });
})->group('actions');

describe('GenerateActivityReportJob', function () {
    it('creates lines from sessions and finalizes the report', function () {
        Event::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $report = ActivityReport::factory()->generating()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => '2026-03-15',
            'started_at' => '2026-03-15 09:00:00',
            'ended_at' => '2026-03-15 10:00:00',
            'duration_minutes' => 60,
        ]);

        CollectDayContext::fake()->shouldReceive('handle')->andReturn(new DayContext([], [], 0));
        BuildLineDescription::fake()->shouldReceive('handle')->andReturn('');
        ExportActivityReportPdf::fake()->shouldReceive('handle')->andReturn('path/to/report.pdf');
        ExportActivityReportCsv::fake()->shouldReceive('handle')->andReturn('path/to/report.csv');

        dispatch_sync(new GenerateActivityReportJob($report));

        expect($report->fresh())
            ->status->toBe(ActivityReportStatus::Finalized)
            ->total_minutes->toBe(60);

        expect($report->lines()->count())->toBe(1);
    });

    it('ignores sessions outside the report period', function () {
        Event::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $report = ActivityReport::factory()->generating()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        Session::factory()->create([
            'project_id' => $project->id,
            'started_at' => '2026-04-01 09:00:00',
            'ended_at' => '2026-04-01 10:00:00',
            'duration_minutes' => 60,
        ]);

        ExportActivityReportPdf::fake()->shouldReceive('handle')->andReturn('path/to/report.pdf');
        ExportActivityReportCsv::fake()->shouldReceive('handle')->andReturn('path/to/report.csv');

        dispatch_sync(new GenerateActivityReportJob($report));

        expect($report->lines()->count())->toBe(0);
    });

    it('groups sessions from the same project and day into a single line', function () {
        Event::fake();

        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $report = ActivityReport::factory()->generating()->create([
            'client_id' => $client->id,
            'month' => 3,
            'year' => 2026,
        ]);

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => '2026-03-15',
            'started_at' => '2026-03-15 09:00:00',
            'ended_at' => '2026-03-15 10:00:00',
            'duration_minutes' => 60,
        ]);

        Session::factory()->create([
            'project_id' => $project->id,
            'date' => '2026-03-15',
            'started_at' => '2026-03-15 14:00:00',
            'ended_at' => '2026-03-15 15:30:00',
            'duration_minutes' => 90,
        ]);

        CollectDayContext::fake()->shouldReceive('handle')->andReturn(new DayContext([], [], 0));
        BuildLineDescription::fake()->shouldReceive('handle')->andReturn('');
        ExportActivityReportPdf::fake()->shouldReceive('handle')->andReturn('path/to/report.pdf');
        ExportActivityReportCsv::fake()->shouldReceive('handle')->andReturn('path/to/report.csv');

        dispatch_sync(new GenerateActivityReportJob($report));

        expect($report->lines()->count())->toBe(1)
            ->and($report->fresh()->total_minutes)->toBe(150);
    });

    it('marks the report as failed when an exception is thrown', function () {
        Event::fake();

        $report = ActivityReport::factory()->generating()->create();

        $job = new GenerateActivityReportJob($report);
        $job->failed(new RuntimeException('Something went wrong'));

        expect($report->fresh()->status)->toBe(ActivityReportStatus::Failed);
    });
})->group('jobs');

describe('DeleteActivityReport action', function () {
    it('deletes the report, its lines, and its files', function () {
        Storage::fake($this->disk);

        $report = ActivityReport::factory()->draft()->create();
        $pdfPath = ActivityReportExportFormat::Pdf->pathFor($report);
        $csvPath = ActivityReportExportFormat::Csv->pathFor($report);

        Storage::disk($this->disk)->put($pdfPath, 'pdf');
        Storage::disk($this->disk)->put($csvPath, 'csv');
        $report->update(['pdf_path' => $pdfPath, 'csv_path' => $csvPath]);
        $report->lines()->create([
            'project_id' => Project::factory()->create()->id,
            'date' => '2026-03-01',
            'minutes' => 60,
            'days' => 0.13,
        ]);

        DeleteActivityReport::make()->handle($report);

        $this->assertDatabaseMissing('activity_reports', ['id' => $report->id]);
        $this->assertDatabaseCount('activity_report_lines', 0);
        Storage::disk($this->disk)->assertMissing($pdfPath);
        Storage::disk($this->disk)->assertMissing($csvPath);
    });

    it('allows deleting a finalized report', function () {
        Storage::fake($this->disk);
        $report = ActivityReport::factory()->finalized()->create();

        DeleteActivityReport::make()->handle($report);

        $this->assertDatabaseMissing('activity_reports', ['id' => $report->id]);
    });

    it('throws exception when report is generating', function () {
        $report = ActivityReport::factory()->generating()->create();

        expect(fn () => DeleteActivityReport::make()->handle($report))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });

    it('throws exception when report is sent', function () {
        $report = ActivityReport::factory()->sent()->create();

        expect(fn () => DeleteActivityReport::make()->handle($report))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });
})->group('actions');

describe('RegenerateActivityReport action', function () {
    it('resets and redispatches the job for a draft report', function () {
        Queue::fake();
        Storage::fake($this->disk);

        $report = ActivityReport::factory()->draft()->create();
        $pdfPath = ActivityReportExportFormat::Pdf->pathFor($report);
        Storage::disk($this->disk)->put($pdfPath, 'pdf');
        $report->update(['pdf_path' => $pdfPath]);
        $report->lines()->create([
            'project_id' => Project::factory()->create()->id,
            'date' => '2026-03-01',
            'minutes' => 60,
            'days' => 0.13,
        ]);

        RegenerateActivityReport::make()->handle($report);

        expect($report->fresh())
            ->status->toBe(ActivityReportStatus::Generating)
            ->total_minutes->toBe(0)
            ->generated_at->toBeNull();

        $this->assertDatabaseCount('activity_report_lines', 0);
        Storage::disk($this->disk)->assertMissing($pdfPath);
        Queue::assertPushed(GenerateActivityReportJob::class);
    });

    it('allows regenerating a finalized report', function () {
        Queue::fake();
        Storage::fake($this->disk);
        $report = ActivityReport::factory()->finalized()->create();

        RegenerateActivityReport::make()->handle($report);

        expect($report->fresh()->status)->toBe(ActivityReportStatus::Generating);
    });

    it('throws exception when report is generating', function () {
        $report = ActivityReport::factory()->generating()->create();

        expect(fn () => RegenerateActivityReport::make()->handle($report))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });

    it('throws exception when report is sent', function () {
        $report = ActivityReport::factory()->sent()->create();

        expect(fn () => RegenerateActivityReport::make()->handle($report))
            ->toThrow(ActivityReportCannotBeModifiedException::class);
    });
})->group('actions');

describe('regenerate activity report', function () {
    it('delegates to RegenerateActivityReport action and redirects to show', function () {
        $report = ActivityReport::factory()->draft()->create();

        RegenerateActivityReport::fake()
            ->shouldReceive('handle')
            ->once()
            ->andReturn($report);

        $this->post(route('reports.regenerate', $report))
            ->assertRedirectToRoute('reports.show', $report);
    });
})->group('controllers');

describe('delete activity report', function () {
    it('deletes draft reports', function () {
        $report = ActivityReport::factory()->draft()->create();

        $this->delete(route('reports.destroy', $report))
            ->assertRedirectToRoute('reports.index');

        $this->assertDatabaseMissing('activity_reports', ['id' => $report->id]);
    });
})->group('controllers');

describe('export activity report', function () {
    it('redirects with error when report is still generating', function () {
        $report = ActivityReport::factory()->generating()->create();

        $this->get(route('reports.export', [$report, 'pdf']))
            ->assertRedirect();
    });

    it('redirects with error when file does not exist', function () {
        Storage::fake($this->disk);
        $report = ActivityReport::factory()->finalized()->create();

        $this->get(route('reports.export', [$report, 'pdf']))
            ->assertRedirect();
    });

    it('downloads the file when report is finalized and file exists', function () {
        Storage::fake($this->disk);
        $report = ActivityReport::factory()->finalized()->create();

        $path = ActivityReportExportFormat::Pdf->pathFor($report);
        Storage::disk($this->disk)->put($path, 'pdf content');

        $this->get(route('reports.export', [$report, 'pdf']))
            ->assertOk()
            ->assertHeader('content-disposition');
    });
})->group('controllers');
