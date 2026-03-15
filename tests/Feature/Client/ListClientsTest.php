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

    it('orders clients alphabetically by name', function () {
        $clientC = Client::factory()->create(['name' => 'Charlie']);
        $clientA = Client::factory()->create(['name' => 'Alpha']);
        $clientB = Client::factory()->create(['name' => 'Beta']);

        $this->get(route('clients.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('clients.data.0.id', $clientA->id)
                ->where('clients.data.1.id', $clientB->id)
                ->where('clients.data.2.id', $clientC->id)
            );
    });
})->group('controllers');
