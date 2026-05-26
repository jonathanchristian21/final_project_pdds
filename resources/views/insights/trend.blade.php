@extends('layouts.insights')

@section('title', 'Sales Trend')
@section('accent', '#1d4ed8')
@section('accent_secondary', '#0f766e')
@section('accent_soft', 'rgba(29, 78, 216, 0.14)')

@php
    $meta = $dashboardData['meta'];
    $series = $dashboardData['series'];
    $rangeLabel = ($meta['date_range']['start'] ?? null) && ($meta['date_range']['end'] ?? null)
        ? \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['start'])->format('M Y') . ' to ' . \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['end'])->format('M Y')
        : 'Date range unavailable';
    $partialYearLabel = count($meta['partial_years']) > 0
        ? implode(', ', array_map(fn ($item) => $item['year'] . ' (' . $item['month_count'] . ' months)', $meta['partial_years']))
        : 'No partial years detected';
@endphp

@section('hero')
    <section class="hero">
        <div class="hero-grid">
            <span class="hero-kicker">Analysis 3</span>
            <h1>Sales Trend</h1>
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
                    <label for="trendCategoryFilter">Category</label>
                    <select id="trendCategoryFilter">
                        <option value="all">All categories</option>
                        @foreach($meta['categories'] as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="trendRegionFilter">Region</label>
                    <select id="trendRegionFilter">
                        <option value="all">All regions</option>
                        @foreach($meta['regions'] as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="trendCompareBy">Line grouping</label>
                    <select id="trendCompareBy">
                        <option value="category">Compare categories</option>
                        <option value="region">Compare regions</option>
                    </select>
                    <span class="muted" id="trendCompareHint" style="font-size: 0.82rem; line-height: 1.45;">
                        Available when category and region are both left on All.
                    </span>
                </div>

                <div class="field">
                    <label for="trendGranularity">Time granularity</label>
                    <select id="trendGranularity">
                        <option value="month">Monthly</option>
                        <option value="quarter">Quarterly</option>
                        <option value="year">Yearly</option>
                    </select>
                </div>

                <div class="field">
                    <label for="trendStartPeriod">Start period</label>
                    <select id="trendStartPeriod">
                        <option value="all">From beginning</option>
                    </select>
                </div>

                <div class="field">
                    <label for="trendEndPeriod">End period</label>
                    <select id="trendEndPeriod">
                        <option value="all">Until end</option>
                    </select>
                </div>

                <div class="button-row">
                    <button type="button" class="button button-primary" id="trendApplyButton">Apply filters</button>
                    <button type="button" class="button button-secondary" id="trendResetButton">Reset view</button>
                </div>

            </aside>

            <div class="dashboard-main">
                <section class="kpi-grid">
                    <article class="kpi-card">
                        <div class="kpi-label">Total sales</div>
                        <div class="kpi-value" id="trendTotalSales">-</div>
                        <div class="kpi-meta">Selected scope</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label" id="trendGrowthLabel">Growth rate</div>
                        <div class="kpi-value" id="trendGrowthRate">-</div>
                        <div class="kpi-meta" id="trendGrowthMeta">-</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Trend status</div>
                        <div class="kpi-value" id="trendStatus">-</div>
                        <div class="kpi-meta">Latest direction</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Series tracked</div>
                        <div class="kpi-value" id="trendSeriesCount">-</div>
                        <div class="kpi-meta">Visible lines</div>
                    </article>
                </section>

                <section class="spotlight-card" id="trendSpotlight">
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">Waiting for the first render.</p>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Sales Trajectory</h2>
                            </div>
                        </div>
                        <div class="chart-frame" id="trendLineWrap">
                            <canvas id="trendLineChart"></canvas>
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
                            <table class="data-table" id="trendTable">
                                <thead>
                                    <tr>
                                        <th class="is-sortable" data-key="category">Category</th>
                                        <th class="is-sortable" data-key="region">Region</th>
                                        <th class="numeric is-sortable" data-key="total_sales">Total Sales</th>
                                        <th class="numeric is-sortable" data-key="growth_rate">Growth Rate</th>
                                        <th class="is-sortable" data-key="status">Trend</th>
                                    </tr>
                                </thead>
                                <tbody id="trendTableBody"></tbody>
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
        const trendMeta = @json($meta);
        const trendSeries = @json($series);

        let trendLineChart = null;

        const trendState = {
            category: 'all',
            region: 'all',
            compareBy: 'category',
            granularity: 'month',
            startPeriod: 'all',
            endPeriod: 'all',
            sortKey: 'growth_rate',
            sortDirection: 'desc',
        };

        const trendDraftState = {
            category: 'all',
            region: 'all',
            compareBy: 'category',
            granularity: 'month',
            startPeriod: 'all',
            endPeriod: 'all',
        };

        document.addEventListener('DOMContentLoaded', () => {
            bindTrendEvents();
            syncTrendControlsFromDraft();
            refreshTrendCompareControl();
            populatePeriodFilters();
            renderTrendDashboard();
        });

        function bindTrendEvents() {
            document.getElementById('trendCategoryFilter').addEventListener('change', (event) => {
                trendDraftState.category = event.target.value;
                refreshTrendCompareControl();
            });

            document.getElementById('trendRegionFilter').addEventListener('change', (event) => {
                trendDraftState.region = event.target.value;
                refreshTrendCompareControl();
            });

            document.getElementById('trendCompareBy').addEventListener('change', (event) => {
                trendDraftState.compareBy = event.target.value;
            });

            document.getElementById('trendGranularity').addEventListener('change', (event) => {
                trendDraftState.granularity = event.target.value;
                populatePeriodFilters();
                trendDraftState.startPeriod = 'all';
                trendDraftState.endPeriod = 'all';
                document.getElementById('trendStartPeriod').value = 'all';
                document.getElementById('trendEndPeriod').value = 'all';
            });

            document.getElementById('trendStartPeriod').addEventListener('change', (event) => {
                trendDraftState.startPeriod = event.target.value;
            });

            document.getElementById('trendEndPeriod').addEventListener('change', (event) => {
                trendDraftState.endPeriod = event.target.value;
            });

            document.getElementById('trendApplyButton').addEventListener('click', () => {
                trendState.category = trendDraftState.category;
                trendState.region = trendDraftState.region;
                trendState.compareBy = trendDraftState.compareBy;
                trendState.granularity = trendDraftState.granularity;
                trendState.startPeriod = trendDraftState.startPeriod;
                trendState.endPeriod = trendDraftState.endPeriod;
                syncTrendControlsFromState();
                renderTrendDashboard();
            });

            document.getElementById('trendResetButton').addEventListener('click', () => {
                trendState.sortKey = 'growth_rate';
                trendState.sortDirection = 'desc';
                trendState.category = 'all';
                trendState.region = 'all';
                trendState.compareBy = 'category';
                trendState.granularity = 'month';
                trendState.startPeriod = 'all';
                trendState.endPeriod = 'all';
                trendDraftState.category = 'all';
                trendDraftState.region = 'all';
                trendDraftState.compareBy = 'category';
                trendDraftState.granularity = 'month';
                trendDraftState.startPeriod = 'all';
                trendDraftState.endPeriod = 'all';
                syncTrendControlsFromState();
                populatePeriodFilters();
                renderTrendDashboard();
            });

            document.querySelectorAll('#trendTable thead th.is-sortable').forEach((heading) => {
                heading.addEventListener('click', () => {
                    const key = heading.dataset.key;
                    trendState.sortDirection = trendState.sortKey === key && trendState.sortDirection === 'desc'
                        ? 'asc'
                        : 'desc';
                    trendState.sortKey = key;
                    renderTrendTable(getTrendRows());
                });
            });
        }

        function syncTrendControlsFromDraft() {
            document.getElementById('trendCategoryFilter').value = trendDraftState.category;
            document.getElementById('trendRegionFilter').value = trendDraftState.region;
            document.getElementById('trendCompareBy').value = trendDraftState.compareBy;
            document.getElementById('trendGranularity').value = trendDraftState.granularity;
            document.getElementById('trendStartPeriod').value = trendDraftState.startPeriod;
            document.getElementById('trendEndPeriod').value = trendDraftState.endPeriod;
        }

        function syncTrendControlsFromState() {
            trendDraftState.category = trendState.category;
            trendDraftState.region = trendState.region;
            trendDraftState.compareBy = trendState.compareBy;
            trendDraftState.granularity = trendState.granularity;
            trendDraftState.startPeriod = trendState.startPeriod;
            trendDraftState.endPeriod = trendState.endPeriod;
            syncTrendControlsFromDraft();
            refreshTrendCompareControl();
        }

        function refreshTrendCompareControl() {
            const compareSelect = document.getElementById('trendCompareBy');
            const compareHint = document.getElementById('trendCompareHint');
            const category = trendDraftState.category;
            const region = trendDraftState.region;

            compareSelect.disabled = false;

            if (category !== 'all' && region === 'all') {
                trendDraftState.compareBy = 'region';
                compareSelect.value = 'region';
                compareSelect.disabled = true;
                compareHint.textContent = 'Category is locked, so the chart now compares regions automatically.';
                return;
            }

            if (region !== 'all' && category === 'all') {
                trendDraftState.compareBy = 'category';
                compareSelect.value = 'category';
                compareSelect.disabled = true;
                compareHint.textContent = 'Region is locked, so the chart now compares categories automatically.';
                return;
            }

            if (region !== 'all' && category !== 'all') {
                compareSelect.disabled = true;
                compareHint.textContent = 'Both filters are locked, so the chart shows the exact category-region slice.';
                return;
            }

            compareSelect.value = trendDraftState.compareBy;
            compareHint.textContent = 'Available when category and region are both left on All.';
        }

        function populatePeriodFilters() {
            const granularity = trendDraftState.granularity;
            const allPeriods = new Set();

            trendSeries.forEach((item) => {
                item.period_data.forEach((period) => {
                    allPeriods.add(trendBucket(period, granularity));
                });
            });

            const sortedPeriods = Array.from(allPeriods).sort(trendBucketSorter);

            const startSelect = document.getElementById('trendStartPeriod');
            const endSelect = document.getElementById('trendEndPeriod');

            startSelect.innerHTML = '<option value="all">From beginning</option>' +
                sortedPeriods.map(p => `<option value="${p}">${dashboardUtils.bucketLabel(p, granularity)}</option>`).join('');

            endSelect.innerHTML = '<option value="all">Until end</option>' +
                sortedPeriods.map(p => `<option value="${p}">${dashboardUtils.bucketLabel(p, granularity)}</option>`).join('');
        }

        function getTrendRows() {
            return trendSeries
                .filter((item) => (trendState.category === 'all' || item.category === trendState.category))
                .filter((item) => (trendState.region === 'all' || item.region === trendState.region))
                .map((item) => {
                    const filteredPeriods = filterPeriodsByRange(item.period_data, trendState.granularity, trendState.startPeriod, trendState.endPeriod);
                    const growthSnapshot = computeGrowthFromPeriods(filteredPeriods, trendState.granularity);

                    return {
                        category: item.category,
                        region: item.region,
                        total_sales: filteredPeriods.reduce((sum, period) => sum + Number(period.sales || 0), 0),
                        growth_rate: Number(growthSnapshot.rate.toFixed(2)),
                        status: growthSnapshot.status,
                        period_data: filteredPeriods,
                    };
                });
        }

        function filterPeriodsByRange(periods, granularity, startPeriod, endPeriod) {
            if (startPeriod === 'all' && endPeriod === 'all') {
                return periods;
            }

            return periods.filter((period) => {
                const bucket = trendBucket(period, granularity);
                const afterStart = startPeriod === 'all' || trendBucketSorter(bucket, startPeriod) >= 0;
                const beforeEnd = endPeriod === 'all' || trendBucketSorter(bucket, endPeriod) <= 0;
                return afterStart && beforeEnd;
            });
        }

        function renderTrendDashboard() {
            const rows = getTrendRows();
            const groupedSeries = buildTrendLineSeries(rows);

            renderTrendKPIs(rows, groupedSeries);
            renderTrendSpotlight(rows, groupedSeries.mode);
            renderTrendChart(groupedSeries);
            renderTrendTable(rows);
        }

        function renderTrendKPIs(rows, groupedSeries) {
            const totalSales = rows.reduce((sum, row) => sum + row.total_sales, 0);
            const overallGrowth = computeGrowthFromPeriods(
                rows.flatMap((row) => row.period_data),
                trendState.granularity
            );
            const tone = overallGrowth.rate > 0 ? 'text-success' : (overallGrowth.rate < 0 ? 'text-danger' : '');
            const growthLabel = trendState.granularity === 'month'
                ? 'Growth rate (MoM)'
                : (trendState.granularity === 'quarter' ? 'Growth rate (QoQ)' : 'Growth rate (YoY)');
            const statusLabel = overallGrowth.status === 'increasing'
                ? 'Increasing'
                : (overallGrowth.status === 'decreasing' ? 'Decreasing' : 'Stable');

            document.getElementById('trendTotalSales').textContent = dashboardUtils.formatCurrency(totalSales);
            document.getElementById('trendGrowthLabel').textContent = growthLabel;
            document.getElementById('trendGrowthRate').textContent = dashboardUtils.formatPercent(overallGrowth.rate);
            document.getElementById('trendStatus').textContent = statusLabel;
            document.getElementById('trendSeriesCount').textContent = dashboardUtils.formatNumber(groupedSeries.datasets.length);
            document.getElementById('trendGrowthRate').className = `kpi-value ${tone}`.trim();
            document.getElementById('trendStatus').className = `kpi-value ${tone}`.trim();
            document.getElementById('trendGrowthMeta').textContent = overallGrowth.message;
        }

        function renderTrendSpotlight(rows, mode) {
            const spotlight = document.getElementById('trendSpotlight');

            if (!rows.length) {
                spotlight.innerHTML = `
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">No trend rows are available for the active filter combination.</p>
                `;
                return;
            }

            const sortedRows = [...rows].sort((left, right) => right.growth_rate - left.growth_rate);
            const topRow = sortedRows[0];
            const weakRow = [...rows].sort((left, right) => left.growth_rate - right.growth_rate)[0];

            spotlight.innerHTML = `
                <span class="spotlight-tag">Insight spotlight</span>
                <p class="spotlight-copy">
                    Current comparison mode: <strong>${mode}</strong>. The strongest visible pairing is
                    <strong>${topRow.category} in ${topRow.region}</strong> with
                    <strong>${dashboardUtils.formatPercent(topRow.growth_rate)}</strong> latest growth.
                </p>
                <p class="spotlight-copy">
                    The weakest visible pairing is <strong>${weakRow.category} in ${weakRow.region}</strong>,
                    currently moving at <strong>${dashboardUtils.formatPercent(weakRow.growth_rate)}</strong>.
                </p>
            `;
        }

        function buildTrendLineSeries(rows) {
            const mode = resolveTrendMode();
            const grouped = {};

            rows.forEach((row) => {
                const key = mode === 'category'
                    ? row.category
                    : (mode === 'region' ? row.region : `${row.category} / ${row.region}`);

                if (!grouped[key]) {
                    grouped[key] = {};
                }

                row.period_data.forEach((period) => {
                    const bucket = trendBucket(period, trendState.granularity);

                    if (!grouped[key][bucket]) {
                        grouped[key][bucket] = {
                            sales: 0,
                        };
                    }

                    grouped[key][bucket].sales += Number(period.sales || 0);
                });
            });

            const periodKeys = Array.from(new Set(
                Object.values(grouped).flatMap((series) => Object.keys(series))
            )).sort(trendBucketSorter);

            return {
                mode,
                labels: periodKeys.map((key) => dashboardUtils.bucketLabel(key, trendState.granularity)),
                datasets: Object.keys(grouped).map((key, index) => ({
                    label: key,
                    data: periodKeys.map((periodKey) => Number((grouped[key][periodKey]?.sales || 0).toFixed(2))),
                    borderColor: dashboardUtils.pickColor(index),
                    backgroundColor: dashboardUtils.pickColor(index),
                    tension: 0.35,
                    pointRadius: 0,
                    borderWidth: 2.5,
                })),
            };
        }

        function renderTrendChart(groupedSeries) {
            const wrap = document.getElementById('trendLineWrap');
            dashboardUtils.destroyChart(trendLineChart);

            if (!groupedSeries.datasets.length) {
                wrap.innerHTML = `
                    <div class="empty-state">
                        <strong>No line series can be drawn for the active filters.</strong>
                        <span>Reset the filters to restore the full trend comparison.</span>
                    </div>
                `;
                return;
            }

            if (!wrap.querySelector('canvas')) {
                wrap.innerHTML = '<canvas id="trendLineChart"></canvas>';
            }

            trendLineChart = new Chart(document.getElementById('trendLineChart'), {
                type: 'line',
                data: {
                    labels: groupedSeries.labels,
                    datasets: groupedSeries.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                        },
                        y: {
                            ticks: {
                                callback: (value) => dashboardUtils.formatCurrency(value),
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.dataset.label}: ${dashboardUtils.formatCurrency(context.parsed.y)}`,
                            },
                        },
                    },
                },
            });
        }

        function renderTrendTable(rows) {
            const tbody = document.getElementById('trendTableBody');
            const sortedRows = dashboardUtils.sortRows(rows, trendState.sortKey, trendState.sortDirection);

            if (!sortedRows.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <strong>No detail rows match the active trend filters.</strong>
                                <span>Reset the view to recover the full category-region list.</span>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = sortedRows.map((row) => {
                const tone = row.growth_rate > 0 ? 'success' : (row.growth_rate < 0 ? 'danger' : 'warning');
                const toneText = row.growth_rate > 0 ? 'text-success' : (row.growth_rate < 0 ? 'text-danger' : '');
                const statusText = row.status === 'increasing' ? 'Increasing' : (row.status === 'decreasing' ? 'Decreasing' : 'Stable');

                return `
                    <tr>
                        <td><strong>${row.category}</strong></td>
                        <td>${row.region}</td>
                        <td class="numeric">${dashboardUtils.formatCurrency(row.total_sales)}</td>
                        <td class="numeric ${toneText}">${dashboardUtils.formatPercent(row.growth_rate)}</td>
                        <td><span class="status-pill ${tone}">${statusText}</span></td>
                    </tr>
                `;
            }).join('');
        }

        function resolveTrendMode() {
            if (trendState.category !== 'all' && trendState.region === 'all') {
                return 'region';
            }

            if (trendState.region !== 'all' && trendState.category === 'all') {
                return 'category';
            }

            if (trendState.category !== 'all' && trendState.region !== 'all') {
                return 'pair';
            }

            return trendState.compareBy;
        }

        function trendBucket(period, granularity) {
            if (granularity === 'month') {
                return period.period_key;
            }

            if (granularity === 'quarter') {
                return `${period.year}-${period.quarter}`;
            }

            return String(period.year);
        }

        function trendBucketSorter(left, right) {
            if (trendState.granularity === 'month') {
                return left.localeCompare(right);
            }

            if (trendState.granularity === 'year') {
                return Number(left) - Number(right);
            }

            const [leftYear, leftQuarter] = left.split('-Q').map(Number);
            const [rightYear, rightQuarter] = right.split('-Q').map(Number);

            if (leftYear !== rightYear) {
                return leftYear - rightYear;
            }

            return leftQuarter - rightQuarter;
        }

        function computeGrowthFromPeriods(periods, granularity) {
            if (!periods.length) {
                return {
                    rate: 0,
                    status: 'stable',
                    message: 'No periods are available for the active view.',
                };
            }

            const bucketMap = {};

            periods.forEach((period) => {
                const bucket = trendBucket(period, granularity);

                if (!bucketMap[bucket]) {
                    bucketMap[bucket] = {
                        value: 0,
                        months: new Set(),
                    };
                }

                bucketMap[bucket].value += Number(period.sales || 0);
                bucketMap[bucket].months.add(period.period_key);
            });

            const points = Object.keys(bucketMap)
                .sort(trendBucketSorter)
                .map((key) => ({
                    key,
                    value: bucketMap[key].value,
                    monthCount: bucketMap[key].months.size,
                }));

            let eligible = points;

            if (granularity === 'quarter') {
                const complete = points.filter((point) => point.monthCount === 3);
                if (complete.length >= 2) {
                    eligible = complete;
                }
            }

            if (granularity === 'year') {
                const complete = points.filter((point) => point.monthCount === 12);
                if (complete.length >= 2) {
                    eligible = complete;
                }
            }

            const comparisonPoints = eligible.length >= 2 ? eligible.slice(-2) : points.slice(-2);

            if (comparisonPoints.length < 2) {
                return {
                    rate: 0,
                    status: 'stable',
                    message: 'Not enough complete periods yet to compute a growth change.',
                };
            }

            const previous = comparisonPoints[0].value;
            const current = comparisonPoints[1].value;

            if (previous === 0) {
                return {
                    rate: 0,
                    status: current > 0 ? 'increasing' : 'stable',
                    message: `Growth compares ${comparisonPoints[0].key} and ${comparisonPoints[1].key}, but the earlier period starts from zero.`,
                };
            }

            const rate = ((current - previous) / previous) * 100;
            const status = rate > 0 ? 'increasing' : (rate < 0 ? 'decreasing' : 'stable');
            const cadence = granularity === 'month' ? 'MoM' : (granularity === 'quarter' ? 'QoQ' : 'YoY');

            return {
                rate,
                status,
                message: `${cadence} growth compares ${comparisonPoints[0].key} against ${comparisonPoints[1].key}.`,
            };
        }
    </script>
@endsection
