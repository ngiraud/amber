<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect(parent::rules())
            ->reject(fn (mixed $_, string $key) => str_starts_with($key, 'repositories'))
            ->all();
    }
}
