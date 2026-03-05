<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoundingStrategy;
use App\Models\ActivityEvent;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Nico',
            'email' => 'contact@ngiraud.me',
        ]);

        $acme = Client::factory()->create(['name' => 'Acme Corp']);
        $startup = Client::factory()->create(['name' => 'Startup Inc']);

        $website = Project::factory()->inactive()->create([
            'client_id' => $acme->id,
            'name' => 'Website Redesign',
            'color' => '#6366f1',
            'daily_rate' => 800,
            'hourly_rate' => null,
            'rounding' => RoundingStrategy::Quarter,
        ]);

        $website->repositories()->createMany([
            ['local_path' => '/Users/nico/code/acme-website', 'name' => 'acme-website'],
            ['local_path' => '/Users/nico/code/acme-api', 'name' => 'acme-api'],
        ]);

        $mobile = Project::factory()->inactive()->create([
            'client_id' => $acme->id,
            'name' => 'Mobile App',
            'color' => '#f59e0b',
            'hourly_rate' => 95,
            'daily_rate' => null,
            'rounding' => RoundingStrategy::HalfHour,
        ]);

        $mobile->repositories()->createMany([
            ['local_path' => '/Users/nico/code/acme-mobile', 'name' => 'acme-mobile'],
            ['local_path' => '/Users/nico/code/acme-mobile-api', 'name' => 'acme-mobile-api'],
        ]);

        $saas = Project::factory()->create([
            'id' => '01kj5qc6ad8d7408erk4e4dtq9',
            'client_id' => $startup->id,
            'name' => 'SaaS Platform',
            'color' => '#10b981',
            'daily_rate' => 700,
            'hourly_rate' => null,
            'rounding' => RoundingStrategy::Quarter,
        ]);

        $saas->repositories()->createMany([
            ['local_path' => '/Users/nico/Web/saas-starter', 'name' => 'SAAS Starter', 'id' => '01kj5qc6ae8e484z0fczpqkzsq'],
            ['local_path' => '/Users/nico/code/saas-mobile', 'name' => 'SAAS Mobile'],
        ]);

        $thisProject = Project::factory()->create([
            'id' => '01kj5qc6afcnqgn9zek1nx1zsz',
            'client_id' => $startup->id,
            'name' => 'CRA project',
            'color' => '#000000',
            'daily_rate' => 700,
            'hourly_rate' => null,
            'rounding' => RoundingStrategy::Quarter,
        ]);

        $thisProject->repositories()->createMany([
            ['local_path' => '/Users/nico/code/activity-record-desktop', 'name' => 'CRA Tracker', 'id' => '01kj5qc6agq8dw5z7pqg6xqg0v'],
        ]);

        $this->pushActivityEvents();
    }

    protected function pushActivityEvents(): void
    {
        $json = file_get_contents(storage_path('sample-data/activity_events.json'));

        collect(json_decode($json, true))
            ->chunk(100)
            ->each(function ($chunk) {
                ActivityEvent::insert($chunk->all());
            });

        DB::table('activity_events')->update(['session_id' => null]);
    }
}
