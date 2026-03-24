<?php

declare(strict_types=1);

use App\Actions\Client\UpdateClientNotes;
use App\Models\Client;

pest()->group('client');

describe('update client notes', function () {
    it('delegates to UpdateClientNotes action and redirects back', function () {
        $client = Client::factory()->create();

        UpdateClientNotes::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $client->id),
                '<p>Some notes</p>',
            )
            ->andReturn($client);

        $this->patch(route('clients.notes.update', $client), ['notes' => '<p>Some notes</p>'])
            ->assertRedirectBack();
    });

    it('accepts null notes', function () {
        $client = Client::factory()->create();

        UpdateClientNotes::fake()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $client->id),
                null,
            )
            ->andReturn($client);

        $this->patch(route('clients.notes.update', $client), ['notes' => null])
            ->assertRedirectBack();
    });
})->group('controllers');

describe('UpdateClientNotes action', function () {
    it('updates only the notes field', function () {
        $client = Client::factory()->create(['name' => 'Acme', 'notes' => null]);

        UpdateClientNotes::make()->handle($client, '<p>Hello</p>');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Acme',
            'notes' => '<p>Hello</p>',
        ]);
    });

    it('clears notes when null is passed', function () {
        $client = Client::factory()->create(['notes' => '<p>Old notes</p>']);

        UpdateClientNotes::make()->handle($client, null);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'notes' => null,
        ]);
    });
})->group('actions');
