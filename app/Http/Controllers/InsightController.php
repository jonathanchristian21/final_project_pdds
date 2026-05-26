<?php

namespace App\Http\Controllers;

use App\Services\InsightDashboardService;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function __construct(
        private readonly InsightDashboardService $dashboardService
    ) {
    }

    public function index(): View
    {
        return view('insights.index', [
            'overviewData' => $this->dashboardService->getOverviewData(),
        ]);
    }

    public function profitability(): View
    {
        return view('insights.profitability', [
            'dashboardData' => $this->dashboardService->getProfitabilityDashboardData(),
        ]);
    }

    public function discount(): View
    {
        return view('insights.discount', [
            'dashboardData' => $this->dashboardService->getDiscountDashboardData(),
        ]);
    }

    public function trend(): View
    {
        return view('insights.trend', [
            'dashboardData' => $this->dashboardService->getTrendDashboardData(),
        ]);
    }
}
