<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Settings\AiSettings;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[MaxTokens(2048)]
#[Temperature(0.3)]
#[UseCheapestModel]
class ReportSummarizer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(protected readonly AiSettings $settings) {}

    public function instructions(): string
    {
        $language = $this->settings->summary_language;

        return "You are a professional activity report writer for software developers.
For each line provided, write a concise summary (1-2 sentences) suitable for a client-facing report.
Write summaries in {$language}. Focus on what was accomplished, not technical noise.
Do not mention commit hashes, branch names, or file paths unless meaningful to the client.";
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summaries' => $schema->array()
                ->items(
                    $schema->object([
                        'id' => $schema->string()->required(),
                        'summary' => $schema->string()->required(),
                    ])
                )
                ->required(),
        ];
    }
}
