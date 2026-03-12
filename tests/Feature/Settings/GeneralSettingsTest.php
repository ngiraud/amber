<?php

declare(strict_types=1);

use App\Actions\Settings\UpdateGeneralSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;

pest()->group('settings', 'general');

describe('general settings', function () {
    it('renders the general tab with required props', function () {
        $this->get(route('settings.general'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('settings/General')
                ->has('generalSettings')
                ->has('timezones')
                //                ->has('locales')
            );
    });

    it('delegates PUT to UpdateGeneralSettings and redirects', function () {
        UpdateGeneralSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['company_name'] === 'Acme Corp'));

        $this->put(route('settings.general.update'), [
            'company_name' => 'Acme Corp',
            'default_rounding_strategy' => 15,
            'timezone' => 'Europe/Paris',
            //            'locale' => 'fr',
            'theme' => 'system',
            'open_at_login' => false,
        ])->assertRedirectToRoute('settings.general');
    });

    it('validates default_rounding_strategy is a valid enum value', function () {
        $this->put(route('settings.general.update'), ['default_rounding_strategy' => 999])
            ->assertInvalid(['default_rounding_strategy']);
    });

    it('validates timezone is a valid timezone identifier', function () {
        $this->put(route('settings.general.update'), ['timezone' => 'Invalid/Zone'])
            ->assertInvalid(['timezone']);
    });

    it('accepts a valid timezone', function () {
        UpdateGeneralSettings::fake()->shouldReceive('handle')->once();

        $this->put(route('settings.general.update'), [
            'default_rounding_strategy' => 15,
            'timezone' => 'Europe/Paris',
            //            'locale' => 'fr',
            'theme' => 'system',
            'open_at_login' => false,
        ])->assertRedirectToRoute('settings.general');
    });

    //    it('validates locale must be in allowed list', function () {
    //        $this->put(route('settings.general.update'), ['locale' => 'de'])
    //            ->assertInvalid(['locale']);
    //    });
})->group('controllers');

describe('UpdateGeneralSettings action', function () {
    beforeEach(function () {
        Http::fake([
            '*/system/theme' => Http::response(['result' => 'system']),
            '*/app/open-at-login' => Http::response([]),
        ]);
    });

    it('persists general settings', function () {
        UpdateGeneralSettings::make()->handle([
            'company_name' => 'Acme Corp',
            'default_daily_reference_hours' => 7,
        ]);

        $settings = app(GeneralSettings::class);
        expect($settings->company_name)->toBe('Acme Corp')
            ->and($settings->default_daily_reference_hours)->toBe(7);
    });

    it('persists open_at_login and applies it', function () {
        UpdateGeneralSettings::make()->handle([
            'open_at_login' => true,
        ]);

        expect(app(GeneralSettings::class)->open_at_login)->toBeTrue();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'app/open-at-login')
            && $request->data()['open'] === true
        );
    });
})->group('actions');
