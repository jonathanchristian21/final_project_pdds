@extends('layouts.insights')

@section('title', 'Profitability')
@section('accent', '#0f766e')
@section('accent_secondary', '#1d4ed8')
@section('accent_soft', 'rgba(15, 118, 110, 0.14)')

@php
    $meta = $dashboardData['meta'];
    $series = $dashboardData['series'];
    $rangeLabel = ($meta['date_range']['start'] ?? null) && ($meta['date_range']['end'] ?? null)
        ? \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['start'])->format('M Y') . ' to ' . \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['end'])->format('M Y')
        : 'Date range unavailable';
    $partialYearLabel = count($meta['partial_years']) > 0
        ? implode(', ', array_map(fn ($item) => $item['year'] . ' (' . $item['month_count'] . ' months)', $meta['partial_years']))
        : 'No partial-year caveat';
@endphp

@section('hero')
    <section class="hero">
        <div class="hero-grid">
            <span class="hero-kicker">Analysis 1</span>
            <h1>Profitability</h1>
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
                    <label for="profitYearFilter">Year focus</label>
                    <select id="profitYearFilter">
                        <option value="all">All years</option>
                        @foreach($meta['years'] as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="profitStartMonth">Start month</label>
                    <input id="profitStartMonth" type="month" value="{{ $meta['date_range']['start'] }}">
                </div>

                <div class="field">
                    <label for="profitEndMonth">End month</label>
                    <input id="profitEndMonth" type="month" value="{{ $meta['date_range']['end'] }}">
                </div>

                <div class="button-row">
                    <button type="button" class="button button-primary" id="profitApplyButton">Apply window</button>
                    <button type="button" class="button button-secondary" id="profitResetButton">Reset</button>
                </div>
            </aside>

            <div class="dashboard-main">
                <section class="kpi-grid">
                    <article class="kpi-card">
                        <div class="kpi-label">Total sales</div>
                        <div class="kpi-value" id="profitTotalSales">-</div>
                        <div class="kpi-meta">Selected window</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Total profit</div>
                        <div class="kpi-value" id="profitTotalProfit">-</div>
                        <div class="kpi-meta">Selected window</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Profit margin</div>
                        <div class="kpi-value" id="profitMargin">-</div>
                        <div class="kpi-meta">Profit / Sales</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Status</div>
                        <div class="kpi-value" id="profitStatus">-</div>
                        <div class="kpi-meta" id="profitStatusMeta">-</div>
                    </article>
                </section>

                <section class="spotlight-card" id="profitSpotlight">
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">Waiting for the first render.</p>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Heatmap</h2>
                            </div>
                        </div>
                        <div class="heatmap-board" id="profitHeatmap"></div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-body">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Total Profit by Category and Region</h2>
                            </div>
                        </div>
                        <div class="chart-frame">
                            <canvas id="profitBarChart"></canvas>
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
                            <table class="data-table" id="profitTable">
                                <thead>
                                    <tr>
                                        <th class="is-sortable" data-key="category">Category</th>
                                        <th class="is-sortable" data-key="region">Region</th>
                                        <th class="numeric is-sortable" data-key="total_sales">Total Sales</th>
                                        <th class="numeric is-sortable" data-key="total_profit">Total Profit</th>
                                        <th class="numeric is-sortable" data-key="profit_margin">Profit Margin</th>
                                        <th class="is-sortable" data-key="status">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="profitTableBody"></tbody>
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
        const profitabilityMeta = @json($meta);
        const profitabilitySeries = @json($series);

        let profitBarChart = null;

        const profitabilityState = {
            start: profitabilityMeta.date_range.start,
            end: profitabilityMeta.date_range.end,
            sortKey: 'total_profit',
            sortDirection: 'desc',
        };

        const yearFilter = document.getElementById('profitYearFilter');
        const startMonthInput = document.getElementById('profitStartMonth');
        const endMonthInput = document.getElementById('profitEndMonth');

        document.addEventListener('DOMContentLoaded', () => {
            bindProfitabilityEvents();
            syncProfitInputsFromState();
            renderProfitabilityDashboard();
        });

        function bindProfitabilityEvents() {
            yearFilter.addEventListener('change', () => {
                if (yearFilter.value === 'all') {
                    startMonthInput.value = profitabilityMeta.date_range.start;
                    endMonthInput.value = profitabilityMeta.date_range.end;
                } else {
                    startMonthInput.value = `${yearFilter.value}-01`;
                    endMonthInput.value = `${yearFilter.value}-12`;
                }
            });

            document.getElementById('profitApplyButton').addEventListener('click', () => {
                const nextStart = startMonthInput.value || profitabilityMeta.date_range.start;
                let nextEnd = endMonthInput.value || profitabilityMeta.date_range.end;

                if (nextStart > nextEnd) {
                    nextEnd = nextStart;
                }

                profitabilityState.start = nextStart;
                profitabilityState.end = nextEnd;
                syncProfitInputsFromState();
                renderProfitabilityDashboard();
            });

            document.getElementById('profitResetButton').addEventListener('click', () => {
                profitabilityState.start = profitabilityMeta.date_range.start;
                profitabilityState.end = profitabilityMeta.date_range.end;
                profitabilityState.sortKey = 'total_profit';
                profitabilityState.sortDirection = 'desc';
                yearFilter.value = 'all';
                syncProfitInputsFromState();
                renderProfitabilityDashboard();
            });

            document.querySelectorAll('#profitTable thead th.is-sortable').forEach((heading) => {
                heading.addEventListener('click', () => {
                    const key = heading.dataset.key;
                    profitabilityState.sortDirection = profitabilityState.sortKey === key && profitabilityState.sortDirection === 'desc'
                        ? 'asc'
                        : 'desc';
                    profitabilityState.sortKey = key;
                    renderProfitabilityTable(buildProfitabilityRows());
                });
            });
        }

        function syncProfitInputsFromState() {
            startMonthInput.value = profitabilityState.start;
            endMonthInput.value = profitabilityState.end;

            const stateYear = profitabilityState.start.slice(0, 4) === profitabilityState.end.slice(0, 4)
                && profitabilityState.start.endsWith('-01')
                && profitabilityState.end.endsWith('-12')
                ? profitabilityState.start.slice(0, 4)
                : 'all';

            yearFilter.value = profitabilityMeta.years.map(String).includes(stateYear) ? stateYear : 'all';
        }

        function buildProfitabilityRows() {
            return profitabilitySeries.map((item) => {
                const periods = item.period_data.filter((period) =>
                    dashboardUtils.isWithinRange(period.period_key, profitabilityState.start, profitabilityState.end)
                );

                const totalSales = periods.reduce((sum, period) => sum + Number(period.sales || 0), 0);
                const totalProfit = periods.reduce((sum, period) => sum + Number(period.profit || 0), 0);
                const transactionCount = periods.reduce((sum, period) => sum + Number(period.transaction_count || 0), 0);
                const profitMargin = totalSales === 0 ? 0 : (totalProfit / totalSales) * 100;
                const status = totalProfit > 0 ? 'Profit' : (totalProfit < 0 ? 'Loss' : 'Neutral');

                return {
                    category: item.category,
                    region: item.region,
                    total_sales: Number(totalSales.toFixed(2)),
                    total_profit: Number(totalProfit.toFixed(2)),
                    profit_margin: Number(profitMargin.toFixed(2)),
                    transaction_count: transactionCount,
                    status,
                    has_data: periods.length > 0,
                };
            });
        }

        function renderProfitabilityDashboard() {
            const rows = buildProfitabilityRows();

            renderProfitabilityKPIs(rows);
            renderProfitabilitySpotlight(rows);
            renderProfitabilityHeatmap(rows);
            renderProfitabilityBarChart(rows);
            renderProfitabilityTable(rows);
        }

        function renderProfitabilityKPIs(rows) {
            const activeRows = rows.filter((row) => row.has_data);
            const totalSales = activeRows.reduce((sum, row) => sum + row.total_sales, 0);
            const totalProfit = activeRows.reduce((sum, row) => sum + row.total_profit, 0);
            const margin = totalSales === 0 ? 0 : (totalProfit / totalSales) * 100;
            const status = totalProfit > 0 ? 'Profit' : (totalProfit < 0 ? 'Loss' : 'Balanced');
            const tone = totalProfit > 0 ? 'text-success' : (totalProfit < 0 ? 'text-danger' : '');

            document.getElementById('profitTotalSales').textContent = dashboardUtils.formatCurrency(totalSales);
            document.getElementById('profitTotalProfit').textContent = dashboardUtils.formatCurrency(totalProfit);
            document.getElementById('profitMargin').textContent = dashboardUtils.formatPercent(margin);
            document.getElementById('profitStatus').textContent = status;
            document.getElementById('profitTotalProfit').className = `kpi-value ${tone}`.trim();
            document.getElementById('profitMargin').className = `kpi-value ${tone}`.trim();
            document.getElementById('profitStatus').className = `kpi-value ${tone}`.trim();
            document.getElementById('profitStatusMeta').textContent =
                `${activeRows.length} visible combinations between ${dashboardUtils.monthKeyToLabel(profitabilityState.start)} and ${dashboardUtils.monthKeyToLabel(profitabilityState.end)}.`;
        }

        function renderProfitabilitySpotlight(rows) {
            const activeRows = rows.filter((row) => row.has_data).sort((left, right) => right.profit_margin - left.profit_margin);
            const best = activeRows[0];
            const worst = [...activeRows].sort((left, right) => left.profit_margin - right.profit_margin)[0];
            const spotlight = document.getElementById('profitSpotlight');

            if (!best || !worst) {
                spotlight.innerHTML = `
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p class="spotlight-copy">No profitability rows are available for the current filter window.</p>
                `;
                return;
            }

            spotlight.innerHTML = `
                <span class="spotlight-tag">Insight spotlight</span>
                <p class="spotlight-copy">
                    <strong>${best.category} in ${best.region}</strong> is the strongest visible combination with a
                    <strong>${dashboardUtils.formatPercent(best.profit_margin)}</strong> margin and
                    <strong>${dashboardUtils.formatCurrency(best.total_profit)}</strong> profit.
                </p>
                <p class="spotlight-copy">
                    The weakest cell is <strong>${worst.category} in ${worst.region}</strong>, currently at
                    <strong>${dashboardUtils.formatPercent(worst.profit_margin)}</strong> margin and
                    <strong>${dashboardUtils.formatCurrency(worst.total_profit)}</strong> profit.
                </p>
            `;
        }

        function renderProfitabilityHeatmap(rows) {
            const grid = document.getElementById('profitHeatmap');
            const regions = profitabilityMeta.regions;
            const categories = profitabilityMeta.categories;
            const lookup = new Map(rows.map((row) => [`${row.category}__${row.region}`, row]));

            const fragments = [];
            fragments.push('<div class="heatmap-grid">');
            fragments.push('<div class="heatmap-axis head">Category</div>');

            regions.forEach((region) => {
                fragments.push(`<div class="heatmap-axis head">${region}</div>`);
            });

            categories.forEach((category) => {
                fragments.push(`<div class="heatmap-axis">${category}</div>`);

                regions.forEach((region) => {
                    const row = lookup.get(`${category}__${region}`) || {
                        total_sales: 0,
                        total_profit: 0,
                        profit_margin: 0,
                        status: 'Neutral',
                        has_data: false,
                    };

                    const background = row.has_data ? profitabilityColor(row.profit_margin) : 'rgba(255, 255, 255, 0.68)';
                    const statusClass = row.total_profit > 0 ? 'success' : (row.total_profit < 0 ? 'danger' : 'warning');

                    fragments.push(`
                        <div class="heatmap-cell" style="background: ${background}">
                            <strong>${dashboardUtils.formatPercent(row.profit_margin)}</strong>
                            <span>${dashboardUtils.formatCurrency(row.total_profit)} profit</span>
                            <span class="status-pill ${statusClass}">${row.status}</span>
                        </div>
                    `);
                });
            });

            fragments.push('</div>');
            grid.innerHTML = fragments.join('');
        }

        function profitabilityColor(margin) {
            const clamped = Math.max(-15, Math.min(15, margin));

            if (clamped > 0) {
                const opacity = 0.18 + (clamped / 15) * 0.58;
                return `rgba(31, 122, 77, ${opacity})`;
            }

            if (clamped < 0) {
                const opacity = 0.18 + (Math.abs(clamped) / 15) * 0.58;
                return `rgba(177, 65, 52, ${opacity})`;
            }

            return 'rgba(255, 255, 255, 0.74)';
        }

        function renderProfitabilityBarChart(rows) {
            const categories = profitabilityMeta.categories;
            const regions = profitabilityMeta.regions;

            dashboardUtils.destroyChart(profitBarChart);

            profitBarChart = new Chart(document.getElementById('profitBarChart'), {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: regions.map((region, index) => ({
                        label: region,
                        data: categories.map((category) => {
                            const row = rows.find((entry) => entry.category === category && entry.region === region);
                            return row ? row.total_profit : 0;
                        }),
                        backgroundColor: dashboardUtils.pickColor(index),
                        borderRadius: 10,
                        maxBarThickness: 28,
                    })),
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

        function renderProfitabilityTable(rows) {
            const tbody = document.getElementById('profitTableBody');
            const sortableRows = rows.filter((row) => row.has_data);
            const sortedRows = dashboardUtils.sortRows(sortableRows, profitabilityState.sortKey, profitabilityState.sortDirection);

            if (!sortedRows.length) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <strong>No profitability records match the current filter window.</strong>
                                <span>Try widening the date range or resetting to all years.</span>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = sortedRows.map((row) => {
                const statusClass = row.total_profit > 0 ? 'success' : (row.total_profit < 0 ? 'danger' : 'warning');
                const toneClass = row.total_profit > 0 ? 'text-success' : (row.total_profit < 0 ? 'text-danger' : '');

                return `
                    <tr>
                        <td><strong>${row.category}</strong></td>
                        <td>${row.region}</td>
                        <td class="numeric">${dashboardUtils.formatCurrency(row.total_sales)}</td>
                        <td class="numeric ${toneClass}">${dashboardUtils.formatCurrency(row.total_profit)}</td>
                        <td class="numeric">${dashboardUtils.formatPercent(row.profit_margin)}</td>
                        <td><span class="status-pill ${statusClass}">${row.status}</span></td>
                    </tr>
                `;
            }).join('');
        }
    </script>
@endsection
