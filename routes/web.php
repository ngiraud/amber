<?php

declare(strict_types=1);

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Client
Route::get('/clients', [ClientController::class, 'index'])
    ->can('viewAny', Client::class)
    ->name('clients.index');

Route::get('/clients/create', [ClientController::class, 'create'])
    ->can('create', Client::class)
    ->name('clients.create');

Route::post('/clients', [ClientController::class, 'store'])
    ->can('create', Client::class)
    ->name('clients.store');

Route::get('/clients/{client}', [ClientController::class, 'show'])
    ->can('view', 'client')
    ->name('clients.show');

Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])
    ->can('update', 'client')
    ->name('clients.edit');

Route::patch('/clients/{client}', [ClientController::class, 'update'])
    ->can('update', 'client')
    ->name('clients.update');

Route::delete('/clients/{client}', [ClientController::class, 'destroy'])
    ->can('delete', 'client')
    ->name('clients.destroy');

// Project
Route::get('/projects', [ProjectController::class, 'index'])
    ->can('viewAny', Project::class)
    ->name('projects.index');

Route::get('/clients/{client}/projects/create', [ProjectController::class, 'create'])
    ->can('create', Project::class)
    ->name('projects.create');

Route::post('/clients/{client}/projects', [ProjectController::class, 'store'])
    ->can('create', Project::class)
    ->name('projects.store');

Route::get('/clients/{client}/projects/{project}', [ProjectController::class, 'show'])
    ->can('view', 'project')
    ->name('projects.show');

Route::get('/clients/{client}/projects/{project}/edit', [ProjectController::class, 'edit'])
    ->can('update', 'project')
    ->name('projects.edit');

Route::patch('/clients/{client}/projects/{project}', [ProjectController::class, 'update'])
    ->can('update', 'project')
    ->name('projects.update');

Route::delete('/clients/{client}/projects/{project}', [ProjectController::class, 'destroy'])
    ->can('delete', 'project')
    ->name('clients.destroy');
