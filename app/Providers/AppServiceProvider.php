<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Native\StartSessionFromMenu;
use App\Events\Native\StopSessionFromMenu;
use App\Events\Native\SwitchProjectFromMenu;
use App\Events\Native\ToggleSessionShortcut;
use App\Events\SessionStarted;
use App\Events\SessionStopped;
use App\Listeners\HandleStartSessionFromMenu;
use App\Listeners\HandleStopSessionFromMenu;
use App\Listeners\HandleSwitchProjectFromMenu;
use App\Listeners\HandleToggleSessionShortcut;
use App\Listeners\RefreshMenuBarOnSessionChange;
use App\Listeners\SendSessionNotification;
use App\Services\MenuBarService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MenuBarService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureDates();
        $this->configureModels();
        $this->configurePasswords();
        $this->configureRelations();
        $this->configureResources();
        $this->configureUrl();
        $this->configureVite();
        $this->configureEvents();
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

    protected function configureUrl(): void
    {
        if (! $this->app->environment('local')) {
            URL::forceScheme('https');
        }
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
        Event::listen(StartSessionFromMenu::class, HandleStartSessionFromMenu::class);
        Event::listen(StopSessionFromMenu::class, HandleStopSessionFromMenu::class);
        Event::listen(SwitchProjectFromMenu::class, HandleSwitchProjectFromMenu::class);
        Event::listen(ToggleSessionShortcut::class, HandleToggleSessionShortcut::class);
        Event::listen([SessionStarted::class, SessionStopped::class], RefreshMenuBarOnSessionChange::class);
        Event::listen([SessionStarted::class, SessionStopped::class], SendSessionNotification::class);
    }
}
