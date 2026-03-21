<?php

declare(strict_types=1);

namespace App\Enums;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;
use App\Data\ActivitySourceConfigs\FieldDefinition;
use App\Enums\Concerns\EnhanceEnum;
use App\Services\ActivitySources\Contracts\ActivitySource;
use App\Settings\ActivitySourceSettings;
use Illuminate\Support\Str;
use Native\Desktop\Support\Environment;

enum ActivityEventSourceType: string
{
    use EnhanceEnum;

    case Fswatch = 'fswatch';
    case Git = 'git';
    case GitHub = 'github';
    case ClaudeCode = 'claude_code';
    case Gemini = 'gemini';
    case MistralVibe = 'mistral_vibe';
    case Opencode = 'opencode';

    public function isEnabled(): bool
    {
        return app(ActivitySourceSettings::class)->configFor($this)->isEnabled();
    }

    /** @return class-string<ActivitySource>|null */
    public function sourceClass(): ?string
    {
        return $this->guessActivitySource();
    }

    public function label(): string
    {
        return Str::headline($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Git => 'text-[#05df72]',
            self::GitHub => 'text-[#0FBF3E]',
            self::ClaudeCode => 'text-[#DE7356]',
            self::Gemini => 'text-[#4796E3]',
            self::MistralVibe => 'text-[#FFAF00]',
            self::Opencode => 'text-[#007ACC]',
            self::Fswatch => 'text-[#ff637e]',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Git => __('app.sources.git.description'),
            self::GitHub => __('app.sources.github.description'),
            self::ClaudeCode => __('app.sources.claude_code.description'),
            self::Gemini => __('app.sources.gemini.description'),
            self::MistralVibe => __('app.sources.mistral_vibe.description'),
            self::Opencode => __('app.sources.opencode.description'),
            self::Fswatch => __('app.sources.fswatch.description'),
        };
    }

    public function requirements(): string
    {
        return match ($this) {
            self::Git => __('app.sources.git.requirements'),
            self::GitHub => __('app.sources.github.requirements'),
            self::ClaudeCode => __('app.sources.claude_code.requirements'),
            self::Gemini => __('app.sources.gemini.requirements'),
            self::MistralVibe => __('app.sources.mistral_vibe.requirements'),
            self::Opencode => __('app.sources.opencode.requirements'),
            self::Fswatch => __('app.sources.fswatch.requirements'),
        };
    }

    /**
     * @return array<array{label?: string, command: string}>
     */
    public function installationInstructions(): array
    {
        $npm = fn (string $package): array => [['command' => "npm install -g {$package}"]];
        $curl = fn (string $url): array => [['command' => "curl -LsSf {$url} | bash"]];

        if (Environment::isMac()) {
            return match ($this) {
                self::Git => [['command' => 'brew install git']],
                self::GitHub => [['command' => 'brew install gh && gh auth login']],
                self::Fswatch => [['command' => 'brew install fswatch']],
                self::ClaudeCode => $npm('@anthropic-ai/claude-code'),
                self::Gemini => $npm('@google/gemini-cli'),
                self::MistralVibe => $curl('https://mistral.ai/vibe/install.sh'),
                self::Opencode => $curl('https://opencode.ai/install'),
            };
        }

        return match ($this) {
            self::Git => [
                ['label' => 'Debian/Ubuntu', 'command' => 'sudo apt install git'],
                ['label' => 'Fedora', 'command' => 'sudo dnf install git'],
                ['label' => 'Arch', 'command' => 'sudo pacman -S git'],
            ],
            self::GitHub => [
                ['label' => 'Debian/Ubuntu', 'command' => 'sudo apt install gh && gh auth login'],
                ['label' => 'Fedora', 'command' => 'sudo dnf install gh && gh auth login'],
                ['label' => 'Arch', 'command' => 'sudo pacman -S github-cli && gh auth login'],
            ],
            self::Fswatch => [
                ['label' => 'Debian/Ubuntu', 'command' => 'sudo apt install fswatch'],
                ['label' => 'Fedora', 'command' => 'sudo dnf install fswatch'],
                ['label' => 'Arch', 'command' => 'sudo pacman -S fswatch'],
            ],
            self::ClaudeCode => $npm('@anthropic-ai/claude-code'),
            self::Gemini => $npm('@google/gemini-cli'),
            self::MistralVibe => $curl('https://mistral.ai/vibe/install.sh'),
            self::Opencode => $curl('https://opencode.ai/install'),
        };
    }

    /** @return class-string<SourceConfig> */
    public function configClass(): string
    {
        return $this->guessConfigClass();
    }

    public function category(): ActivitySourceCategory
    {
        return match ($this) {
            self::ClaudeCode, self::Gemini, self::MistralVibe, self::Opencode => ActivitySourceCategory::AiClients,
            self::Git, self::GitHub => ActivitySourceCategory::DevTools,
            self::Fswatch => ActivitySourceCategory::FileWatcher,
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => Str::ucfirst($this->label()),
            'color' => $this->color(),
            'description' => $this->description(),
            'requirements' => $this->requirements(),
            'installation_instructions' => $this->installationInstructions(),
            'category' => $this->category()->toArray(),
            'fields' => array_map(fn (FieldDefinition $f) => $f->toArray(), $this->configClass()::fieldDefinitions()),
        ];
    }

    /** @return class-string<ActivitySource>|null */
    private function guessActivitySource(): ?string
    {
        $class = sprintf('App\\Services\\ActivitySources\\%sActivitySource', $this->name);

        return class_exists($class) ? $class : null;
    }

    /** @return class-string<SourceConfig>|null */
    private function guessConfigClass(): ?string
    {
        $class = sprintf('App\\Data\\ActivitySourceConfigs\\%sSourceConfig', $this->name);

        return class_exists($class) ? $class : null;
    }
}
