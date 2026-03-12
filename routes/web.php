<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityEventController;
use App\Http\Controllers\ActivityReportController;
use App\Http\Controllers\ActivityReportExportController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DismissOnboardingController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectRepositoryController;
use App\Http\Controllers\RegenerateActivityReportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionTimerController;
use App\Http\Controllers\Settings\ActivitySettingsController;
use App\Http\Controllers\Settings\ActivitySourceSettingsController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\Settings\GeneralSettingsController;
use App\Http\Controllers\Settings\ResetDatabaseController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\ToggleProjectStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

// Onboarding
Route::post('/onboarding/dismiss', DismissOnboardingController::class)->name('onboarding.dismiss');

// Activity
Route::get('/activity', ActivityEventController::class)->name('activity.index');

// Clients
Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

// Projects
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
Route::post('/projects/{project}/toggle-status', ToggleProjectStatusController::class)->name('projects.toggle-status');

// Repositories
Route::scopeBindings()->group(function () {
    Route::post('/projects/{project}/repositories', [ProjectRepositoryController::class, 'store'])->name('projects.repositories.store');
    Route::delete('/projects/{project}/repositories/{repository}', [ProjectRepositoryController::class, 'destroy'])->name('projects.repositories.destroy');
});

// Sessions — Timer
Route::post('/sessions/start', [SessionTimerController::class, 'start'])->name('sessions.start');
Route::patch('/sessions/{session}/stop', [SessionTimerController::class, 'stop'])->name('sessions.stop');
Route::post('/sessions/reconstruct', [SessionTimerController::class, 'reconstruct'])->name('sessions.reconstruct');
Route::post('/sessions/reconstruct-from', [SessionTimerController::class, 'reconstructFrom'])->name('sessions.reconstruct-from');

// Sessions — CRUD
Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
Route::get('/sessions/{session}', [SessionController::class, 'show'])->name('sessions.show');
Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
Route::patch('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');

// Timeline
Route::get('/timeline/{date}', [TimelineController::class, 'show'])->name('timeline.show');
Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline.index');

// Reports
Route::get('/reports', [ActivityReportController::class, 'index'])->name('reports.index');
Route::post('/reports', [ActivityReportController::class, 'store'])->name('reports.store');
Route::get('/reports/{report}', [ActivityReportController::class, 'show'])->name('reports.show');
Route::delete('/reports/{report}', [ActivityReportController::class, 'destroy'])->name('reports.destroy');
Route::post('/reports/{report}/regenerate', RegenerateActivityReportController::class)->name('reports.regenerate');
Route::get('/reports/{report}/{format}', ActivityReportExportController::class)->name('reports.export');

// Settings
Route::redirect('/settings', '/settings/general')->name('settings.index');
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general');
    Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.update');

    Route::get('/activity', [ActivitySettingsController::class, 'edit'])->name('activity');
    Route::put('/activity', [ActivitySettingsController::class, 'update'])->name('activity.update');

    Route::get('/sources', [ActivitySourceSettingsController::class, 'edit'])->name('sources');
    Route::put('/sources/{source}', [ActivitySourceSettingsController::class, 'update'])->name('sources.update');
    Route::post('/sources/{source}/test', [ActivitySourceSettingsController::class, 'test'])->name('sources.test');

    Route::get('/ai', [AiSettingsController::class, 'edit'])->name('ai');
    Route::put('/ai', [AiSettingsController::class, 'update'])->name('ai.update');
    Route::post('/ai/test', [AiSettingsController::class, 'test'])->name('ai.test');

    Route::post('/reset', ResetDatabaseController::class)->name('reset');
});
