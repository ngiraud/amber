<?php

declare(strict_types=1);

namespace App\Data;

class ProjectRepositoryData
{
    public function __construct(
        public readonly string $name,
        public readonly string $localPath,
    ) {}

    /**
     * @param  array{name: string, local_path: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            localPath: $data['local_path'],
        );
    }

    /**
     * @return array{name: string, local_path: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'local_path' => $this->localPath,
        ];
    }
}
