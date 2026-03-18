<?php

declare(strict_types=1);

use App\Models\Client;

pest()->group('api', 'client');

describe('GET /api/clients', function () {
    it('returns all clients ordered by name', function () {
        $c1 = Client::factory()->create(['name' => 'Zebra Corp']);
        $c2 = Client::factory()->create(['name' => 'Alpha Inc']);

        $this->getJson('/api/clients')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $c2->id)
            ->assertJsonPath('data.1.id', $c1->id);
    });

    it('returns an empty list when no clients exist', function () {
        $this->getJson('/api/clients')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns cursor pagination links', function () {
        Client::factory()->count(3)->create();

        $this->getJson('/api/clients')
            ->assertSuccessful()
            ->assertJsonStructure(['data', 'links' => ['next', 'prev']]);
    });
});
