<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('activity.idle_timeout_minutes', 30);
        $this->migrator->add('activity.block_end_padding_minutes', 5);
        $this->migrator->add('activity.manual_session_reminder_minutes', 60);
        $this->migrator->add('activity.last_scan_completed_at', null);
    }
};
