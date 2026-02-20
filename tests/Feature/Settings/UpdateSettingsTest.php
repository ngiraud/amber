<?php

declare(strict_types=1);

use App\Actions\Settings\UpdateSettings;

pest()->group('settings');

describe('update settings', function () {
    it('delegates to UpdateSettings action and redirects to settings edit', function () {
        UpdateSettings::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['company_name'] === 'My Company'));

        $this->put(route('settings.update'), [
            'company_name' => 'My Company',
        ])->assertRedirectToRoute('settings.edit');
    });

    it('shows the settings form', function () {
        $this->get(route('settings.edit'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('settings/Edit'));
    });

    it('validates git author emails are valid email addresses', function () {
        $this->put(route('settings.update'), [
            'git_author_emails' => ['not-an-email'],
        ])->assertInvalid(['git_author_emails.0']);
    });

    it('validates default_rounding_strategy is a valid enum value', function () {
        $this->put(route('settings.update'), [
            'default_rounding_strategy' => 999,
        ])->assertInvalid(['default_rounding_strategy']);
    });
})->group('controllers');

describe('UpdateSettings action', function () {
    it('upserts setting values in the database', function () {
        UpdateSettings::make()->handle([
            'company_name' => 'Acme Corp',
            'default_daily_reference_hours' => 7,
        ]);

        $this->assertDatabaseHas('app_settings', ['key' => 'company_name', 'value' => '"Acme Corp"']);
        $this->assertDatabaseHas('app_settings', ['key' => 'default_daily_reference_hours', 'value' => '7']);
    });

    it('upserts without creating duplicates', function () {
        UpdateSettings::make()->handle(['company_name' => 'Old Name']);
        UpdateSettings::make()->handle(['company_name' => 'New Name']);

        $this->assertDatabaseHas('app_settings', ['key' => 'company_name', 'value' => '"New Name"']);
    });
})->group('actions');
