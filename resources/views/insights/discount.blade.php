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



@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1>Discount Effectiveness</h1>
            <p class="text-muted">Analyze the relationship between discounts, sales volume, and profit margins.</p>
        </div>
    </div>

    <div>
        <section class="dashboard-grid">
            <aside class="filter-sidebar card">
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

                <div class="flex gap-4" style="margin-top: 24px;">
                    <button type="button" class="button button-primary" id="discountApplyButton">Apply</button>
                    <button type="button" class="button button-secondary" id="discountResetButton">Reset</button>
                </div>
            </aside>

            <div class="dashboard-main">
                <section class="metric-grid">
                    <article class="metric-card">
                        <div class="metric-label">Avg discount</div>
                        <div class="metric-value" id="discountAvg">-</div>
                        <div class="metric-meta">Selected window</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Avg profit margin</div>
                        <div class="metric-value" id="discountMargin">-</div>
                        <div class="metric-meta">Selected window</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Sub-categories</div>
                        <div class="metric-value" id="discountCount">-</div>
                        <div class="metric-meta">In current view</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Negative impact</div>
                        <div class="metric-value text-danger" id="discountWarningCount">-</div>
                        <div class="metric-meta">Sub-cats with margin < 0</div>
                    </article>
                </section>

                <section class="spotlight" id="discountSpotlight">
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p>Waiting for the first render.</p>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Discount vs. Profit Margin</h2>
                    <p class="text-muted" style="margin-top:4px; font-size:0.82rem;">Bubble size represents the number of transactions.</p>
                    <div class="chart-container" id="discountScatterWrap">
                        <canvas id="discountScatterChart"></canvas>
                    </div>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Trend Over Time</h2>
                    <div class="chart-container" id="discountTrendWrap">
                        <canvas id="discountTrendChart"></canvas>
                    </div>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Detail Table</h2>
                    <div class="table-wrap">
                        <table class="data-table" id="discountTable">
                            <thead>
                                <tr>
                                    <th class="is-sortable" data-key="category">Category</th>
                                    <th class="is-sortable" data-key="sub_category">Sub-category</th>
                                    <th class="numeric is-sortable" data-key="avg_discount">Avg Discount</th>
                                    <th class="numeric is-sortable" data-key="avg_profit_margin">Avg Margin</th>
                                    <th class="numeric is-sortable" data-key="transaction_count">Transactions</th>
                                </tr>
                            </thead>
                            <tbody id="discountTableBody"></tbody>
                        </table>
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

        const discountDraftState = {
            category: 'all',
        };

        document.addEventListener('DOMContentLoaded', () => {
            bindDiscountEvents();
            syncDiscountControlsFromDraft();
            renderDiscountDashboard();
        });

        function bindDiscountEvents() {
            document.getElementById('discountCategoryFilter').addEventListener('change', (event) => {
                discountDraftState.category = event.target.value;
            });

            document.getElementById('discountApplyButton').addEventListener('click', () => {
                discountState.category = discountDraftState.category;
                renderDiscountDashboard();
            });

            document.getElementById('discountResetButton').addEventListener('click', () => {
                discountState.category = 'all';
                discountState.sortKey = 'avg_profit_margin';
                discountState.sortDirection = 'asc';
                discountDraftState.category = 'all';
                syncDiscountControlsFromDraft();
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

        function syncDiscountControlsFromDraft() {
            document.getElementById('discountCategoryFilter').value = discountDraftState.category;
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

            renderDiscountMetrics(rows);
            renderDiscountSpotlight(rows);
            renderDiscountScatter(rows);
            renderDiscountTrend(rows);
            renderDiscountTable(rows);
        }

        function renderDiscountMetrics(rows) {
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

            document.getElementById('discountAvg').textContent = dashboardUtils.formatPercent(weightedDiscount * 100);
            document.getElementById('discountMargin').textContent = dashboardUtils.formatPercent(weightedMargin);
            document.getElementById('discountCount').textContent = dashboardUtils.formatNumber(rows.length);
            document.getElementById('discountWarningCount').textContent = dashboardUtils.formatNumber(negativeCount);
            
            document.getElementById('discountMargin').className = `metric-value ${tone}`.trim();
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
                    pointRadius: categoryRows.map((row) => Math.max(4, Math.min(22, Math.sqrt(row.transaction_count) * 0.85))),
                    pointHoverRadius: categoryRows.map((row) => Math.max(6, Math.min(24, Math.sqrt(row.transaction_count) * 0.85 + 2))),
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
                            borderColor: '#ea580c',
                            backgroundColor: 'rgba(234, 88, 12, 0.10)',
                            yAxisID: 'discountAxis',
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#ea580c',
                            borderWidth: 2.5,
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
                            borderColor: '#16a34a',
                            backgroundColor: 'rgba(22, 163, 74, 0.10)',
                            yAxisID: 'marginAxis',
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#16a34a',
                            borderWidth: 2.5,
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
                        <td class="numeric">${dashboardUtils.formatNumber(row.transaction_count)}</td>
                    </tr>
                `;
            }).join('');
        }
    </script>
@endsection
