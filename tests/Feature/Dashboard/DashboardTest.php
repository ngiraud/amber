<?php

declare(strict_types=1);

pest()->group('controllers');

test('home renders the dashboard page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('date')
            ->has('sessions')
            ->has('total_minutes')
            ->has('week_minutes')
            ->has('month_minutes')
            ->has('projects')
        );
});
