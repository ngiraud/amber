<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Actions\Action;
use App\Data\ClientData;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class CreateClient extends Action
{
    public function handle(ClientData $data): Client
    {
        return DB::transaction(function () use ($data) {
            return Client::create($data->toArray());
        });
    }
}
