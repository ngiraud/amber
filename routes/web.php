<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityEventController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectRepositoryController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionTimerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimelineController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

// Activity
Route::get('/activity', ActivityEventController::class)->name('activity.index');

// Clients
Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

// Projects
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/clients/{client}/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/clients/{client}/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/clients/{client}/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/clients/{client}/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('/clients/{client}/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/clients/{client}/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// Repositories
Route::post('/projects/{project}/repositories', [ProjectRepositoryController::class, 'store'])->name('projects.repositories.store');
Route::delete('/projects/{project}/repositories/{repository}', [ProjectRepositoryController::class, 'destroy'])->name('projects.repositories.destroy');

// Sessions — Timer
Route::post('/sessions/start', [SessionTimerController::class, 'start'])->name('sessions.start');
Route::patch('/sessions/{session}/stop', [SessionTimerController::class, 'stop'])->name('sessions.stop');
Route::post('/sessions/reconstruct', [SessionTimerController::class, 'reconstruct'])->name('sessions.reconstruct');

// Sessions — CRUD
Route::get('/sessions/{session?}', [SessionController::class, 'index'])->name('sessions.index');
Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
Route::patch('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

// Timeline
Route::get('/timeline/{date}/{session?}', [TimelineController::class, 'show'])->name('timeline.show');
Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline.index');

// Settings
Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
