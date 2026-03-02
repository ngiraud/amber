<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Client\CreateClient;
use App\Actions\Client\DeleteClient;
use App\Actions\Client\UpdateClient;
use App\Data\ClientData;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\ViewModels\EventsViewModel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('client/Index', [
            'clients' => ClientResource::collection(
                Client::query()->withCount('projects')->latest('id')->paginate()
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request, CreateClient $action): RedirectResponse
    {
        $client = $action->handle(ClientData::fromArray($request->validated()));

        return redirect()->route('clients.show', $client);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client, EventsViewModel $eventsViewModel): Response
    {
        return Inertia::render('client/Show', [
            'client' => fn () => ClientResource::make($client->load('projects')),
            'clients' => fn () => ClientResource::collection(Client::query()->orderBy('name')->get()),
            $eventsViewModel,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client, UpdateClient $action): RedirectResponse
    {
        $client = $action->handle($client, ClientData::fromArray($request->validated()));

        return redirect()->route('clients.show', $client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client, DeleteClient $action): RedirectResponse
    {
        $action->handle($client);

        return redirect()->route('clients.index');
    }
}
