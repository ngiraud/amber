<?php

declare(strict_types=1);

namespace App\Data\ActivitySourceConfigs;

class FieldDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        public readonly string $hint,
        public readonly ?string $placeholder = null,
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?int $rows = null,
        public readonly ?string $separator = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'label' => $this->label,
            'hint' => $this->hint,
            'placeholder' => $this->placeholder,
            'min' => $this->min,
            'max' => $this->max,
            'rows' => $this->rows,
            'separator' => $this->separator,
        ];
    }
}
