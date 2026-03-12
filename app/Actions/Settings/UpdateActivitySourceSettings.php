<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
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
    public function handle(ActivityEventSourceType $source, array $data): void
    {
        // Check availability BEFORE touching $this->settings so a failure leaves no side-effects
        $this->assertSourceAvailable($source, $data);

        $previousFswatch = $this->settings->fswatch;

        $this->settings->mergeConfig($source, $data);

        $this->settings->save();

        if ($source === ActivityEventSourceType::Fswatch) {
            $this->handleFswatchLifecycle($previousFswatch);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertSourceAvailable(ActivityEventSourceType $source, array $data): void
    {
        $beingEnabled = ($data['enabled'] ?? null) === true;
        $wasEnabled = $this->settings->configFor($source)->isEnabled();

        if (! $beingEnabled) {
            return;
        }

        if ($wasEnabled) {
            return;
        }

        if (app(TestActivitySourceConnection::class)->handle($source)) {
            return;
        }

        throw ValidationException::withMessages([
            "{$source->value}.enabled" => $source->requirements(),
        ]);
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
