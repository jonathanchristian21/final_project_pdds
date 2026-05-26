<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - MongoDB Insights</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
        <h1 class="text-3xl font-bold text-emerald-400 mb-2">Debug MongoDB Insights</h1>
        <p class="text-gray-400 mb-4">Pengecekan data collection: <span class="font-mono text-emerald-400">insights</span></p>
        <a href="{{ route('insights.index') }}" class="inline-block px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors text-sm">
            ← Kembali ke Dashboard
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Total Documents</div>
            <div class="text-3xl font-bold text-emerald-400">{{ $totalInsights }}</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Profit Analysis</div>
            <div class="text-3xl font-bold text-blue-400">{{ $countsByType['profit_analysis'] ?? 0 }}</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Discount Effectiveness</div>
            <div class="text-3xl font-bold text-purple-400">{{ $countsByType['discount_effectiveness'] ?? 0 }}</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Sales Trend</div>
            <div class="text-3xl font-bold text-yellow-400">{{ $countsByType['sales_trend'] ?? 0 }}</div>
        </div>
    </div>

    @if($latestInsight)
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-emerald-400 mb-4">Latest Insight (Full JSON)</h2>
        <div class="bg-gray-900 rounded p-4 overflow-x-auto">
            <pre class="text-xs text-gray-300 font-mono">{{ json_encode($latestInsight->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    @endif

    <!-- Profit Analysis Samples -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-blue-400 mb-4">Sample: Profit Analysis (3 dokumen)</h2>
        @if($profitSample->isEmpty())
            <p class="text-gray-500">Tidak ada data</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($profitSample as $item)
                    <div class="bg-gray-900 border border-gray-700 rounded p-4">
                        <h3 class="font-bold text-yellow-400 mb-3 text-sm">{{ $item->dimensions['category'] ?? '-' }} / {{ $item->dimensions['region'] ?? '-' }}</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">analysis_type:</span>
                                <span class="text-orange-400">{{ $item->analysis_type }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">total_sales:</span>
                                <span class="text-orange-400">${{ number_format($item->metrics['total_sales'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">total_profit:</span>
                                <span class="text-orange-400">${{ number_format($item->metrics['total_profit'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">profit_margin:</span>
                                <span class="text-orange-400">{{ number_format($item->metrics['profit_margin'] ?? 0, 2) }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-400">profit_status:</span>
                                <span class="text-orange-400">{{ $item->analysis_result['profit_status'] ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Discount Effectiveness Samples -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-purple-400 mb-4">Sample: Discount Effectiveness (3 dokumen)</h2>
        @if($discountSample->isEmpty())
            <p class="text-gray-500">Tidak ada data</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($discountSample as $item)
                    <div class="bg-gray-900 border border-gray-700 rounded p-4">
                        <h3 class="font-bold text-yellow-400 mb-3 text-sm">{{ $item->dimensions['category'] ?? '-' }} / {{ $item->dimensions['sub_category'] ?? '-' }}</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">analysis_type:</span>
                                <span class="text-orange-400">{{ $item->analysis_type }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">avg_discount:</span>
                                <span class="text-orange-400">{{ number_format(($item->metrics['avg_discount'] ?? 0) * 100, 2) }}%</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">total_profit:</span>
                                <span class="text-orange-400">${{ number_format($item->metrics['total_profit'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">effectiveness:</span>
                                <span class="text-orange-400">{{ $item->analysis_result['effectiveness'] ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-400">period_count:</span>
                                <span class="text-orange-400">{{ count($item->period_data ?? []) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Sales Trend Samples -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-yellow-400 mb-4">Sample: Sales Trend (3 dokumen)</h2>
        @if($trendSample->isEmpty())
            <p class="text-gray-500">Tidak ada data</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($trendSample as $item)
                    <div class="bg-gray-900 border border-gray-700 rounded p-4">
                        <h3 class="font-bold text-yellow-400 mb-3 text-sm">{{ $item->dimensions['category'] ?? '-' }} / {{ $item->dimensions['region'] ?? '-' }}</h3>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">analysis_type:</span>
                                <span class="text-orange-400">{{ $item->analysis_type }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">total_sales:</span>
                                <span class="text-orange-400">${{ number_format($item->metrics['total_sales'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">growth_rate:</span>
                                <span class="text-orange-400">{{ number_format($item->analysis_result['growth_rate'] ?? 0, 2) }}%</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-700 pb-1">
                                <span class="text-blue-400">sales_trend:</span>
                                <span class="text-orange-400">{{ $item->analysis_result['sales_trend'] ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-400">period_count:</span>
                                <span class="text-orange-400">{{ count($item->period_data ?? []) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

</body>
</html>
