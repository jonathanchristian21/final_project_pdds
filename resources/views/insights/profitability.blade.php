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



@section('content')
    <style>
        /* ── Drilldown modal ─────────────────────────────── */
        .drilldown-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 220ms ease;
        }
        .drilldown-overlay.is-open {
            opacity: 1;
            pointer-events: all;
        }
        .drilldown-modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            box-shadow: 0 28px 72px rgba(15, 23, 42, 0.18);
            width: 100%;
            max-width: 780px;
            max-height: 88vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: translateY(18px) scale(0.98);
            transition: transform 220ms ease, opacity 220ms ease;
        }
        .drilldown-overlay.is-open .drilldown-modal {
            transform: translateY(0) scale(1);
        }
        .drilldown-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .drilldown-title-group {
            display: grid;
            gap: 2px;
        }
        .drilldown-eyebrow {
            font-size: 0.71rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
        }
        .drilldown-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.25;
        }
        .drilldown-level-switch {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }
        .level-btn {
            padding: 7px 14px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--muted);
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 150ms ease, color 150ms ease, border-color 150ms ease;
        }
        .level-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        .drilldown-close {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            line-height: 1;
            transition: background 150ms ease, color 150ms ease;
            flex-shrink: 0;
        }
        .drilldown-close:hover {
            background: var(--danger-soft);
            color: var(--danger);
            border-color: rgba(177, 65, 52, 0.2);
        }
        .drilldown-body {
            overflow-y: auto;
            padding: 18px 20px 20px;
            flex: 1;
        }
        .drilldown-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .drilldown-metric {
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            background: var(--surface-muted);
            border: 1px solid var(--border);
        }
        .drilldown-metric-label {
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .drilldown-metric-value {
            margin-top: 5px;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .drilldown-bar-list {
            display: grid;
            gap: 8px;
        }
        .drilldown-bar-item {
            display: grid;
            gap: 4px;
        }
        .drilldown-bar-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .drilldown-bar-name {
            font-weight: 600;
            font-size: 0.88rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        .drilldown-bar-numbers {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            flex-shrink: 0;
        }
        .drilldown-bar-track {
            height: 8px;
            border-radius: 999px;
            background: var(--border);
            overflow: hidden;
        }
        .drilldown-bar-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 400ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        .drilldown-loading {
            display: grid;
            place-items: center;
            min-height: 200px;
            color: var(--muted);
            gap: 10px;
            font-size: 0.9rem;
        }
        .drilldown-spinner {
            width: 28px;
            height: 28px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 700ms linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Heatmap clickable cells ─────────────────────── */
        .heatmap-cell {
            cursor: pointer;
            transition: background 140ms ease, box-shadow 140ms ease;
            position: relative;
        }
        .heatmap-cell:hover {
            box-shadow: inset 0 0 0 2px var(--accent);
            z-index: 2;
        }
        .heatmap-cell .drill-hint {
            font-size: 0.68rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--accent);
            opacity: 0;
            transition: opacity 140ms ease;
            margin-top: 2px;
        }
        .heatmap-cell:hover .drill-hint {
            opacity: 1;
        }
    </style>

    <!-- Drilldown Modal -->
    <div class="drilldown-overlay" id="drilldownOverlay" role="dialog" aria-modal="true" aria-label="Heatmap Drilldown">
        <div class="drilldown-modal" id="drilldownModal">
            <div class="drilldown-header">
                <div class="drilldown-title-group">
                    <span class="drilldown-eyebrow" id="drilldownEyebrow">Drilldown</span>
                    <h2 class="drilldown-title" id="drilldownTitle">—</h2>
                </div>
                <div class="drilldown-level-switch">
                    <button type="button" class="level-btn active" id="drilldownLevelState">By State</button>
                    <button type="button" class="level-btn" id="drilldownLevelSubcategory">By Sub-category</button>
                </div>
                <button type="button" class="drilldown-close" id="drilldownClose" aria-label="Close">✕</button>
            </div>
            <div class="drilldown-body" id="drilldownBody">
                <div class="drilldown-loading">
                    <div class="drilldown-spinner"></div>
                    <span>Loading data…</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1>Profitability Analysis</h1>
            <p class="text-muted">Analyze margins and total profit across categories and regions.</p>
        </div>
    </div>

    <div>
        <section class="dashboard-grid">
            <aside class="filter-sidebar card">
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

                <div class="flex gap-4" style="margin-top: 24px;">
                    <button type="button" class="button button-primary" id="profitApplyButton">Apply</button>
                    <button type="button" class="button button-secondary" id="profitResetButton">Reset</button>
                </div>
            </aside>

            <div class="dashboard-main">
                <section class="metric-grid">
                    <article class="metric-card">
                        <div class="metric-label">Total sales</div>
                        <div class="metric-value" id="profitTotalSales">-</div>
                        <div class="metric-meta">Selected window</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Total profit</div>
                        <div class="metric-value" id="profitTotalProfit">-</div>
                        <div class="metric-meta">Selected window</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Profit margin</div>
                        <div class="metric-value" id="profitMargin">-</div>
                        <div class="metric-meta">Profit / Sales</div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-label">Status</div>
                        <div class="metric-value" id="profitStatus">-</div>
                        <div class="metric-meta" id="profitStatusMeta">-</div>
                    </article>
                </section>

                <section class="spotlight" id="profitSpotlight">
                    <span class="spotlight-tag">Insight spotlight</span>
                    <p>Waiting for the first render.</p>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Heatmap</h2>
                    <p class="text-muted" style="margin-top:4px; font-size:0.82rem;">Click any cell to drill down by state or sub-category.</p>
                    <div class="heatmap-board" id="profitHeatmap"></div>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Total Profit by Category and Region</h2>
                    <div class="chart-container">
                        <canvas id="profitBarChart"></canvas>
                    </div>
                </section>

                <section class="card mb-8">
                    <h2 class="panel-title">Detail Table</h2>
                    <div class="table-wrap">
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
                </section>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        const profitabilityMeta   = @json($meta);
        const profitabilitySeries = @json($series);
        const drilldownApiUrl     = '{{ route('insights.profitability.drilldown') }}';

        let profitBarChart = null;

        const profitabilityState = {
            year:          'all',
            start:         profitabilityMeta.date_range.start,
            end:           profitabilityMeta.date_range.end,
            sortKey:       'total_profit',
            sortDirection: 'desc',
        };

        const profitabilityDraftState = {
            year:  'all',
            start: profitabilityMeta.date_range.start,
            end:   profitabilityMeta.date_range.end,
        };

        /* ── Drilldown state ──────────────────────────────── */
        const drilldownState = {
            category: null,
            region:   null,
            level:    'state',
            pending:  false,
        };

        const yearFilter       = document.getElementById('profitYearFilter');
        const startMonthInput  = document.getElementById('profitStartMonth');
        const endMonthInput    = document.getElementById('profitEndMonth');
        const overlay          = document.getElementById('drilldownOverlay');
        const drilldownBody    = document.getElementById('drilldownBody');
        const drilldownTitle   = document.getElementById('drilldownTitle');
        const drilldownEyebrow = document.getElementById('drilldownEyebrow');
        const btnState         = document.getElementById('drilldownLevelState');
        const btnSubcategory   = document.getElementById('drilldownLevelSubcategory');

        document.addEventListener('DOMContentLoaded', () => {
            bindProfitabilityEvents();
            bindDrilldownEvents();
            syncProfitControlsFromDraft();
            renderProfitabilityDashboard();
        });

        /* ══════════════════════════════════════════════════ */
        /*  Events                                            */
        /* ══════════════════════════════════════════════════ */
        function bindProfitabilityEvents() {
            yearFilter.addEventListener('change', () => {
                if (yearFilter.value === 'all') {
                    profitabilityDraftState.start = profitabilityMeta.date_range.start;
                    profitabilityDraftState.end   = profitabilityMeta.date_range.end;
                } else {
                    profitabilityDraftState.start = `${yearFilter.value}-01`;
                    profitabilityDraftState.end   = `${yearFilter.value}-12`;
                }
                profitabilityDraftState.year = yearFilter.value;
                syncProfitControlsFromDraft();
            });

            startMonthInput.addEventListener('change', () => {
                profitabilityDraftState.start = startMonthInput.value || profitabilityMeta.date_range.start;
            });

            endMonthInput.addEventListener('change', () => {
                profitabilityDraftState.end = endMonthInput.value || profitabilityMeta.date_range.end;
            });

            document.getElementById('profitApplyButton').addEventListener('click', () => {
                let nextStart = profitabilityDraftState.start;
                let nextEnd   = profitabilityDraftState.end;

                if (nextStart > nextEnd) nextEnd = nextStart;

                profitabilityState.year  = profitabilityDraftState.year;
                profitabilityState.start = nextStart;
                profitabilityState.end   = nextEnd;
                renderProfitabilityDashboard();
            });

            document.getElementById('profitResetButton').addEventListener('click', () => {
                profitabilityState.year          = 'all';
                profitabilityState.start         = profitabilityMeta.date_range.start;
                profitabilityState.end           = profitabilityMeta.date_range.end;
                profitabilityState.sortKey       = 'total_profit';
                profitabilityState.sortDirection = 'desc';
                profitabilityDraftState.year  = 'all';
                profitabilityDraftState.start = profitabilityMeta.date_range.start;
                profitabilityDraftState.end   = profitabilityMeta.date_range.end;
                syncProfitControlsFromDraft();
                renderProfitabilityDashboard();
            });

            document.querySelectorAll('#profitTable thead th.is-sortable').forEach((heading) => {
                heading.addEventListener('click', () => {
                    const key = heading.dataset.key;
                    profitabilityState.sortDirection =
                        profitabilityState.sortKey === key && profitabilityState.sortDirection === 'desc'
                            ? 'asc'
                            : 'desc';
                    profitabilityState.sortKey = key;
                    renderProfitabilityTable(buildProfitabilityRows());
                });
            });
        }

        function bindDrilldownEvents() {
            /* Close on overlay background click */
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeDrilldown();
            });
            document.getElementById('drilldownClose').addEventListener('click', closeDrilldown);

            /* Escape key */
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeDrilldown();
            });

            /* Level switcher */
            btnState.addEventListener('click', () => {
                if (drilldownState.level === 'state') return;
                drilldownState.level = 'state';
                btnState.classList.add('active');
                btnSubcategory.classList.remove('active');
                fetchAndRenderDrilldown();
            });

            btnSubcategory.addEventListener('click', () => {
                if (drilldownState.level === 'subcategory') return;
                drilldownState.level = 'subcategory';
                btnSubcategory.classList.add('active');
                btnState.classList.remove('active');
                fetchAndRenderDrilldown();
            });
        }

        /* ══════════════════════════════════════════════════ */
        /*  Drilldown open / close / fetch                    */
        /* ══════════════════════════════════════════════════ */
        function openDrilldown(category, region) {
            drilldownState.category = category;
            drilldownState.region   = region;
            drilldownState.level    = 'state';

            /* Reset level switcher UI */
            btnState.classList.add('active');
            btnSubcategory.classList.remove('active');

            drilldownEyebrow.textContent = `${category} · ${region}`;
            drilldownTitle.textContent   = 'Profit Breakdown';

            showDrilldownLoading();
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';

            fetchAndRenderDrilldown();
        }

        function closeDrilldown() {
            overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        function showDrilldownLoading() {
            drilldownBody.innerHTML = `
                <div class="drilldown-loading">
                    <div class="drilldown-spinner"></div>
                    <span>Loading data…</span>
                </div>
            `;
        }

        async function fetchAndRenderDrilldown() {
            if (!drilldownState.category || !drilldownState.region) return;

            showDrilldownLoading();

            const params = new URLSearchParams({
                category: drilldownState.category,
                region:   drilldownState.region,
                level:    drilldownState.level,
                start:    profitabilityState.start,
                end:      profitabilityState.end,
            });

            try {
                const response = await fetch(`${drilldownApiUrl}?${params.toString()}`);
                const data     = await response.json();
                renderDrilldownBody(data);
            } catch (err) {
                drilldownBody.innerHTML = `
                    <div class="empty-state">
                        <strong>Failed to load drilldown data.</strong>
                        <span>Check your network connection and try again.</span>
                    </div>
                `;
            }
        }

        function renderDrilldownBody(data) {
            const { rows, category, region, level } = data;

            if (!rows || rows.length === 0) {
                drilldownBody.innerHTML = `
                    <div class="empty-state">
                        <strong>No breakdown data available.</strong>
                        <span>No records for ${category} · ${region} in the current date window.</span>
                    </div>
                `;
                return;
            }

            const totalSales  = rows.reduce((s, r) => s + r.total_sales, 0);
            const totalProfit = rows.reduce((s, r) => s + r.total_profit, 0);
            const totalTxn    = rows.reduce((s, r) => s + r.transaction_count, 0);
            const margin      = totalSales > 0 ? (totalProfit / totalSales) * 100 : 0;
            const marginTone  = totalProfit > 0 ? 'text-success' : (totalProfit < 0 ? 'text-danger' : '');

            const levelLabel = level === 'subcategory' ? 'Sub-category' : 'State';

            /* Max absolute profit for bar scaling */
            const maxAbsProfit = Math.max(...rows.map(r => Math.abs(r.total_profit)), 1);

            const barsHtml = rows.map(row => {
                const pct       = (Math.abs(row.total_profit) / maxAbsProfit) * 100;
                const isProfit  = row.total_profit > 0;
                const isLoss    = row.total_profit < 0;
                const fillColor = isProfit ? '#15803d' : (isLoss ? '#b91c1c' : '#94a3b8');
                const toneClass = isProfit ? 'text-success' : (isLoss ? 'text-danger' : '');
                const statusCls = isProfit ? 'success' : (isLoss ? 'danger' : 'warning');

                return `
                    <div class="drilldown-bar-item">
                        <div class="drilldown-bar-top">
                            <span class="drilldown-bar-name" title="${row.label}">${row.label}</span>
                            <div class="drilldown-bar-numbers">
                                <span class="${toneClass}" style="font-weight:700;">${dashboardUtils.formatCurrency(row.total_profit)}</span>
                                <span style="color:var(--muted);">${dashboardUtils.formatPercent(row.profit_margin)}</span>
                                <span class="status-pill ${statusCls}" style="font-size:0.65rem; min-height:22px; padding:0 7px;">${row.status}</span>
                            </div>
                        </div>
                        <div class="drilldown-bar-track">
                            <div class="drilldown-bar-fill" style="width: ${pct.toFixed(1)}%; background: ${fillColor};"></div>
                        </div>
                        <div style="font-size:0.75rem; color:var(--muted); margin-top:1px;">
                            Sales: ${dashboardUtils.formatCurrency(row.total_sales)} &nbsp;·&nbsp; Transactions: ${dashboardUtils.formatNumber(row.transaction_count)}
                        </div>
                    </div>
                `;
            }).join('');

            drilldownBody.innerHTML = `
                <div class="drilldown-summary">
                    <div class="drilldown-metric">
                        <div class="drilldown-metric-label">Total Sales</div>
                        <div class="drilldown-metric-value">${dashboardUtils.formatCurrency(totalSales)}</div>
                    </div>
                    <div class="drilldown-metric">
                        <div class="drilldown-metric-label">Total Profit</div>
                        <div class="drilldown-metric-value ${marginTone}">${dashboardUtils.formatCurrency(totalProfit)}</div>
                    </div>
                    <div class="drilldown-metric">
                        <div class="drilldown-metric-label">Margin</div>
                        <div class="drilldown-metric-value ${marginTone}">${dashboardUtils.formatPercent(margin)}</div>
                    </div>
                </div>
                <p style="font-size:0.78rem; color:var(--muted); margin:0 0 12px;">${rows.length} ${levelLabel.toLowerCase()}${rows.length !== 1 ? 's' : ''} · ${dashboardUtils.formatNumber(totalTxn)} transactions</p>
                <div class="drilldown-bar-list">${barsHtml}</div>
            `;
        }

        /* ══════════════════════════════════════════════════ */
        /*  Core rendering helpers                            */
        /* ══════════════════════════════════════════════════ */
        function syncProfitControlsFromDraft() {
            yearFilter.value      = profitabilityDraftState.year;
            startMonthInput.value = profitabilityDraftState.start;
            endMonthInput.value   = profitabilityDraftState.end;
        }

        function buildProfitabilityRows() {
            return profitabilitySeries.map((item) => {
                const periods = item.period_data.filter((period) =>
                    dashboardUtils.isWithinRange(period.period_key, profitabilityState.start, profitabilityState.end)
                );

                const totalSales       = periods.reduce((sum, p) => sum + Number(p.sales || 0), 0);
                const totalProfit      = periods.reduce((sum, p) => sum + Number(p.profit || 0), 0);
                const transactionCount = periods.reduce((sum, p) => sum + Number(p.transaction_count || 0), 0);
                const profitMargin     = totalSales === 0 ? 0 : (totalProfit / totalSales) * 100;
                const status           = totalProfit > 0 ? 'Profit' : (totalProfit < 0 ? 'Loss' : 'Neutral');

                return {
                    category:          item.category,
                    region:            item.region,
                    total_sales:       Number(totalSales.toFixed(2)),
                    total_profit:      Number(totalProfit.toFixed(2)),
                    profit_margin:     Number(profitMargin.toFixed(2)),
                    transaction_count: transactionCount,
                    status,
                    has_data:          periods.length > 0,
                };
            });
        }

        function renderProfitabilityDashboard() {
            const rows = buildProfitabilityRows();

            renderSummaryMetrics(rows);
            renderProfitabilitySpotlight(rows);
            renderProfitabilityHeatmap(rows);
            renderProfitabilityBarChart(rows);
            renderProfitabilityTable(rows);
        }

        /* ══════════════════════════════════════════════════ */
        /*  Summary metrics (renamed from KPIs)               */
        /* ══════════════════════════════════════════════════ */
        function renderSummaryMetrics(rows) {
            const activeRows  = rows.filter((row) => row.has_data);
            const totalSales  = activeRows.reduce((sum, row) => sum + row.total_sales, 0);
            const totalProfit = activeRows.reduce((sum, row) => sum + row.total_profit, 0);
            const margin      = totalSales === 0 ? 0 : (totalProfit / totalSales) * 100;
            const status      = totalProfit > 0 ? 'Profit' : (totalProfit < 0 ? 'Loss' : 'Balanced');
            const tone        = totalProfit > 0 ? 'text-success' : (totalProfit < 0 ? 'text-danger' : '');

            document.getElementById('profitTotalSales').textContent  = dashboardUtils.formatCurrency(totalSales);
            document.getElementById('profitTotalProfit').textContent = dashboardUtils.formatCurrency(totalProfit);
            document.getElementById('profitMargin').textContent      = dashboardUtils.formatPercent(margin);
            document.getElementById('profitStatus').textContent      = status;

            document.getElementById('profitTotalProfit').className = `metric-value ${tone}`.trim();
            document.getElementById('profitMargin').className      = `metric-value ${tone}`.trim();
            document.getElementById('profitStatus').className      = `metric-value ${tone}`.trim();
            document.getElementById('profitStatusMeta').textContent =
                `${activeRows.length} visible combinations between ${dashboardUtils.monthKeyToLabel(profitabilityState.start)} and ${dashboardUtils.monthKeyToLabel(profitabilityState.end)}.`;
        }

        function renderProfitabilitySpotlight(rows) {
            const activeRows = rows.filter((row) => row.has_data).sort((l, r) => r.profit_margin - l.profit_margin);
            const best       = activeRows[0];
            const worst      = [...activeRows].sort((l, r) => l.profit_margin - r.profit_margin)[0];
            const spotlight  = document.getElementById('profitSpotlight');

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

        /* ══════════════════════════════════════════════════ */
        /*  Heatmap with drilldown                            */
        /* ══════════════════════════════════════════════════ */
        function renderProfitabilityHeatmap(rows) {
            const grid       = document.getElementById('profitHeatmap');
            const regions    = profitabilityMeta.regions;
            const categories = profitabilityMeta.categories;
            const lookup     = new Map(rows.map((row) => [`${row.category}__${row.region}`, row]));

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
                        total_sales:   0,
                        total_profit:  0,
                        profit_margin: 0,
                        status:        'Neutral',
                        has_data:      false,
                    };

                    const background   = row.has_data ? profitabilityColor(row.profit_margin) : 'rgba(255, 255, 255, 0.68)';
                    const statusClass  = row.total_profit > 0 ? 'success' : (row.total_profit < 0 ? 'danger' : 'warning');
                    const clickable    = row.has_data ? `data-category="${category}" data-region="${region}" tabindex="0" role="button" aria-label="Drilldown: ${category} in ${region}"` : '';

                    fragments.push(`
                        <div class="heatmap-cell" style="background: ${background}" ${clickable}>
                            <strong>${dashboardUtils.formatPercent(row.profit_margin)}</strong>
                            <span>${dashboardUtils.formatCurrency(row.total_profit)} profit</span>
                            <span class="status-pill ${statusClass}">${row.status}</span>
                            ${row.has_data ? '<span class="drill-hint">↗ Drill down</span>' : ''}
                        </div>
                    `);
                });
            });

            fragments.push('</div>');
            grid.innerHTML = fragments.join('');

            /* Attach click events to cells */
            grid.querySelectorAll('.heatmap-cell[data-category]').forEach((cell) => {
                const handler = () => openDrilldown(cell.dataset.category, cell.dataset.region);
                cell.addEventListener('click', handler);
                cell.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); handler(); }
                });
            });
        }

        function profitabilityColor(margin) {
            const clamped = Math.max(-25, Math.min(25, margin));

            if (clamped > 0) {
                const opacity = 0.04 + (clamped / 25) * 0.2;
                return `rgba(21, 128, 61, ${opacity})`;
            }

            if (clamped < 0) {
                const opacity = 0.04 + (Math.abs(clamped) / 25) * 0.2;
                return `rgba(185, 28, 28, ${opacity})`;
            }

            return 'rgba(0, 0, 0, 0.02)';
        }

        /* ══════════════════════════════════════════════════ */
        /*  Bar chart                                         */
        /* ══════════════════════════════════════════════════ */
        function renderProfitabilityBarChart(rows) {
            const categories = profitabilityMeta.categories;
            const regions    = profitabilityMeta.regions;

            dashboardUtils.destroyChart(profitBarChart);

            profitBarChart = new Chart(document.getElementById('profitBarChart'), {
                type: 'bar',
                data: {
                    labels:   categories,
                    datasets: regions.map((region, index) => ({
                        label: region,
                        data: categories.map((category) => {
                            const row = rows.find((entry) => entry.category === category && entry.region === region);
                            return row ? row.total_profit : 0;
                        }),
                        backgroundColor: dashboardUtils.pickColorAlpha(index, 0.85),
                        borderColor: dashboardUtils.pickColor(index),
                        borderWidth: 0,
                        borderRadius: 8,
                        maxBarThickness: 32,
                    })),
                },
                options: {
                    responsive:          true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: { grid: { display: false } },
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

        /* ══════════════════════════════════════════════════ */
        /*  Detail table                                      */
        /* ══════════════════════════════════════════════════ */
        function renderProfitabilityTable(rows) {
            const tbody      = document.getElementById('profitTableBody');
            const sortable   = rows.filter((row) => row.has_data);
            const sortedRows = dashboardUtils.sortRows(sortable, profitabilityState.sortKey, profitabilityState.sortDirection);

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
                const toneClass   = row.total_profit > 0 ? 'text-success' : (row.total_profit < 0 ? 'text-danger' : '');

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
