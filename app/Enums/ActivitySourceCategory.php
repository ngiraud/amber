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
            self::AiClients => 'AI Clients',
            self::DevTools => 'Developer Tools',
            self::FileWatcher => 'File Watcher',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AiClients => 'AI-powered coding assistants and tools',
            self::DevTools => 'Version control and development tools',
            self::FileWatcher => 'Real-time file system monitoring',
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
