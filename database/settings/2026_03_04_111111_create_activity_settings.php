<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('activity.idle_timeout_minutes', 30);
        $this->migrator->add('activity.untracked_threshold_minutes', 15);
        $this->migrator->add('activity.scan_interval_minutes', 2);
        $this->migrator->add('activity.block_end_padding_minutes', 15);
    }
};
