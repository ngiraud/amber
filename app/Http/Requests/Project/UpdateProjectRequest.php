<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function rules(): array
    {
        return collect(parent::rules())->except('client_id')->all();
    }
}
