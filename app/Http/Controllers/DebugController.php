<?php

namespace App\Http\Controllers;

use App\Models\Insight;
use Illuminate\View\View;

class DebugController extends Controller
{
    public function index(): View
    {
        $allInsights = Insight::query()->get();

        $countsByType = $allInsights
            ->groupBy('analysis_type')
            ->map(fn ($items) => $items->count())
            ->toArray();

        return view('debug.index', [
            'totalInsights' => $allInsights->count(),
            'countsByType' => $countsByType,
            'profitSample' => $allInsights->where('analysis_type', 'profit_analysis')->take(3),
            'discountSample' => $allInsights->where('analysis_type', 'discount_effectiveness')->take(3),
            'trendSample' => $allInsights->where('analysis_type', 'sales_trend')->take(3),
            'latestInsight' => $allInsights->sortByDesc('created_at')->first(),
        ]);
    }
}
