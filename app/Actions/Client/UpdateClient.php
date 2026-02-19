<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Actions\Action;
use App\Data\ClientData;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class UpdateClient extends Action
{
    public function handle(Client $client, ClientData $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->update($data->toArray());

            return $client;
        });
    }
}
