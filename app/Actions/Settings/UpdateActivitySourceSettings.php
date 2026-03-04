<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Data\ActivitySourceConfigs\ClaudeCodeSourceConfig;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Data\ActivitySourceConfigs\GitHubSourceConfig;
use App\Data\ActivitySourceConfigs\GitSourceConfig;
use App\Services\FileWatcherService;
use App\Settings\ActivitySourceSettings;

class UpdateActivitySourceSettings extends Action
{
    public function __construct(protected readonly ActivitySourceSettings $settings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        if (isset($data['git'])) {
            $this->settings->git = GitSourceConfig::fromArray(array_merge(
                $this->settings->git->toArray(),
                $data['git'],
            ));
        }

        if (isset($data['github'])) {
            $this->settings->github = GitHubSourceConfig::fromArray(array_merge(
                $this->settings->github->toArray(),
                $data['github'],
            ));
        }

        if (isset($data['claude_code'])) {
            $this->settings->claude_code = ClaudeCodeSourceConfig::fromArray(array_merge(
                $this->settings->claude_code->toArray(),
                $data['claude_code'],
            ));
        }

        $previousFswatch = $this->settings->fswatch;

        if (isset($data['fswatch'])) {
            $this->settings->fswatch = FswatchSourceConfig::fromArray(array_merge(
                $this->settings->fswatch->toArray(),
                $data['fswatch'],
            ));
        }

        $this->settings->save();

        $this->handleFswatchLifecycle($previousFswatch);
    }

    protected function handleFswatchLifecycle(FswatchSourceConfig $previous): void
    {
        $current = $this->settings->fswatch;
        $watcher = FileWatcherService::make();

        if ($previous->enabled && ! $current->enabled) {
            $watcher->stop();

            return;
        }

        if (! $previous->enabled && $current->enabled) {
            $watcher->start();

            return;
        }

        if ($current->enabled) {
            $debounceChanged = $current->debounce_seconds !== $previous->debounce_seconds;
            $patternsChanged = $current->excluded_patterns !== $previous->excluded_patterns;

            if ($debounceChanged || $patternsChanged) {
                $watcher->restart();
            }
        }
    }
}
