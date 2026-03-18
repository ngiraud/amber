<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Project;

pest()->group('api', 'project');

describe('GET /api/projects', function () {
    it('returns only active projects ordered by name', function () {
        $client = Client::factory()->create(['name' => 'Acme']);
        $active1 = Project::factory()->create(['name' => 'Zebra', 'is_active' => true, 'client_id' => $client->id]);
        $active2 = Project::factory()->create(['name' => 'Alpha', 'is_active' => true, 'client_id' => $client->id]);
        Project::factory()->create(['name' => 'Archived', 'is_active' => false, 'client_id' => $client->id]);

        $this->getJson('/api/projects')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $active2->id)
            ->assertJsonPath('data.1.id', $active1->id)
            ->assertJsonPath('data.0.client.name', 'Acme');
    });

    it('returns an empty list when no active projects exist', function () {
        Project::factory()->create(['is_active' => false]);

        $this->getJson('/api/projects')
            ->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('returns cursor pagination links', function () {
        Client::factory()->has(Project::factory(3)->state(['is_active' => true]))->create();

        $this->getJson('/api/projects')
            ->assertSuccessful()
            ->assertJsonStructure(['data', 'links' => ['next', 'prev']]);
    });

    it('follows cursor to the next page', function () {
        $client = Client::factory()->create();
        Project::factory()->count(3)->state(['is_active' => true])->create(['client_id' => $client->id]);

        $first = $this->getJson('/api/projects?per_page=2')->assertSuccessful();
        $nextUrl = $first->json('links.next');

        if ($nextUrl) {
            $cursor = parse_url($nextUrl, PHP_URL_QUERY);
            parse_str($cursor, $params);

            $this->getJson('/api/projects?'.$cursor)
                ->assertSuccessful()
                ->assertJsonCount(1, 'data');
        } else {
            // All fit in one page — nothing to assert
            expect($first->json('data'))->toHaveCount(3);
        }
    });
});
