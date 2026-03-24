<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRepositoryRequest extends FormRequest
{
    /**
     * @return array<string, array<string>>
     */
    public static function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'local_path' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules();
    }
}
