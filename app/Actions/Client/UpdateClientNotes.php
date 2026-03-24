<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Actions\Action;
use App\Models\Client;

class UpdateClientNotes extends Action
{
    public function handle(Client $client, ?string $notes): Client
    {
        $client->update(['notes' => $notes]);

        return $client;
    }
}
