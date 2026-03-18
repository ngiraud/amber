<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::apiResource('projects', ProjectController::class)->only('index');
Route::apiResource('clients', ClientController::class)->only('index');
