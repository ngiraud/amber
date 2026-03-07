<?php

declare(strict_types=1);

use App\Actions\ActivityReport\CollectDayContext;
use App\Data\DayContext;
use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Models\Session;
use Carbon\CarbonImmutable;

pest()->group('activity-report', 'actions');

describe('CollectDayContext action', function () {
    it('returns empty context when no events exist', function () {
        $project = Project::factory()->create();
        $date = CarbonImmutable::parse('2026-03-01');

        $context = CollectDayContext::make()->handle($project, $date);

        expect($context)->toBeInstanceOf(DayContext::class)
            ->and($context->labels)->toBe([])
            ->and($context->details)->toBe([])
            ->and($context->filesChanged)->toBe(0);
    });

    it('collects labels from git commits linked to a session', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->completed()->create(['project_id' => $project->id]);
        $date = CarbonImmutable::parse('2026-03-01');

        ActivityEvent::factory()->create([
            'project_id' => $project->id,
            'session_id' => $session->id,
            'type' => ActivityEventType::GitCommit,
            'source_type' => ActivityEventSourceType::Git,
            'occurred_at' => $date->setTime(10, 0),
            'metadata' => ['message' => 'feat: add login', 'hash' => 'abc1234', 'branch' => 'main'],
        ]);

        $context = CollectDayContext::make()->handle($project, $date);

        expect($context->labels)->toContain('feat: add login')
            ->and($context->filesChanged)->toBe(0);
    });

    it('ignores events not linked to a session', function () {
        $project = Project::factory()->create();
        $date = CarbonImmutable::parse('2026-03-01');

        ActivityEvent::factory()->create([
            'project_id' => $project->id,
            'session_id' => null,
            'type' => ActivityEventType::GitCommit,
            'source_type' => ActivityEventSourceType::Git,
            'occurred_at' => $date->setTime(10, 0),
            'metadata' => ['message' => 'feat: should be ignored'],
        ]);

        $context = CollectDayContext::make()->handle($project, $date);

        expect($context->labels)->toBe([])
            ->and($context->details)->toBe([]);
    });

    it('counts file changes', function () {
        $project = Project::factory()->create();
        $session = Session::factory()->completed()->create(['project_id' => $project->id]);
        $date = CarbonImmutable::parse('2026-03-01');

        ActivityEvent::factory()->count(3)->create([
            'project_id' => $project->id,
            'session_id' => $session->id,
            'type' => ActivityEventType::FileChange,
            'source_type' => ActivityEventSourceType::Fswatch,
            'occurred_at' => $date->setTime(10, 0),
            'metadata' => ['file_path' => 'src/file.ts'],
        ]);

        $context = CollectDayContext::make()->handle($project, $date);

        expect($context->filesChanged)->toBe(3);
    });
});
