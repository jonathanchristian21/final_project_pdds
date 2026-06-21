<?php

namespace App\Services;

use App\Models\Insight;
use Illuminate\Support\Collection;

class InsightDashboardService
{
    private const CATEGORY_ORDER = [
        'Furniture',
        'Office Supplies',
        'Technology',
    ];

    private const REGION_ORDER = [
        'West',
        'East',
        'Central',
        'South',
    ];

    public function getOverviewData(): array
    {
        $allInsights = Insight::query()->get();
        $profitInsights = collect($this->transformProfitInsights(
            $allInsights->where('analysis_type', 'profit_analysis')
        ));
        $discountInsights = collect($this->transformDiscountInsights(
            $allInsights->where('analysis_type', 'discount_effectiveness')
        ));
        $trendInsights = collect($this->transformTrendInsights(
            $allInsights->where('analysis_type', 'sales_trend')
        ));

        $meta = $this->buildMeta($allInsights);

        $lossCount = $profitInsights
            ->filter(fn (array $item) => $item['metrics']['total_profit'] < 0)
            ->count();
        $negativeDiscountCount = $discountInsights
            ->filter(fn (array $item) => $item['analysis_result']['effectiveness'] === 'negative')
            ->count();

        return [
            'meta' => $meta,
            'summary' => [
                'documents' => $allInsights->count(),
                'total_sales' => round($profitInsights->sum('metrics.total_sales'), 2),
                'total_profit' => round($profitInsights->sum('metrics.total_profit'), 2),
                'loss_count' => $lossCount,
                'profitable_count' => $profitInsights->count() - $lossCount,
                'negative_discount_count' => $negativeDiscountCount,
                'positive_discount_count' => $discountInsights->count() - $negativeDiscountCount,
                'top_profit_segment' => $profitInsights->sortByDesc('metrics.profit_margin')->first(),
                'weakest_profit_segment' => $profitInsights->sortBy('metrics.profit_margin')->first(),
                'top_growth_segment' => $trendInsights->sortByDesc('analysis_result.last_year_growth')->first(),
                'watchlist_discount_segment' => $trendInsights->sortBy('analysis_result.last_year_growth')->first(),
            ],
        ];
    }

    public function getProfitabilityDashboardData(): array
    {
        $insights = Insight::query()
            ->where('analysis_type', 'profit_analysis')
            ->get();

        return [
            'meta' => $this->buildMeta($insights),
            'series' => $this->transformProfitInsights($insights),
        ];
    }

    public function getDiscountDashboardData(): array
    {
        $insights = Insight::query()
            ->where('analysis_type', 'discount_effectiveness')
            ->get();

        return [
            'meta' => $this->buildMeta($insights),
            'series' => $this->transformDiscountInsights($insights),
        ];
    }

    public function getTrendDashboardData(): array
    {
        $insights = Insight::query()
            ->where('analysis_type', 'sales_trend')
            ->get();

        return [
            'meta' => $this->buildMeta($insights),
            'series' => $this->transformTrendInsights($insights),
        ];
    }

    public function getProfitabilityDrilldownData(
        string $category,
        string $region,
        string $level,
        ?string $start,
        ?string $end
    ): array {
        $insight = Insight::where('analysis_type', 'profit_analysis')
            ->get()
            ->firstWhere(function ($item) use ($category, $region) {
                return ($item->dimensions['category'] ?? '') === $category 
                    && ($item->dimensions['region'] ?? '') === $region;
            });

        if (!$insight || !isset($insight->period_data)) {
            return [
                'category' => $category,
                'region'   => $region,
                'level'    => $level,
                'rows'     => [],
            ];
        }

        $aggregated = [];
        $drilldownKey = $level === 'subcategory' ? 'subcategory' : 'state';

        foreach ($insight->period_data as $period) {
            $periodStr = sprintf('%04d-%02d', $period['year'], $period['month']);

            if ($start && $periodStr < $start) {
                continue;
            }
            if ($end && $periodStr > $end) {
                continue;
            }

            if (!isset($period['drilldown'][$drilldownKey])) {
                continue;
            }

            foreach ($period['drilldown'][$drilldownKey] as $key => $data) {
                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'label'             => $key,
                        'total_sales'       => 0,
                        'total_profit'      => 0,
                        'transaction_count' => 0,
                    ];
                }

                $aggregated[$key]['total_sales']       += $data['sales'];
                $aggregated[$key]['total_profit']      += $data['profit'];
                $aggregated[$key]['transaction_count'] += $data['transaction_count'];
            }
        }

        $rows = array_values($aggregated);

        foreach ($rows as &$row) {
            $totalSales  = (float) $row['total_sales'];
            $totalProfit = (float) $row['total_profit'];
            $margin      = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;

            $row['total_sales']   = round($totalSales, 2);
            $row['total_profit']  = round($totalProfit, 2);
            $row['profit_margin'] = round($margin, 2);
            $row['status']        = $totalProfit > 0 ? 'Profit' : ($totalProfit < 0 ? 'Loss' : 'Neutral');
        }
        unset($row);

        usort($rows, fn($a, $b) => $b['total_profit'] <=> $a['total_profit']);

        return [
            'category' => $category,
            'region'   => $region,
            'level'    => $level,
            'rows'     => $rows,
        ];
    }

    private function transformProfitInsights(Collection $insights): array
    {
        return $this->sortByCategoryRegion(
            $insights->map(function (Insight $item) {
                return [
                    'category' => $item->dimensions['category'] ?? '-',
                    'region' => $item->dimensions['region'] ?? '-',
                    'metrics' => [
                        'total_sales' => round((float) ($item->metrics['total_sales'] ?? 0), 2),
                        'total_profit' => round((float) ($item->metrics['total_profit'] ?? 0), 2),
                        'profit_margin' => round((float) ($item->metrics['profit_margin'] ?? 0), 2),
                        'transaction_count' => (int) ($item->metrics['transaction_count'] ?? 0),
                    ],
                    'period_data' => $this->normalizePeriods($item->period_data ?? [], [
                        'sales',
                        'profit',
                        'profit_margin',
                        'transaction_count',
                    ]),
                    'analysis_result' => [
                        'profit_status' => $item->analysis_result['profit_status'] ?? 'profit',
                        'summary' => $item->analysis_result['summary'] ?? '',
                    ],
                ];
            })
        )->values()->all();
    }

    private function transformDiscountInsights(Collection $insights): array
    {
        return $this->sortByCategorySubcategory(
            $insights->map(function (Insight $item) {
                return [
                    'category' => $item->dimensions['category'] ?? '-',
                    'sub_category' => $item->dimensions['sub_category'] ?? '-',
                    'metrics' => [
                        'avg_discount' => round((float) ($item->metrics['avg_discount'] ?? 0), 4),
                        'avg_profit_margin' => round((float) ($item->metrics['avg_profit_margin'] ?? 0), 2),
                        'transaction_count' => (int) ($item->metrics['transaction_count'] ?? 0),
                    ],
                    'period_data' => $this->normalizePeriods($item->period_data ?? [], [
                        'avg_discount',
                        'profit',
                        'profit_margin',
                        'avg_sales',
                        'transaction_count',
                    ]),
                    'analysis_result' => [
                        'effectiveness' => $item->analysis_result['effectiveness'] ?? 'positive',
                        'discount_margin_correlation' => round((float) ($item->analysis_result['discount_margin_correlation'] ?? 0), 4),
                        'negative_period_ratio' => round((float) ($item->analysis_result['negative_period_ratio'] ?? 0), 4),
                        'summary' => $item->analysis_result['summary'] ?? '',
                    ],
                ];
            })
        )->values()->all();
    }

    private function transformTrendInsights(Collection $insights): array
    {
        return $this->sortByCategoryRegion(
            $insights->map(function (Insight $item) {
                    $periodData = $this->normalizePeriods($item->period_data ?? [], [
                        'sales',
                        'transaction_count',
                        'avg_sales',
                    ]);

                    // Hitung yearly sales untuk membandingkan tahun terakhir dan tahun sebelumnya
                    $yearlySales = [];
                    foreach ($periodData as $p) {
                        $y = $p['year'];
                        if (!isset($yearlySales[$y])) {
                            $yearlySales[$y] = 0;
                        }
                        $yearlySales[$y] += $p['sales'];
                    }
                    ksort($yearlySales);
                    $years = array_keys($yearlySales);
                    $yearCount = count($years);
                    
                    $lastYearGrowth = 0;
                    $lastYearBasis = '';
                    if ($yearCount >= 2) {
                        $lastY = $years[$yearCount - 1];
                        $prevY = $years[$yearCount - 2];
                        $lastS = $yearlySales[$lastY];
                        $prevS = $yearlySales[$prevY];
                        if ($prevS > 0) {
                            $lastYearGrowth = (($lastS - $prevS) / $prevS) * 100;
                            $lastYearBasis = "{$prevY} vs {$lastY}";
                        }
                    }

                    return [
                        'category' => $item->dimensions['category'] ?? '-',
                        'region' => $item->dimensions['region'] ?? '-',
                        'metrics' => [
                            'total_sales' => round((float) ($item->metrics['total_sales'] ?? 0), 2),
                            'transaction_count' => (int) ($item->metrics['transaction_count'] ?? 0),
                            'avg_monthly_sales' => round((float) ($item->metrics['avg_monthly_sales'] ?? 0), 2),
                        ],
                        'period_data' => $periodData,
                        'analysis_result' => [
                            'growth_rate' => round((float) ($item->analysis_result['growth_rate'] ?? 0), 2),
                            'last_year_growth' => round($lastYearGrowth, 2),
                            'last_year_basis' => $lastYearBasis,
                            'sales_trend' => $item->analysis_result['sales_trend'] ?? 'stable',
                            'comparison_basis' => $item->analysis_result['comparison_basis'] ?? '',
                            'summary' => $item->analysis_result['summary'] ?? '',
                        ],
                    ];
            })
        )->values()->all();
    }

    private function buildMeta(Collection $insights): array
    {
        $periods = $insights->flatMap(function (Insight $item) {
            return collect($item->period_data ?? [])->map(function (array $period) {
                return [
                    'year' => (int) ($period['year'] ?? 0),
                    'month' => (int) ($period['month'] ?? 0),
                ];
            });
        })->filter(fn (array $period) => $period['year'] > 0 && $period['month'] > 0);

        $monthKeys = $periods
            ->map(fn (array $period) => $this->makePeriodKey($period['year'], $period['month']))
            ->unique()
            ->sort()
            ->values();

        $yearMonthCoverage = [];
        foreach ($periods as $period) {
            $yearMonthCoverage[$period['year']][$period['month']] = true;
        }

        $partialYears = collect($yearMonthCoverage)
            ->map(function (array $months, string $year) {
                return [
                    'year' => (int) $year,
                    'month_count' => count($months),
                ];
            })
            ->filter(fn (array $item) => $item['month_count'] < 12)
            ->sortBy('year')
            ->values()
            ->all();

        return [
            'categories' => $this->sortValues(
                $insights->pluck('dimensions.category')->filter()->unique()->all(),
                self::CATEGORY_ORDER
            ),
            'regions' => $this->sortValues(
                $insights->pluck('dimensions.region')->filter()->unique()->all(),
                self::REGION_ORDER
            ),
            'sub_categories' => $this->sortValues(
                $insights->pluck('dimensions.sub_category')->filter()->unique()->all(),
                []
            ),
            'years' => $periods->pluck('year')->unique()->sort()->values()->all(),
            'date_range' => [
                'start' => $monthKeys->first(),
                'end' => $monthKeys->last(),
            ],
            'partial_years' => $partialYears,
        ];
    }

    private function normalizePeriods(array $periods, array $valueFields): array
    {
        return collect($periods)
            ->map(function (array $period) use ($valueFields) {
                $normalized = [
                    'year' => (int) ($period['year'] ?? 0),
                    'month' => (int) ($period['month'] ?? 0),
                    'quarter' => $this->makeQuarter((int) ($period['month'] ?? 0)),
                    'period_key' => $this->makePeriodKey(
                        (int) ($period['year'] ?? 0),
                        (int) ($period['month'] ?? 0)
                    ),
                    'label' => $this->formatPeriodLabel(
                        (int) ($period['year'] ?? 0),
                        (int) ($period['month'] ?? 0)
                    ),
                ];

                foreach ($valueFields as $field) {
                    $normalized[$field] = $field === 'transaction_count'
                        ? (int) ($period[$field] ?? 0)
                        : round((float) ($period[$field] ?? 0), 4);
                }

                return $normalized;
            })
            ->sortBy('period_key')
            ->values()
            ->all();
    }

    private function sortByCategoryRegion(Collection $items): Collection
    {
        return $items->sortBy(function (array $item) {
            return sprintf(
                '%03d-%03d-%s-%s',
                $this->rankValue($item['category'] ?? '', self::CATEGORY_ORDER),
                $this->rankValue($item['region'] ?? '', self::REGION_ORDER),
                $item['category'] ?? '',
                $item['region'] ?? ''
            );
        });
    }

    private function sortByCategorySubcategory(Collection $items): Collection
    {
        return $items->sortBy(function (array $item) {
            return sprintf(
                '%03d-%s',
                $this->rankValue($item['category'] ?? '', self::CATEGORY_ORDER),
                $item['sub_category'] ?? ''
            );
        });
    }

    private function sortValues(array $values, array $preferredOrder): array
    {
        $items = collect($values)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values();

        if ($preferredOrder === []) {
            return $items->sort()->values()->all();
        }

        return $items
            ->sortBy(fn ($value) => sprintf('%03d-%s', $this->rankValue((string) $value, $preferredOrder), $value))
            ->values()
            ->all();
    }

    private function rankValue(string $value, array $preferredOrder): int
    {
        $index = array_search($value, $preferredOrder, true);

        return $index === false ? 999 : $index;
    }

    private function makeQuarter(int $month): string
    {
        if ($month < 1) {
            return 'Q0';
        }

        return 'Q' . (int) ceil($month / 3);
    }

    private function makePeriodKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    private function formatPeriodLabel(int $year, int $month): string
    {
        $monthNames = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        if ($year === 0 || $month === 0) {
            return '-';
        }

        return ($monthNames[$month] ?? 'Mon') . ' ' . $year;
    }
}
