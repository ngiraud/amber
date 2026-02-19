<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Actions\Action;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class DeleteClient extends Action
{
    public function handle(Client $client): void
    {
        DB::transaction(function () use ($client) {
            $client->projects()->delete();
            $client->delete();
        });
    }
}
