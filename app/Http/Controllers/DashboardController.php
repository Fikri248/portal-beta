<?php

namespace App\Http\Controllers;

use App\Services\DashboardChartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardChartService $chartService,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'charts' => $this->chartService->build($user),
        ]);
    }
}
