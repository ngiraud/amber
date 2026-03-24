<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Client\UpdateClientNotes;
use App\Http\Requests\Client\UpdateClientNotesRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;

class ClientNotesController extends Controller
{
    public function __invoke(UpdateClientNotesRequest $request, Client $client, UpdateClientNotes $action): RedirectResponse
    {
        $action->handle($client, $request->validated('notes'));

        return back();
    }
}
