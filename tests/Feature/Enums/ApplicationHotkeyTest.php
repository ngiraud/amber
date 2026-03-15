<?php

declare(strict_types=1);

use App\Enums\ApplicationHotkey;

pest()->group('enums');

describe('ApplicationHotkey', function () {
    it('returns a non-empty label for every case', function () {
        foreach (ApplicationHotkey::cases() as $hotkey) {
            expect($hotkey->label())->toBeString()->not->toBeEmpty();
        }
    });

    it('navigation cases are correctly identified', function () {
        $navigation = array_filter(ApplicationHotkey::cases(), fn ($h) => $h->isNavigation());
        $nonNavigation = array_filter(ApplicationHotkey::cases(), fn ($h) => ! $h->isNavigation());

        expect($navigation)->toHaveCount(8);
        expect($nonNavigation)->toHaveCount(3);
    });

    it('navigation labels have no Navigate prefix', function () {
        $cases = array_filter(ApplicationHotkey::cases(), fn ($h) => $h->isNavigation());

        foreach ($cases as $hotkey) {
            expect($hotkey->label())->not->toStartWith('Navigate');
        }
    });

    it('every navigation case resolves a url', function () {
        $cases = array_filter(ApplicationHotkey::cases(), fn ($h) => $h->isNavigation());

        foreach ($cases as $hotkey) {
            expect($hotkey->navigationUrl())->toBeString()->not->toBeEmpty();
        }
    });

    it('non-navigation cases return null url', function () {
        expect(ApplicationHotkey::ToggleSession->navigationUrl())->toBeNull();
        expect(ApplicationHotkey::NewClient->navigationUrl())->toBeNull();
        expect(ApplicationHotkey::NewProject->navigationUrl())->toBeNull();
    });

    it('exposes all cases via options() with value and label keys', function () {
        $options = ApplicationHotkey::options();

        expect($options)->toHaveCount(count(ApplicationHotkey::cases()));
        $options->each(fn ($option) => expect($option)->toHaveKeys(['value', 'label']));
    });

    it('options labels match expected user-facing strings', function () {
        $map = ApplicationHotkey::options()->keyBy('label');

        expect($map->has('Dashboard'))->toBeTrue();
        expect($map->has('Settings'))->toBeTrue();
        expect($map->has('Toggle Session'))->toBeTrue();
        expect($map->has('New Client'))->toBeTrue();
        expect($map->has('New Project'))->toBeTrue();
    });
});
