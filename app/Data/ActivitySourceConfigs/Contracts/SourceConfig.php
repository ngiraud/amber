<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs\Contracts;

use App\Data\ActivitySourceConfigs\FieldDefinition;
use Illuminate\Contracts\Validation\ValidationRule;

interface SourceConfig
{
    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function validationRules(): array;

    /**
     * @return array<string, mixed>
     */
    public static function defaultData(): array;

    /**
     * @return FieldDefinition[]
     */
    public static function fieldDefinitions(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;

    public function isEnabled(): bool;
}
