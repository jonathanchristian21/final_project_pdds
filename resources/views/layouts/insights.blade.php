<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Analisis Penjualan Superstore')</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f6f8;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --ink: #0f172a;
            --muted: #64748b;
            --success: #15803d;
            --success-soft: #dcfce7;
            --danger: #b91c1c;
            --danger-soft: #fee2e2;
            --warning: #b45309;
            --warning-soft: #fef3c7;
            --accent: @yield('accent', '#1d4ed8');
            --accent-secondary: @yield('accent_secondary', '#1d4ed8');
            --accent-soft: @yield('accent_soft', 'rgba(29, 78, 216, 0.1)');
            --shadow-soft: 0 1px 2px rgba(15, 23, 42, 0.05);
            --shadow-strong: 0 12px 32px rgba(15, 23, 42, 0.08);
            --radius-xl: 20px;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
            --font-body: "Segoe UI", "Aptos", "Helvetica Neue", Arial, sans-serif;
            --font-display: "Segoe UI", "Aptos", "Helvetica Neue", Arial, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: var(--font-body);
            color: var(--ink);
            background: var(--bg);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .page-shell {
            max-width: 1360px;
            margin: 0 auto;
            padding: 20px clamp(16px, 2.4vw, 32px) 28px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--surface);
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 12px;
            z-index: 20;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            color: white;
            font-weight: 700;
            letter-spacing: 0.04em;
            background: var(--accent);
        }

        .brand-copy {
            min-width: 0;
        }

        .eyebrow {
            margin: 0 0 2px;
            color: var(--muted);
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .brand-name {
            display: block;
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.1;
        }

        .nav-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav-link {
            padding: 9px 12px;
            border-radius: 10px;
            color: var(--muted);
            font-weight: 600;
            transition: 180ms ease;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            color: var(--ink);
            background: var(--surface-muted);
            border-color: var(--border);
        }

        .nav-link.active {
            color: white;
            background: var(--accent);
            box-shadow: none;
        }

        .content {
            margin-top: 18px;
        }

        .hero {
            border-radius: var(--radius-xl);
            padding: 20px 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
        }

        .hero-grid {
            display: grid;
            gap: 10px;
        }

        .hero-kicker {
            display: inline-flex;
            width: fit-content;
            padding: 6px 10px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .hero h1 {
            margin: 0;
            font-family: var(--font-display);
            font-size: clamp(1.6rem, 3vw, 2.25rem);
            line-height: 1.1;
            max-width: none;
        }

        .hero p {
            margin: 0;
            max-width: 64ch;
            color: var(--muted);
            font-size: 0.96rem;
            line-height: 1.6;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 4px;
        }

        .meta-chip {
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            color: var(--muted);
            font-weight: 600;
            font-size: 0.86rem;
        }

        .stack {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .panel-body {
            padding: 20px;
        }

        .panel-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .panel-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .panel-copy {
            margin: 2px 0 0;
            color: var(--muted);
            line-height: 1.5;
            max-width: 56ch;
            font-size: 0.9rem;
        }

        .dashboard-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .filter-card {
            position: sticky;
            top: 84px;
            align-self: start;
            padding: 18px;
            border-radius: var(--radius-lg);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
        }

        .filter-card h2,
        .filter-card h3 {
            margin: 0 0 10px;
            font-size: 1rem;
        }

        .filter-card p {
            margin: 0 0 16px;
            color: var(--muted);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .field {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        .field label {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--ink);
        }

        .field input,
        .field select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink);
            min-height: 42px;
            padding: 0 12px;
            outline: none;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .button-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-weight: 600;
            cursor: pointer;
            transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease;
        }

        .button:hover {
            box-shadow: none;
        }

        .button-primary {
            color: white;
            background: var(--accent);
            border-color: var(--accent);
        }

        .button-secondary {
            color: var(--ink);
            background: var(--surface);
        }

        .button-link {
            color: var(--accent);
            background: var(--surface);
        }

        .dashboard-main {
            display: grid;
            gap: 16px;
            min-width: 0;
        }

        .kpi-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .kpi-card {
            padding: 16px;
            border-radius: var(--radius-md);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: none;
        }

        .kpi-label {
            color: var(--muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .kpi-value {
            margin: 8px 0 0;
            font-size: clamp(1.3rem, 2vw, 1.8rem);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .kpi-meta {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .spotlight-card {
            display: grid;
            gap: 8px;
            padding: 16px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: var(--surface);
            box-shadow: none;
        }

        .spotlight-tag {
            width: fit-content;
            padding: 5px 8px;
            border-radius: 8px;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--accent);
            background: var(--accent-soft);
        }

        .spotlight-copy {
            margin: 0;
            line-height: 1.55;
            color: var(--ink);
            font-size: 0.92rem;
        }

        .split-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .chart-frame {
            position: relative;
            height: min(420px, 58vw);
            min-height: 300px;
        }

        .chart-frame canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .heatmap-board {
            display: grid;
            gap: 8px;
            min-width: 0;
        }

        .heatmap-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: 124px repeat(4, minmax(0, 1fr));
            align-items: stretch;
        }

        .heatmap-axis,
        .heatmap-cell {
            min-height: 96px;
            border-radius: 12px;
        }

        .heatmap-axis {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            color: var(--muted);
            font-weight: 700;
            text-align: center;
        }

        .heatmap-axis.head {
            min-height: 48px;
            font-size: 0.78rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .heatmap-cell {
            padding: 14px;
            border: 1px solid var(--border);
            display: grid;
            gap: 8px;
            align-content: start;
            color: #0f172a;
        }

        .heatmap-cell strong {
            font-size: 1.15rem;
            letter-spacing: -0.03em;
        }

        .heatmap-cell span {
            color: rgba(15, 23, 42, 0.8);
            font-size: 0.84rem;
            line-height: 1.4;
        }

        .heatmap-legend {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .legend-bar {
            width: 140px;
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            border: 1px solid var(--border);
        }

        .data-table-wrap {
            overflow: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .data-table th,
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #eef2f7;
            text-align: left;
            vertical-align: middle;
        }

        .data-table th {
            position: sticky;
            top: 0;
            background: var(--surface);
            color: var(--muted);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            z-index: 1;
        }

        .data-table th.is-sortable {
            cursor: pointer;
            user-select: none;
        }

        .data-table th.is-sortable:hover {
            color: var(--ink);
        }

        .data-table td.numeric,
        .data-table th.numeric {
            text-align: right;
        }

        .data-table tr:hover td {
            background: #f8fafc;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .status-pill.success {
            color: var(--success);
            background: var(--success-soft);
            border-color: rgba(31, 122, 77, 0.18);
        }

        .status-pill.danger {
            color: var(--danger);
            background: var(--danger-soft);
            border-color: rgba(177, 65, 52, 0.18);
        }

        .status-pill.warning {
            color: var(--warning);
            background: var(--warning-soft);
            border-color: rgba(185, 125, 18, 0.18);
        }

        .section-note {
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            color: var(--muted);
            line-height: 1.5;
            font-size: 0.88rem;
        }

        .card-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .journey-card {
            display: grid;
            gap: 12px;
            padding: 18px;
            border-radius: var(--radius-lg);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-soft);
        }

        .journey-card h3 {
            margin: 0;
            font-size: 1rem;
            font-family: var(--font-display);
            line-height: 1.3;
        }

        .journey-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: 0.9rem;
        }

        .journey-card .button {
            width: fit-content;
        }

        .mini-metrics {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .mini-metric {
            padding: 8px 10px;
            border-radius: 10px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            color: var(--ink);
            font-weight: 600;
            font-size: 0.84rem;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
            padding: 4px 2px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .empty-state {
            display: grid;
            place-items: center;
            gap: 8px;
            min-height: 180px;
            border-radius: 12px;
            border: 1px dashed var(--border-strong);
            background: var(--surface-muted);
            color: var(--muted);
            text-align: center;
            padding: 24px;
        }

        .muted {
            color: var(--muted);
        }

        .text-success {
            color: var(--success);
        }

        .text-danger {
            color: var(--danger);
        }

        @media (max-width: 1120px) {
            .dashboard-grid,
            .split-grid,
            .card-grid {
                grid-template-columns: 1fr;
            }

            .filter-card {
                position: static;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .heatmap-grid {
                grid-template-columns: 112px repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .page-shell {
                padding: 16px 14px 30px;
            }

            .topbar {
                border-radius: 14px;
                padding: 14px;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-tabs {
                width: 100%;
                justify-content: flex-start;
            }

            .hero h1 {
                max-width: none;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .heatmap-grid {
                grid-template-columns: 92px repeat(4, minmax(132px, 1fr));
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .heatmap-board {
                overflow-x: auto;
            }

            .button-row {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <div class="brand">
                <!-- <div class="brand-mark">PD</div> -->
                <div class="brand-copy">
                    <p class="eyebrow">MySQL and MongoDB</p>
                    <a href="{{ route('insights.index') }}" class="brand-name">Analisis Penjualan Superstore</a>
                </div>
            </div>
            <nav class="nav-tabs" aria-label="Primary">
                <a href="{{ route('insights.index') }}" class="nav-link {{ request()->routeIs('insights.index') || request()->routeIs('home') ? 'active' : '' }}">Overview</a>
                <a href="{{ route('insights.profitability') }}" class="nav-link {{ request()->routeIs('insights.profitability') ? 'active' : '' }}">Profitability</a>
                <a href="{{ route('insights.discount') }}" class="nav-link {{ request()->routeIs('insights.discount') ? 'active' : '' }}">Discount</a>
                <a href="{{ route('insights.trend') }}" class="nav-link {{ request()->routeIs('insights.trend') ? 'active' : '' }}">Sales Trend</a>
            </nav>
        </header>

        <main class="content">
            @yield('hero')
            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const dashboardUtils = (() => {
            const currencyFormatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const numberFormatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });

            const decimalFormatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const monthMap = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const palette = ['#0f766e', '#2563eb', '#ca8a04', '#c2410c', '#9333ea', '#0f766e', '#db2777'];

            function toMonthKey(year, month) {
                return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`;
            }

            function toQuarterKey(year, month) {
                return `${year}-Q${Math.ceil(month / 3)}`;
            }

            function monthKeyToLabel(key) {
                const [year, month] = key.split('-').map(Number);
                if (!year || !month) {
                    return key;
                }

                return `${monthMap[month - 1]} ${year}`;
            }

            function quarterKeyToLabel(key) {
                const [year, quarter] = key.split('-');
                return `${quarter} ${year}`;
            }

            function bucketLabel(key, granularity) {
                if (granularity === 'month') {
                    return monthKeyToLabel(key);
                }

                if (granularity === 'quarter') {
                    return quarterKeyToLabel(key);
                }

                return key;
            }

            function isWithinRange(key, start, end) {
                return key >= start && key <= end;
            }

            function formatCurrency(value) {
                return currencyFormatter.format(Number(value || 0));
            }

            function formatNumber(value) {
                return numberFormatter.format(Number(value || 0));
            }

            function formatDecimal(value) {
                return decimalFormatter.format(Number(value || 0));
            }

            function formatPercent(value, digits = 2) {
                return `${Number(value || 0).toFixed(digits)}%`;
            }

            function pickColor(index) {
                return palette[index % palette.length];
            }

            function destroyChart(chart) {
                if (chart) {
                    chart.destroy();
                }
            }

            function sortRows(rows, key, direction = 'desc') {
                return [...rows].sort((left, right) => {
                    const leftValue = left[key];
                    const rightValue = right[key];

                    if (typeof leftValue === 'number' && typeof rightValue === 'number') {
                        return direction === 'asc' ? leftValue - rightValue : rightValue - leftValue;
                    }

                    return direction === 'asc'
                        ? String(leftValue).localeCompare(String(rightValue))
                        : String(rightValue).localeCompare(String(leftValue));
                });
            }

            function summarizeGrowth(points) {
                if (!points.length) {
                    return { rate: 0, status: 'stable' };
                }

                if (points.length === 1 || !points[points.length - 2]) {
                    return { rate: 0, status: 'stable' };
                }

                const previous = Number(points[points.length - 2].value || 0);
                const latest = Number(points[points.length - 1].value || 0);

                if (previous === 0) {
                    return {
                        rate: 0,
                        status: latest > 0 ? 'increasing' : 'stable',
                    };
                }

                const rate = ((latest - previous) / previous) * 100;

                return {
                    rate,
                    status: rate > 0 ? 'increasing' : (rate < 0 ? 'decreasing' : 'stable'),
                };
            }

            function statusTone(value) {
                if (value > 0) {
                    return 'success';
                }

                if (value < 0) {
                    return 'danger';
                }

                return 'warning';
            }

            return {
                bucketLabel,
                destroyChart,
                formatCurrency,
                formatDecimal,
                formatNumber,
                formatPercent,
                isWithinRange,
                monthKeyToLabel,
                pickColor,
                sortRows,
                statusTone,
                summarizeGrowth,
                toMonthKey,
                toQuarterKey,
            };
        })();

        Chart.defaults.color = '#334155';
        Chart.defaults.font.family = '"Aptos", "Avenir Next", "Segoe UI", sans-serif';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 18;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.92)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 12;
    </script>
    @yield('scripts')
</body>
</html>
