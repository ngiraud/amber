<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Data\ActivitySourceConfigs\ClaudeCodeSourceConfig;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Data\ActivitySourceConfigs\GitHubSourceConfig;
use App\Data\ActivitySourceConfigs\GitSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Services\FileWatcherService;
use App\Settings\ActivitySourceSettings;
use Illuminate\Validation\ValidationException;

class UpdateActivitySourceSettings extends Action
{
    public function __construct(protected readonly ActivitySourceSettings $settings) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        // Check availability BEFORE touching $this->settings so a failure leaves no side-effects
        $this->assertSourcesAvailable($data);

        $previousFswatch = $this->settings->fswatch;

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

        if (isset($data['fswatch'])) {
            $this->settings->fswatch = FswatchSourceConfig::fromArray(array_merge(
                $this->settings->fswatch->toArray(),
                $data['fswatch'],
            ));
        }

        $this->settings->save();

        $this->handleFswatchLifecycle($previousFswatch);
    }

    /**
     * @return array<string, bool>
     */
    protected function captureEnabledStates(): array
    {
        return collect(ActivityEventSourceType::cases())
            ->mapWithKeys(fn (ActivityEventSourceType $type) => [
                $type->settingsKey() => $this->settings->configFor($type)->isEnabled(),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertSourcesAvailable(array $data): void
    {
        $previousEnabled = $this->captureEnabledStates();

        $errors = [];

        foreach (ActivityEventSourceType::cases() as $type) {
            $key = $type->settingsKey();
            $beingEnabled = ($data[$key]['enabled'] ?? null) === true;
            $wasEnabled = $previousEnabled[$key];

            if (! $beingEnabled) {
                continue;
            }

            if ($wasEnabled) {
                continue;
            }

            if (app(TestActivitySourceConnection::class)->handle($type)) {
                continue;
            }

            $errors["{$key}.enabled"] = $type->requirements();
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
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
