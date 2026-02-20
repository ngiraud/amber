<?php

declare(strict_types=1);

use App\Actions\Client\UpdateClient;
use App\Data\ClientData;
use App\Models\Client;

pest()->group('client');

describe('update client', function () {
    it('delegates to UpdateClient action and redirects to show', function () {
        $client = Client::factory()->create();

        UpdateClient::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $client->id),
                Mockery::on(fn ($data) => $data->name === 'Updated Name'),
            )
            ->andReturn($client);

        $this->patch(route('clients.update', $client), ['name' => 'Updated Name'])
            ->assertRedirectToRoute('clients.show', $client);
    });

    it('shows the edit form with client data', function () {
        $client = Client::factory()->create();

        $this->get(route('clients.edit', $client))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('client/Form')
                ->has('client')
            );
    });

    it('validates that name is required', function () {
        $client = Client::factory()->create();

        $this->patch(route('clients.update', $client), [])
            ->assertInvalid(['name']);
    });
})->group('controllers');

describe('UpdateClient action', function () {
    it('updates the client in the database', function () {
        $client = Client::factory()->create(['name' => 'Old Name']);
        $data = new ClientData(name: 'New Name');

        UpdateClient::make()->handle($client, $data);

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'New Name']);
    });
})->group('actions');
