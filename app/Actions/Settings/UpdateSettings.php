<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Actions\Action;
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;

class UpdateSettings extends Action
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): void
    {
        DB::transaction(function () use ($data) {
            $now = now();

            $rows = collect($data)
                ->map(fn ($value, $key) => [
                    'key' => $key,
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values()
                ->all();

            AppSetting::upsert($rows, ['key'], ['value', 'updated_at']);
        });
    }
}
