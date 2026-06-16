<?php

namespace App\Console\Commands;

use App\Models\Insight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInsights extends Command
{
    protected $signature = 'generate:insights {--type= : Tipe insights (profit_analysis|discount_effectiveness|sales_trend), kosongkan untuk semua}';

    protected $description = 'Generate insights dari MySQL ke MongoDB berdasarkan 3 tipe analisis';

    public function handle(): void
    {
        $this->info('Generating insights from MySQL to MongoDB...');

        Insight::truncate();

        $type = $this->option('type');
        $types = $type ? [$type] : ['profit_analysis', 'discount_effectiveness', 'sales_trend'];

        foreach ($types as $analysisType) {
            match ($analysisType) {
                'profit_analysis' => $this->generateProfitAnalysis(),
                'discount_effectiveness' => $this->generateDiscountEffectiveness(),
                'sales_trend' => $this->generateSalesTrend(),
                default => $this->error("Unknown type: {$analysisType}"),
            };
        }

        $this->info("\nAll insights generated successfully.");
    }

    private function generateProfitAnalysis(): void
    {
        $this->info("\nGenerating profit analysis...");

        $results = DB::table('sales_facts as sf')
            ->join('products as p', 'sf.product_id', '=', 'p.product_id')
            ->join('locations as l', 'sf.location_id', '=', 'l.location_id')
            ->join('date_dimensions as dd', 'sf.order_date_id', '=', 'dd.date_id')
            ->select(
                'p.category',
                'l.region',
                'dd.year',
                'dd.month',
                'l.state',
                'p.sub_category',
                DB::raw('SUM(sf.sales) as period_sales'),
                DB::raw('SUM(ROUND(sf.profit, 2)) as period_profit'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('p.category', 'l.region', 'dd.year', 'dd.month', 'l.state', 'p.sub_category')
            ->get();

        $grouped = [];
        $rawGrouped = [];

        foreach ($results as $row) {
            $key = "{$row->category}|{$row->region}";
            $periodKey = "{$row->year}-{$row->month}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $row->category,
                    'region' => $row->region,
                    'periods' => [],
                ];
                $rawGrouped[$key] = [
                    'sales' => 0,
                    'profit' => 0,
                    'transaction_count' => 0,
                ];
            }

            if (!isset($grouped[$key]['periods'][$periodKey])) {
                $grouped[$key]['periods'][$periodKey] = [
                    'year' => (int) $row->year,
                    'month' => (int) $row->month,
                    'sales' => 0,
                    'profit' => 0,
                    'transaction_count' => 0,
                    'drilldown' => [
                        'state' => [],
                        'subcategory' => [],
                    ]
                ];
            }

            $periodSales = (float) $row->period_sales;
            $periodProfit = (float) $row->period_profit;
            $txnCount = (int) $row->transaction_count;

            $rawGrouped[$key]['sales'] += $periodSales;
            $rawGrouped[$key]['profit'] += $periodProfit;
            $rawGrouped[$key]['transaction_count'] += $txnCount;

            $grouped[$key]['periods'][$periodKey]['sales'] += $periodSales;
            $grouped[$key]['periods'][$periodKey]['profit'] += $periodProfit;
            $grouped[$key]['periods'][$periodKey]['transaction_count'] += $txnCount;

            $state = $row->state;
            if (!isset($grouped[$key]['periods'][$periodKey]['drilldown']['state'][$state])) {
                $grouped[$key]['periods'][$periodKey]['drilldown']['state'][$state] = [
                    'sales' => 0,
                    'profit' => 0,
                    'transaction_count' => 0
                ];
            }
            $grouped[$key]['periods'][$periodKey]['drilldown']['state'][$state]['sales'] += $periodSales;
            $grouped[$key]['periods'][$periodKey]['drilldown']['state'][$state]['profit'] += $periodProfit;
            $grouped[$key]['periods'][$periodKey]['drilldown']['state'][$state]['transaction_count'] += $txnCount;

            $subcat = $row->sub_category;
            if (!isset($grouped[$key]['periods'][$periodKey]['drilldown']['subcategory'][$subcat])) {
                $grouped[$key]['periods'][$periodKey]['drilldown']['subcategory'][$subcat] = [
                    'sales' => 0,
                    'profit' => 0,
                    'transaction_count' => 0
                ];
            }
            $grouped[$key]['periods'][$periodKey]['drilldown']['subcategory'][$subcat]['sales'] += $periodSales;
            $grouped[$key]['periods'][$periodKey]['drilldown']['subcategory'][$subcat]['profit'] += $periodProfit;
            $grouped[$key]['periods'][$periodKey]['drilldown']['subcategory'][$subcat]['transaction_count'] += $txnCount;
        }

        foreach ($grouped as $key => &$data) {
            $periodsList = [];
            foreach ($data['periods'] as $pk => $p) {
                $p['profit_margin'] = round($this->calculateMargin($p['profit'], $p['sales']), 2);
                $p['sales'] = round($p['sales'], 2);
                $p['profit'] = round($p['profit'], 2);
                $periodsList[] = $p;
            }
            usort($periodsList, fn($a, $b) => $a['year'] <=> $b['year'] ?: $a['month'] <=> $b['month']);
            $data['periods'] = $periodsList;
        }
        unset($data);

        $bar = $this->output->createProgressBar(count($grouped));
        $bar->start();

        foreach ($grouped as $key => $data) {
            $totalSales = $rawGrouped[$key]['sales'];
            $totalProfit = $rawGrouped[$key]['profit'];
            $transactionCount = $rawGrouped[$key]['transaction_count'];
            $profitMargin = $this->calculateMargin($totalProfit, $totalSales);
            $profitStatus = $totalProfit >= 0 ? 'profit' : 'loss';

            Insight::create([
                'analysis_type' => 'profit_analysis',
                'dimensions' => [
                    'category' => $data['category'],
                    'region' => $data['region'],
                ],
                'metrics' => [
                    'total_sales' => round($totalSales, 2),
                    'total_profit' => round($totalProfit, 2),
                    'profit_margin' => round($profitMargin, 2),
                    'transaction_count' => $transactionCount,
                ],
                'period_data' => $data['periods'],
                'analysis_result' => [
                    'profit_status' => $profitStatus,
                    'summary' => "Kategori {$data['category']} di region {$data['region']} "
                        . ($profitStatus === 'profit' ? 'menguntungkan' : 'merugi'),
                ],
                'created_at' => now(),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line('Profit analysis created: ' . count($grouped) . ' insights');
    }

    private function generateDiscountEffectiveness(): void
    {
        $this->info("\nGenerating discount effectiveness...");

        $results = DB::table('sales_facts as sf')
            ->join('products as p', 'sf.product_id', '=', 'p.product_id')
            ->join('date_dimensions as dd', 'sf.order_date_id', '=', 'dd.date_id')
            ->select(
                'p.category',
                'p.sub_category',
                'dd.year',
                'dd.month',
                DB::raw('AVG(sf.discount) as avg_discount'),
                DB::raw('SUM(ROUND(sf.profit, 2)) as total_profit'),
                DB::raw('SUM(sf.sales) as total_sales'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(sf.sales) as avg_sales')
            )
            ->groupBy('p.category', 'p.sub_category', 'dd.year', 'dd.month')
            ->orderBy('p.category')
            ->orderBy('p.sub_category')
            ->orderBy('dd.year')
            ->orderBy('dd.month')
            ->get();

        $grouped = [];
        $rawGrouped = [];

        foreach ($results as $row) {
            $key = "{$row->category}|{$row->sub_category}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $row->category,
                    'sub_category' => $row->sub_category,
                    'periods' => [],
                ];
                $rawGrouped[$key] = [
                    'profit' => 0,
                    'sales' => 0,
                    'transaction_count' => 0,
                    'discount_weighted' => 0,
                ];
            }

            $profit = (float) $row->total_profit;
            $sales = (float) $row->total_sales;
            $transCount = (int) $row->transaction_count;
            $avgDiscount = (float) $row->avg_discount;
            $profitMargin = $this->calculateMargin($profit, $sales);

            $rawGrouped[$key]['profit'] += $profit;
            $rawGrouped[$key]['sales'] += $sales;
            $rawGrouped[$key]['transaction_count'] += $transCount;
            $rawGrouped[$key]['discount_weighted'] += $avgDiscount * $transCount;

            $grouped[$key]['periods'][] = [
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'avg_discount' => round($avgDiscount, 4),
                'profit' => round($profit, 2),
                'profit_margin' => round($profitMargin, 2),
                'avg_sales' => round((float) $row->avg_sales, 2),
                'transaction_count' => $transCount,
            ];
        }

        $bar = $this->output->createProgressBar(count($grouped));
        $bar->start();

        foreach ($grouped as $key => $data) {
            $transactionCount = $rawGrouped[$key]['transaction_count'];
            $avgDiscount = $transactionCount > 0 ? $rawGrouped[$key]['discount_weighted'] / $transactionCount : 0;
            $totalProfit = $rawGrouped[$key]['profit'];
            $totalSales = $rawGrouped[$key]['sales'];
            $avgProfitMargin = $this->calculateMargin($totalProfit, $totalSales);
            $correlation = $this->calculatePearsonCorrelation(
                array_column($data['periods'], 'avg_discount'),
                array_column($data['periods'], 'profit_margin')
            );
            $negativePeriodRatio = $this->calculateNegativePeriodRatio($data['periods'], 'profit_margin');
            $effectiveness = $this->analyzeDiscountEffectiveness(
                $data['periods'],
                $avgDiscount,
                $avgProfitMargin,
                $correlation,
                $negativePeriodRatio
            );

            Insight::create([
                'analysis_type' => 'discount_effectiveness',
                'dimensions' => [
                    'category' => $data['category'],
                    'sub_category' => $data['sub_category'],
                ],
                'metrics' => [
                    'avg_discount' => round($avgDiscount, 4),
                    'avg_profit_margin' => round($avgProfitMargin, 2),
                    'transaction_count' => $transactionCount,
                ],
                'period_data' => $data['periods'],
                'analysis_result' => [
                    'effectiveness' => $effectiveness,
                    'discount_margin_correlation' => round($correlation, 4),
                    'negative_period_ratio' => round($negativePeriodRatio, 4),
                    'summary' => "Strategi diskon {$data['category']} - {$data['sub_category']} dinilai {$effectiveness}",
                ],
                'created_at' => now(),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line('Discount effectiveness created: ' . count($grouped) . ' insights');
    }

    private function generateSalesTrend(): void
    {
        $this->info("\nGenerating sales trend...");

        $results = DB::table('sales_facts as sf')
            ->join('products as p', 'sf.product_id', '=', 'p.product_id')
            ->join('locations as l', 'sf.location_id', '=', 'l.location_id')
            ->join('date_dimensions as dd', 'sf.order_date_id', '=', 'dd.date_id')
            ->select(
                'p.category',
                'l.region',
                'dd.year',
                'dd.month',
                DB::raw('SUM(sf.sales) as total_sales'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(sf.sales) as avg_sales')
            )
            ->groupBy('p.category', 'l.region', 'dd.year', 'dd.month')
            ->orderBy('p.category')
            ->orderBy('l.region')
            ->orderBy('dd.year')
            ->orderBy('dd.month')
            ->get();

        $grouped = [];

        foreach ($results as $row) {
            $key = "{$row->category}|{$row->region}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'category' => $row->category,
                    'region' => $row->region,
                    'periods' => [],
                ];
            }

            $grouped[$key]['periods'][] = [
                'year' => (int) $row->year,
                'month' => (int) $row->month,
                'sales' => round((float) $row->total_sales, 2),
                'transaction_count' => (int) $row->transaction_count,
                'avg_sales' => round((float) $row->avg_sales, 2),
            ];
        }

        $bar = $this->output->createProgressBar(count($grouped));
        $bar->start();

        foreach ($grouped as $data) {
            $growthSnapshot = $this->calculateAnnualGrowthSnapshot($data['periods']);
            $growthRate = $growthSnapshot['growth_rate'];
            $trend = $growthRate > 0 ? 'increasing' : ($growthRate < 0 ? 'decreasing' : 'stable');

            Insight::create([
                'analysis_type' => 'sales_trend',
                'dimensions' => [
                    'category' => $data['category'],
                    'region' => $data['region'],
                ],
                'metrics' => [
                    'total_sales' => round($this->calculateSumFromPeriods($data['periods'], 'sales'), 2),
                    'transaction_count' => (int) $this->calculateSumFromPeriods($data['periods'], 'transaction_count'),
                    'avg_monthly_sales' => round($this->calculateAverageFromPeriods($data['periods'], 'sales'), 2),
                ],
                'period_data' => $data['periods'],
                'analysis_result' => [
                    'growth_rate' => round($growthRate, 2),
                    'sales_trend' => $trend,
                    'comparison_basis' => $growthSnapshot['basis'],
                    'summary' => "Tren penjualan {$data['category']} di {$data['region']}: {$trend} ({$growthSnapshot['basis']})",
                ],
                'created_at' => now(),
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line('Sales trend created: ' . count($grouped) . ' insights');
    }

    private function calculateAnnualGrowthSnapshot(array $periods): array
    {
        if (empty($periods)) {
            return [
                'growth_rate' => 0,
                'basis' => 'insufficient data',
            ];
        }

        $yearly = [];

        foreach ($periods as $period) {
            $year = (int) $period['year'];

            if (!isset($yearly[$year])) {
                $yearly[$year] = [
                    'sales' => 0.0,
                    'months' => [],
                ];
            }

            $yearly[$year]['sales'] += (float) ($period['sales'] ?? 0);
            $yearly[$year]['months'][(int) $period['month']] = true;
        }

        ksort($yearly);

        $eligible = array_filter($yearly, static fn (array $item) => count($item['months']) >= 6);
        $series = count($eligible) >= 2 ? $eligible : $yearly;

        $years = array_keys($series);
        $firstYear = (int) $years[0];
        $lastYear = (int) $years[count($years) - 1];
        $firstSales = (float) ($series[$firstYear]['sales'] ?? 0);
        $lastSales = (float) ($series[$lastYear]['sales'] ?? 0);

        if ($firstSales == 0.0) {
            return [
                'growth_rate' => 0,
                'basis' => "{$firstYear} to {$lastYear}",
            ];
        }

        return [
            'growth_rate' => (($lastSales - $firstSales) / $firstSales) * 100,
            'basis' => "{$firstYear} to {$lastYear}",
        ];
    }

    private function analyzeDiscountEffectiveness(
        array $periods,
        float $avgDiscount,
        float $avgProfitMargin,
        float $correlation,
        float $negativePeriodRatio
    ): string {
        return $avgProfitMargin >= 0 ? 'positive' : 'negative';
    }

    private function calculateMargin(float $profit, float $sales): float
    {
        if ($sales == 0.0) {
            return 0;
        }

        return ($profit / $sales) * 100;
    }

    private function calculateSumFromPeriods(array $periods, string $field): float
    {
        return array_reduce($periods, static function (float $carry, array $item) use ($field) {
            return $carry + (float) ($item[$field] ?? 0);
        }, 0.0);
    }

    private function calculateAverageFromPeriods(array $periods, string $field): float
    {
        if (empty($periods)) {
            return 0;
        }

        return $this->calculateSumFromPeriods($periods, $field) / count($periods);
    }

    private function calculateWeightedAverageFromPeriods(array $periods, string $valueField, string $weightField): float
    {
        $weightedTotal = 0.0;
        $weightTotal = 0.0;

        foreach ($periods as $period) {
            $value = (float) ($period[$valueField] ?? 0);
            $weight = (float) ($period[$weightField] ?? 0);
            $weightedTotal += $value * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal == 0.0) {
            return $this->calculateAverageFromPeriods($periods, $valueField);
        }

        return $weightedTotal / $weightTotal;
    }

    private function calculatePearsonCorrelation(array $xValues, array $yValues): float
    {
        $count = min(count($xValues), count($yValues));

        if ($count < 2) {
            return 0;
        }

        $xMean = array_sum($xValues) / $count;
        $yMean = array_sum($yValues) / $count;
        $numerator = 0.0;
        $xVariance = 0.0;
        $yVariance = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $xDelta = $xValues[$index] - $xMean;
            $yDelta = $yValues[$index] - $yMean;
            $numerator += $xDelta * $yDelta;
            $xVariance += $xDelta ** 2;
            $yVariance += $yDelta ** 2;
        }

        if ($xVariance == 0.0 || $yVariance == 0.0) {
            return 0;
        }

        return $numerator / sqrt($xVariance * $yVariance);
    }

    private function calculateNegativePeriodRatio(array $periods, string $field): float
    {
        if (empty($periods)) {
            return 0;
        }

        $negativePeriods = array_filter($periods, static function (array $period) use ($field) {
            return (float) ($period[$field] ?? 0) < 0;
        });

        return count($negativePeriods) / count($periods);
    }
}
