<?php

namespace App\Http\Controllers;

use App\Services\InsightDashboardService;
use App\Services\DataManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsightController extends Controller
{
    public function __construct(
        private readonly InsightDashboardService $dashboardService,
        private readonly DataManagementService $dataManagementService
    ) {
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // max 50MB
        ]);

        $file = $request->file('csv_file');
        $path = $file->storeAs('data', 'uploaded_superstore.csv');

        try {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
            $this->dataManagementService->uploadData($fullPath);
            return response()->json(['message' => 'Data uploaded and insights generated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function clear(): JsonResponse
    {
        try {
            $this->dataManagementService->clearData();
            return response()->json(['message' => 'All data cleared successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

    public function profitabilityDrilldown(Request $request): JsonResponse
    {
        $category = $request->query('category', '');
        $region   = $request->query('region', '');
        $level    = $request->query('level', 'state'); // 'state' | 'subcategory'
        $start    = $request->query('start', null);
        $end      = $request->query('end', null);

        $data = $this->dashboardService->getProfitabilityDrilldownData(
            $category,
            $region,
            $level,
            $start,
            $end
        );

        return response()->json($data);
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
