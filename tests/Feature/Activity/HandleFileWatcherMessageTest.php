<?php

declare(strict_types=1);

use App\Enums\ActivityEventSourceType;
use App\Enums\ActivityEventType;
use App\Listeners\HandleFileWatcherMessage;
use App\Models\ActivityEvent;
use App\Models\ProjectRepository;
use App\Services\FileWatcherService;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Events\ChildProcess\MessageReceived;

pest()->group('listeners', 'activity');

describe('HandleFileWatcherMessage', function () {
    beforeEach(fn () => Event::fake());

    it('ignores messages from other aliases', function () {
        $event = new MessageReceived('other-process', '/tmp/project/file.php');

        app(HandleFileWatcherMessage::class)->handle($event);

        $this->assertDatabaseEmpty('activity_events');
    });

    it('ignores empty message data', function () {
        $event = new MessageReceived(FileWatcherService::ALIAS, '   ');

        app(HandleFileWatcherMessage::class)->handle($event);

        $this->assertDatabaseEmpty('activity_events');
    });

    it('ignores malformed message data', function () {
        // Message data should be a timestamp followed by a file path

        $filePath = '/tmp/watched-project/src/app.php';

        $event = new MessageReceived(FileWatcherService::ALIAS, "azerty {$filePath}");

        app(HandleFileWatcherMessage::class)->handle($event);

        $this->assertDatabaseEmpty('activity_events');
    });

    it('records a FileChange event for a valid file path', function () {
        $repo = ProjectRepository::factory()->create(['local_path' => '/tmp/watched-project']);
        $timestamp = time();
        $filePath = '/tmp/watched-project/src/app.php';

        $event = new MessageReceived(FileWatcherService::ALIAS, "{$timestamp} {$filePath}");

        app(HandleFileWatcherMessage::class)->handle($event);

        $this->assertDatabaseHas('activity_events', [
            'source_type' => ActivityEventSourceType::Fswatch->value,
            'project_id' => $repo->project_id,
            'project_repository_id' => $repo->id,
        ]);

        $recorded = ActivityEvent::first();
        expect($recorded->type)->toBe(ActivityEventType::FileChange);
    });
});
