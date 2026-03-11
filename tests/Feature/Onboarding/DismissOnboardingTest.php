<?php

declare(strict_types=1);

use App\Actions\Onboarding\DismissOnboarding;
use App\Settings\GeneralSettings;

pest()->group('onboarding', 'controllers');

describe('dismiss onboarding', function () {
    it('delegates to DismissOnboarding action', function () {
        DismissOnboarding::fake()
            ->shouldReceive('handle')
            ->once();

        $this->post(route('onboarding.dismiss'))
            ->assertRedirect();
    });

    it('marks onboarding as dismissed in settings', function () {
        $this->post(route('onboarding.dismiss'));

        expect(app(GeneralSettings::class)->onboarding_dismissed)->toBeTrue();
    });
});
