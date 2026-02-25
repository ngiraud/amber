<?php

declare(strict_types=1);

use App\Models\Client;

pest()->group('client');

describe('list clients', function () {
    it('renders the index page with clients', function () {
        Client::factory()->count(3)->create();

        $this->get(route('clients.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('client/Index')
                ->has('clients.data', 3)
            );
    });
})->group('controllers');
