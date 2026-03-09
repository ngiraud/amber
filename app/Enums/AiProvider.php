<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;
use Laravel\Ai\Ai;
use Laravel\Ai\Enums\Lab;
use Throwable;

enum AiProvider: string
{
    use EnhanceEnum;

    case Anthropic = Lab::Anthropic->value;
    case OpenAI = Lab::OpenAI->value;
    case Mistral = Lab::Mistral->value;
    case Ollama = Lab::Ollama->value;
    case Gemini = Lab::Gemini->value;

    public function label(): string
    {
        return match ($this) {
            self::Anthropic => 'Anthropic',
            self::OpenAI => 'OpenAI',
            self::Mistral => 'Mistral',
            self::Ollama => 'Ollama (local)',
            self::Gemini => 'Google Gemini',
        };
    }

    public function model(): string
    {
        try {
            return Ai::textProvider($this->value)->cheapestTextModel();
        } catch (Throwable) {
            return '';
        }
    }

    public function requiresApiKey(): bool
    {
        return $this !== self::Ollama;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => Str::ucfirst($this->label()),
            'model' => $this->model(),
            'requiresApiKey' => $this->requiresApiKey(),
        ];
    }
}
