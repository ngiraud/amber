<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ViewModels\DashboardViewModel;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardViewModel $viewModel): Response
    {
        return Inertia::render('Dashboard', $viewModel);
    }
}
