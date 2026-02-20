<?php

declare(strict_types=1);

use App\Actions\Client\DeleteClient;
use App\Models\Client;
use App\Models\Project;

pest()->group('client');

describe('delete client', function () {
    it('delegates to DeleteClient action and redirects to index', function () {
        $client = Client::factory()->create();

        DeleteClient::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $client->id));

        $this->delete(route('clients.destroy', $client))
            ->assertRedirectToRoute('clients.index');
    });

    it('returns 404 for a non-existent client', function () {
        $this->delete(route('clients.destroy', 'non-existent-id'))
            ->assertNotFound();
    });
})->group('controllers');

describe('DeleteClient action', function () {
    it('deletes the client and all its projects', function () {
        $client = Client::factory()->create();
        Project::factory()->count(2)->create(['client_id' => $client->id]);

        DeleteClient::make()->handle($client);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseEmpty('projects');
    });
})->group('actions');
