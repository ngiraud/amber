<?php

declare(strict_types=1);

use App\Enums\AvailableLocale;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ai.enabled', false);
        $this->migrator->add('ai.provider', null);
        $this->migrator->addEncrypted('ai.api_key', null);
        $this->migrator->add('ai.summary_language', AvailableLocale::French->value);
    }
};
