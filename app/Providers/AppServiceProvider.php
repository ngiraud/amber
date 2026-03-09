<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ApplicationMenuService;
use App\Services\FileWatcherService;
use App\Services\MenuBarService;
use App\Settings\AiSettings;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplicationMenuService::class);
        $this->app->singleton(MenuBarService::class);
        $this->app->singleton(FileWatcherService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAiProvider();
        $this->configureCommands();
        $this->configureDates();
        $this->configureEvents();
        $this->configureLocaleAndTimezone();
        $this->configureModels();
        $this->configurePasswords();
        $this->configureRelations();
        $this->configureResources();
        $this->configureVite();
    }

    protected function configureAiProvider(): void
    {
        try {
            $settings = app(AiSettings::class);

            if ($settings->api_key !== null) {
                config(["ai.providers.{$settings->provider->value}.key" => $settings->api_key]);
            }
        } catch (Throwable) {
            // Settings table may not exist yet (e.g., during migrations)
        }
    }

    protected function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            ! $this->app->environment(['local', 'testing', 'staging'])
        );
    }

    protected function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    protected function configureLocaleAndTimezone(): void
    {
        try {
            $settings = app(GeneralSettings::class);

            app()->setLocale($settings->locale->value);

            config(['app.display_timezone' => $settings->timezone]);
        } catch (Throwable) {
            // Settings table may not exist yet (e.g., during migrations)
        }
    }

    protected function configureModels(): void
    {
        Model::unguard();
        Model::shouldBeStrict();
    }

    protected function configurePasswords(): void
    {
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureResources(): void
    {
        JsonResource::withoutWrapping();
    }

    protected function configureVite(): void
    {
        Vite::prefetch(concurrency: 3);
    }

    protected function configureRelations(): void
    {
        //        Relation::enforceMorphMap([
        //            1 => Post::class,
        //            2 => Comment::class,
        //        ]);
    }

    protected function configureEvents(): void
    {
        // Events should be auto-discovered
    }
}
