<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivitySourceCategory: string
{
    case AiClients = 'ai_clients';
    case DevTools = 'dev_tools';
    case FileWatcher = 'file_watcher';

    public function label(): string
    {
        return match ($this) {
            self::AiClients => __('app.sources.categories.ai_clients.label'),
            self::DevTools => __('app.sources.categories.dev_tools.label'),
            self::FileWatcher => __('app.sources.categories.file_watcher.label'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AiClients => __('app.sources.categories.ai_clients.description'),
            self::DevTools => __('app.sources.categories.dev_tools.description'),
            self::FileWatcher => __('app.sources.categories.file_watcher.description'),
        };
    }

    public function displayLayout(): string
    {
        return match ($this) {
            self::AiClients, self::DevTools => 'grid-2',
            self::FileWatcher => 'full-width',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'description' => $this->description(),
            'display_layout' => $this->displayLayout(),
        ];
    }
}
