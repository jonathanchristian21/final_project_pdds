@extends('layouts.insights')

@section('title', 'Discount Effectiveness')
@section('accent', '#c2410c')
@section('accent_secondary', '#7c3aed')
@section('accent_soft', 'rgba(194, 65, 12, 0.14)')

@php
    $meta = $dashboardData['meta'];
    $series = $dashboardData['series'];
    $rangeLabel = ($meta['date_range']['start'] ?? null) && ($meta['date_range']['end'] ?? null)
        ? \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['start'])->format('M Y') . ' to ' . \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['end'])->format('M Y')
        : 'Date range unavailable';
@endphp

@section('hero')
    <section class="hero">
        <div class="hero-grid">
            <span class="hero-kicker">Analysis 2</span>
            <h1>Discount Effectiveness</h1>
            <div class="hero-meta">
                <span class="meta-chip">Coverage: {{ $rangeLabel }}</span>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <div class="stack">
        <section class="dashboard-grid">
            <aside class="filter-card">
                <h2>Filters</h2>

                <div class="field">
                    <label for="discountCategoryFilter">Category</label>
                    <select id="discountCategoryFilter">
                        <option value="all">All categories</option>
                        @foreach($meta['categories'] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="button-row">
                    <button type="button" class="button button-secondary" id="discountResetButton">Reset view</button>
                </div>

            </aside>

            <div class="dashboard-main">
                <section class="kpi-grid">
                    <article class="kpi-card">
                        <div class="kpi-label">Avg discount</div>
                        <div class="kpi-value" id="discountAvgDiscount">-</div>
                        <div class="kpi-meta">Weighted</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Avg profit margin</div>
                        <div class="kpi-value" id="discountAvgMargin">-</div>
                        <div class="kpi-meta">Selected scope</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Effectiveness status</div>
                        <div class="kpi-value" id="discountStatus">-</div>
                        <div class="kpi-meta" id="discountStatusMeta">-</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Sub-categories tracked</div>
                        <div class="kpi-value" id="discountSubcategories">-</div>
                        <div class="kpi-meta">Visible points</div>
                    </article>
                </section>

                <section class="spotlight-card" id="discountSpotlight">
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">Waiting for the first render.</p>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Discount vs Profit Margin</h2>
                            </div>
                        </div>
                        <div class="chart-frame" id="discountScatterWrap">
                            <canvas id="discountScatterChart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Trend Over Time</h2>
                            </div>
                        </div>
                        <div class="chart-frame" id="discountTrendWrap">
                            <canvas id="discountTrendChart"></canvas>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Detail Table</h2>
                            </div>
                        </div>
                        <div class="data-table-wrap">
                            <table class="data-table" id="discountTable">
                                <thead>
                                    <tr>
                                        <th class="is-sortable" data-key="category">Category</th>
                                        <th class="is-sortable" data-key="sub_category">Sub-category</th>
                                        <th class="numeric is-sortable" data-key="avg_discount">Avg Discount</th>
                                        <th class="numeric is-sortable" data-key="avg_profit_margin">Avg Profit Margin</th>
                                        <th class="is-sortable" data-key="effectiveness">Effectiveness</th>
                                    </tr>
                                </thead>
                                <tbody id="discountTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        const discountMeta = @json($meta);
        const discountSeries = @json($series);

        let discountScatterChart = null;
        let discountTrendChart = null;

        const discountState = {
            category: 'all',
            sortKey: 'avg_profit_margin',
            sortDirection: 'asc',
        };

        document.addEventListener('DOMContentLoaded', () => {
            bindDiscountEvents();
            renderDiscountDashboard();
        });

        function bindDiscountEvents() {
            document.getElementById('discountCategoryFilter').addEventListener('change', (event) => {
                discountState.category = event.target.value;
                renderDiscountDashboard();
            });

            document.getElementById('discountResetButton').addEventListener('click', () => {
                discountState.category = 'all';
                discountState.sortKey = 'avg_profit_margin';
                discountState.sortDirection = 'asc';
                document.getElementById('discountCategoryFilter').value = 'all';
                renderDiscountDashboard();
            });

            document.querySelectorAll('#discountTable thead th.is-sortable').forEach((heading) => {
                heading.addEventListener('click', () => {
                    const key = heading.dataset.key;
                    discountState.sortDirection = discountState.sortKey === key && discountState.sortDirection === 'desc'
                        ? 'asc'
                        : 'desc';
                    discountState.sortKey = key;
                    renderDiscountTable(getFilteredDiscountRows());
                });
            });
        }

        function getFilteredDiscountRows() {
            return discountSeries
                .filter((item) => discountState.category === 'all' || item.category === discountState.category)
                .map((item) => ({
                    category: item.category,
                    sub_category: item.sub_category,
                    avg_discount: Number(item.metrics.avg_discount || 0),
                    avg_profit_margin: Number(item.metrics.avg_profit_margin || 0),
                    transaction_count: Number(item.metrics.transaction_count || 0),
                    period_data: item.period_data,
                    effectiveness: item.analysis_result.effectiveness || 'positive',
                    correlation: Number(item.analysis_result.discount_margin_correlation || 0),
                }));
        }

        function renderDiscountDashboard() {
            const rows = getFilteredDiscountRows();

            renderDiscountKPIs(rows);
            renderDiscountSpotlight(rows);
            renderDiscountScatter(rows);
            renderDiscountTrend(rows);
            renderDiscountTable(rows);
        }

        function renderDiscountKPIs(rows) {
            const totalTransactions = rows.reduce((sum, row) => sum + row.transaction_count, 0);
            const weightedDiscount = totalTransactions === 0
                ? 0
                : rows.reduce((sum, row) => sum + (row.avg_discount * row.transaction_count), 0) / totalTransactions;
            const weightedMargin = totalTransactions === 0
                ? 0
                : rows.reduce((sum, row) => sum + (row.avg_profit_margin * row.transaction_count), 0) / totalTransactions;
            const negativeCount = rows.filter((row) => row.effectiveness === 'negative').length;
            const status = rows.length === 0
                ? 'No data'
                : (weightedMargin < 0 || negativeCount > rows.length / 2 ? 'Negative' : 'Positive');
            const tone = weightedMargin > 0 ? 'text-success' : (weightedMargin < 0 ? 'text-danger' : '');

            document.getElementById('discountAvgDiscount').textContent = dashboardUtils.formatPercent(weightedDiscount * 100);
            document.getElementById('discountAvgMargin').textContent = dashboardUtils.formatPercent(weightedMargin);
            document.getElementById('discountStatus').textContent = status;
            document.getElementById('discountSubcategories').textContent = dashboardUtils.formatNumber(rows.length);
            document.getElementById('discountAvgMargin').className = `kpi-value ${tone}`.trim();
            document.getElementById('discountStatus').className = `kpi-value ${status === 'Positive' ? 'text-success' : (status === 'Negative' ? 'text-danger' : '')}`.trim();
            document.getElementById('discountStatusMeta').textContent =
                rows.length === 0
                    ? 'No sub-category is available for the current filter.'
                    : `${negativeCount} of ${rows.length} visible sub-categories are currently flagged as negative.`;
        }

        function renderDiscountSpotlight(rows) {
            const spotlight = document.getElementById('discountSpotlight');

            if (!rows.length) {
                spotlight.innerHTML = `
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">No discount insight documents match the current category filter.</p>
                `;
                return;
            }

            const watchlist = [...rows].sort((left, right) => {
                if (left.avg_profit_margin !== right.avg_profit_margin) {
                    return left.avg_profit_margin - right.avg_profit_margin;
                }

                return right.avg_discount - left.avg_discount;
            })[0];

            const best = [...rows].sort((left, right) => right.avg_profit_margin - left.avg_profit_margin)[0];

            spotlight.innerHTML = `
                <span class="spotlight-tag">Insight spotlight</span>
                <p class="spotlight-copy">
                    <strong>${watchlist.sub_category}</strong> is the current watchlist leader with
                    <strong>${dashboardUtils.formatPercent(watchlist.avg_discount * 100)}</strong> average discount and
                    <strong>${dashboardUtils.formatPercent(watchlist.avg_profit_margin)}</strong> profit margin.
                </p>
                <p class="spotlight-copy">
                    The healthiest visible segment is <strong>${best.sub_category}</strong>, returning
                    <strong>${dashboardUtils.formatPercent(best.avg_profit_margin)}</strong> profit margin while carrying
                    <strong>${dashboardUtils.formatPercent(best.avg_discount * 100)}</strong> average discount.
                </p>
            `;
        }

        const categoryColors = {
            'Furniture': { bg: 'rgba(194, 65, 12, 0.72)', border: '#c2410c' },
            'Office Supplies': { bg: 'rgba(37, 99, 235, 0.72)', border: '#2563eb' },
            'Technology': { bg: 'rgba(124, 58, 237, 0.72)', border: '#7c3aed' },
        };

        function getCategoryColor(category) {
            return categoryColors[category] || { bg: 'rgba(100, 116, 139, 0.72)', border: '#64748b' };
        }

        function renderDiscountScatter(rows) {
            dashboardUtils.destroyChart(discountScatterChart);
            const scatterCanvasWrap = document.getElementById('discountScatterWrap');

            if (!rows.length) {
                scatterCanvasWrap.innerHTML = `
                    <div class="empty-state">
                        <strong>No scatter data is available for the active category.</strong>
                        <span>Reset the category filter to bring the full sub-category map back.</span>
                    </div>
                `;
                return;
            }

            if (!scatterCanvasWrap.querySelector('canvas')) {
                scatterCanvasWrap.innerHTML = '<canvas id="discountScatterChart"></canvas>';
            }

            const maxDiscount = Math.max(...rows.map((row) => row.avg_discount * 100), 20);

            const categoriesInData = [...new Set(rows.map(row => row.category))];
            const datasets = categoriesInData.map(category => {
                const categoryRows = rows.filter(row => row.category === category);
                const color = getCategoryColor(category);
                
                return {
                    label: category,
                    data: categoryRows.map((row) => ({
                        x: Number((row.avg_discount * 100).toFixed(2)),
                        y: row.avg_profit_margin,
                        subCategory: row.sub_category,
                        effectiveness: row.effectiveness,
                        transactionCount: row.transaction_count,
                    })),
                    backgroundColor: color.bg,
                    borderColor: color.border,
                    borderWidth: 2,
                    pointRadius: categoryRows.map((row) => Math.min(12, 5 + (row.transaction_count / 30))),
                    pointHoverRadius: categoryRows.map((row) => Math.min(15, 7 + (row.transaction_count / 24))),
                };
            });

            datasets.push(
                {
                    type: 'line',
                    label: 'Zero margin',
                    data: [
                        { x: 0, y: 0 },
                        { x: maxDiscount + 6, y: 0 },
                    ],
                    borderColor: '#64748b',
                    borderDash: [6, 6],
                    pointRadius: 0,
                    borderWidth: 1.5,
                }
            );

            discountScatterChart = new Chart(document.getElementById('discountScatterChart'), {
                type: 'scatter',
                data: {
                    datasets: datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'nearest',
                        intersect: true,
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Average discount (%)',
                            },
                            suggestedMin: 0,
                            suggestedMax: maxDiscount + 6,
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Avg profit margin (%)',
                            },
                            ticks: {
                                callback: (value) => dashboardUtils.formatPercent(value),
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    if (context.dataset.type === 'line') {
                                        return context.dataset.label;
                                    }

                                    const point = context.dataset.data[context.dataIndex];
                                    return [
                                        `${context.dataset.label} - ${point.subCategory}`,
                                        `Avg discount: ${dashboardUtils.formatPercent(point.x)}`,
                                        `Avg profit margin: ${dashboardUtils.formatPercent(point.y)}`,
                                        `Transactions: ${dashboardUtils.formatNumber(point.transactionCount)}`,
                                        `Effectiveness: ${point.effectiveness}`,
                                    ];
                                },
                            },
                        },
                    },
                },
            });
        }

        function renderDiscountTrend(rows) {
            dashboardUtils.destroyChart(discountTrendChart);
            const trendCanvasWrap = document.getElementById('discountTrendWrap');
            if (!trendCanvasWrap.querySelector('canvas')) {
                trendCanvasWrap.innerHTML = '<canvas id="discountTrendChart"></canvas>';
            }

            const periodMap = {};

            rows.forEach((row) => {
                row.period_data.forEach((period) => {
                    if (!periodMap[period.period_key]) {
                        periodMap[period.period_key] = {
                            discountWeighted: 0,
                            discountWeight: 0,
                            marginWeighted: 0,
                            marginWeight: 0,
                        };
                    }

                    periodMap[period.period_key].discountWeighted += Number(period.avg_discount || 0) * Number(period.transaction_count || 0);
                    periodMap[period.period_key].discountWeight += Number(period.transaction_count || 0);
                    periodMap[period.period_key].marginWeighted += Number(period.profit_margin || 0) * Number(period.transaction_count || 0);
                    periodMap[period.period_key].marginWeight += Number(period.transaction_count || 0);
                });
            });

            const periodKeys = Object.keys(periodMap).sort();

            if (!periodKeys.length) {
                trendCanvasWrap.innerHTML = `
                    <div class="empty-state">
                        <strong>No trend data is available for the active category.</strong>
                        <span>Try switching back to all categories.</span>
                    </div>
                `;
                return;
            }

            discountTrendChart = new Chart(document.getElementById('discountTrendChart'), {
                type: 'line',
                data: {
                    labels: periodKeys.map((key) => dashboardUtils.monthKeyToLabel(key)),
                    datasets: [
                        {
                            label: 'Avg discount (%)',
                            data: periodKeys.map((key) => {
                                const bucket = periodMap[key];
                                if (bucket.discountWeight === 0) {
                                    return 0;
                                }

                                return Number((((bucket.discountWeighted / bucket.discountWeight) || 0) * 100).toFixed(2));
                            }),
                            borderColor: '#c2410c',
                            backgroundColor: 'rgba(194, 65, 12, 0.14)',
                            yAxisID: 'discountAxis',
                            tension: 0.35,
                            pointRadius: 0,
                        },
                        {
                            label: 'Profit margin (%)',
                            data: periodKeys.map((key) => {
                                const bucket = periodMap[key];
                                if (bucket.marginWeight === 0) {
                                    return 0;
                                }

                                return Number(((bucket.marginWeighted / bucket.marginWeight) || 0).toFixed(2));
                            }),
                            borderColor: '#1f7a4d',
                            backgroundColor: 'rgba(31, 122, 77, 0.14)',
                            yAxisID: 'marginAxis',
                            tension: 0.35,
                            pointRadius: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        discountAxis: {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Average discount (%)',
                            },
                        },
                        marginAxis: {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Profit margin (%)',
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: (value) => dashboardUtils.formatPercent(value),
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${dashboardUtils.formatPercent(context.parsed.y)}`,
                            },
                        },
                    },
                },
            });
        }

        function renderDiscountTable(rows) {
            const tbody = document.getElementById('discountTableBody');
            const sortedRows = dashboardUtils.sortRows(rows, discountState.sortKey, discountState.sortDirection);

            if (!sortedRows.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>No discount rows match the current category filter.</strong>
                                <span>Reset the filter to recover the full sub-category list.</span>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = sortedRows.map((row) => {
                const effectClass = row.effectiveness === 'negative' ? 'danger' : 'success';
                const toneClass = row.avg_profit_margin > 0 ? 'text-success' : (row.avg_profit_margin < 0 ? 'text-danger' : '');

                return `
                    <tr>
                        <td><strong>${row.category}</strong></td>
                        <td>${row.sub_category}</td>
                        <td class="numeric">${dashboardUtils.formatPercent(row.avg_discount * 100)}</td>
                        <td class="numeric ${toneClass}">${dashboardUtils.formatPercent(row.avg_profit_margin)}</td>
                        <td><span class="status-pill ${effectClass}">${row.effectiveness}</span></td>
                    </tr>
                `;
            }).join('');
        }
    </script>
@endsection
