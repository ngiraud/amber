<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AvailableLocale;
use Illuminate\Support\Arr;

class FrontendTranslations
{
    /**
     * @return array<string, array<string, string>>
     */
    public function all(): array
    {
        return AvailableLocale::collect()
            ->mapWithKeys(fn (AvailableLocale $locale) => [
                $locale->value => $this->loadLocale($locale->value),
            ])
            ->filter()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function loadLocale(string $locale): array
    {
        $path = lang_path("{$locale}/app.php");

        if (! file_exists($path)) {
            return [];
        }

        return collect(Arr::dot(require $path))
            ->mapWithKeys(fn ($value, $key) => ["app.{$key}" => $value])
            ->all();
    }
}
