<?php

declare(strict_types=1);

use App\Actions\Client\CreateClient;
use App\Data\ClientData;
use App\Models\Client;

pest()->group('client');

describe('create client', function () {
    it('delegates to CreateClient action and redirects to show', function () {
        $client = Client::factory()->make(['id' => 'fake-id']);

        CreateClient::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($data) => $data->name === 'Acme Corp'))
            ->andReturn($client);

        $this->post(route('clients.store'), ['name' => 'Acme Corp'])
            ->assertRedirectToRoute('clients.show', $client);
    });

    it('validates that name is required', function () {
        $this->post(route('clients.store'), [])
            ->assertInvalid(['name']);
    });

    it('validates that name does not exceed 255 characters', function () {
        $this->post(route('clients.store'), ['name' => str_repeat('a', 256)])
            ->assertInvalid(['name']);
    });
})->group('controllers');

describe('CreateClient action', function () {
    it('creates a client in the database', function () {
        $data = new ClientData(name: 'Acme Corp', address: null, contacts: null, notes: null);

        $client = CreateClient::make()->handle($data);

        expect($client)->toBeInstanceOf(Client::class)
            ->and($client->name)->toBe('Acme Corp');

        $this->assertDatabaseHas('clients', ['name' => 'Acme Corp']);
    });
})->group('actions');
