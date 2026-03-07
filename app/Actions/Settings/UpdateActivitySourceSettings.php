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
    public function handle(array $data): void
    {
        // Check availability BEFORE touching $this->settings so a failure leaves no side-effects
        $this->assertSourcesAvailable($data);

        $previousFswatch = $this->settings->fswatch;

        foreach (ActivityEventSourceType::cases() as $type) {
            if (isset($data[$type->value])) {
                $this->settings->mergeConfig($type, $data[$type->value]);
            }
        }

        $this->settings->save();

        $this->handleFswatchLifecycle($previousFswatch);
    }

    /**
     * @return array<string, bool>
     */
    protected function captureEnabledStates(): array
    {
        return ActivityEventSourceType::collect()
            ->mapWithKeys(fn (ActivityEventSourceType $type) => [
                $type->value => $this->settings->configFor($type)->isEnabled(),
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
            $beingEnabled = ($data[$type->value]['enabled'] ?? null) === true;
            $wasEnabled = $previousEnabled[$type->value];

            if (! $beingEnabled) {
                continue;
            }

            if ($wasEnabled) {
                continue;
            }

            if (app(TestActivitySourceConnection::class)->handle($type)) {
                continue;
            }

            $errors["{$type->value}.enabled"] = $type->requirements();
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
