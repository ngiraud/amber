<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Settings\ActivitySettings;

class UpdateActivitySettings extends Action
{
    public function __construct(protected readonly ActivitySettings $settings) {}

    /**
     * @param  array<string, int>  $data
     */
    public function handle(array $data): void
    {
        if (isset($data['idle_timeout_minutes'])) {
            $this->settings->idle_timeout_minutes = $data['idle_timeout_minutes'];
        }

        if (isset($data['scan_interval_minutes'])) {
            $this->settings->scan_interval_minutes = $data['scan_interval_minutes'];
        }

        if (isset($data['block_end_padding_minutes'])) {
            $this->settings->block_end_padding_minutes = $data['block_end_padding_minutes'];
        }

        if (isset($data['manual_session_reminder_minutes'])) {
            $this->settings->manual_session_reminder_minutes = $data['manual_session_reminder_minutes'];
        }

        $this->settings->save();
    }
}
