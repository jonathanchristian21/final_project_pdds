<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            /* Professional App Color Palette */
            --bg-color: #f8fafc;
            --surface: #ffffff;
            --surface-hover: #f1f5f9;
            --surface-sunken: #f4f4f5;
            --border: #cbd5e1;
            --border-light: #e2e8f0;

            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-tertiary: #94a3b8;
            --text-muted: #64748b;

            /* Colorful Accents */
            --accent: #2563eb;
            --accent-soft: rgba(37, 99, 235, 0.08);
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #ca8a04;

            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;

            --shadow-sm: 0 2px 8px rgba(15, 23, 42, 0.04);
            --shadow-md: 0 4px 16px rgba(15, 23, 42, 0.06);
            --shadow-lg: 0 10px 32px rgba(15, 23, 42, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* ── App Shell ─────────────────────────────── */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-light);
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-brand {
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            color: var(--text-primary);
        }

        .header-nav {
            display: flex;
            gap: 24px;
        }

        .nav-link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: color 0.15s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--text-primary);
        }

        .nav-link.active {
            color: var(--text-primary);
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -22px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--text-primary);
        }

        .app-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 48px 24px;
        }

        /* ── Typography ─────────────────────────────── */
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            margin-bottom: 16px;
        }

        .text-muted {
            color: var(--text-secondary);
        }

        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }

        /* ── Forms & Buttons ─────────────────────────────── */
        input, select, button {
            font-family: inherit;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .field select, .field input {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-color);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            color: var(--text-primary);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .field select:focus, .field input:focus {
            outline: none;
            border-color: var(--text-tertiary);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
        }

        .button-primary {
            background: var(--text-primary);
            color: #fff;
        }

        .button-primary:hover {
            background: #000;
            transform: translateY(-1px);
        }

        .button-secondary {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .button-secondary:hover {
            background: var(--surface-hover);
        }

        /* ── Components ─────────────────────────────── */
        .card {
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            padding: 24px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--surface);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            padding: 20px;
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-value {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: -0.03em;
            margin: 8px 0 4px;
            color: var(--text-primary);
        }

        .metric-meta {
            font-size: 0.8125rem;
            color: var(--text-tertiary);
        }

        /* ── Layout Grids ─────────────────────────────── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 32px;
            align-items: start;
        }

        .filter-sidebar {
            position: sticky;
            top: 96px;
        }

        /* ── Tables ─────────────────────────────── */
        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        th {
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            padding: 12px 16px;
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-primary);
        }

        th.numeric, td.numeric {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        th.is-sortable {
            cursor: pointer;
            transition: color 0.15s ease;
        }
        th.is-sortable:hover {
            color: var(--text-primary);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
            background: var(--surface-hover);
        }

        .status-pill.success { color: var(--success); background: rgba(16, 185, 129, 0.1); }
        .status-pill.danger { color: var(--danger); background: rgba(239, 68, 68, 0.1); }

        /* ── Spotlight ─────────────────────────────── */
        .spotlight {
            background: var(--surface);
            border-left: 3px solid var(--text-primary);
            padding: 20px 24px;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            margin-bottom: 32px;
            box-shadow: var(--shadow-sm);
        }

        .spotlight-tag {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
            margin-bottom: 8px;
        }

        .spotlight p {
            font-size: 0.9375rem;
            color: var(--text-primary);
            max-width: 800px;
        }

        /* ── Charts ─────────────────────────────── */
        .chart-container {
            height: 400px;
            width: 100%;
            margin-top: 16px;
        }

        /* ── Heatmap ─────────────────────────────── */
        .heatmap-board {
            overflow-x: auto;
            margin-top: 16px;
        }
        .heatmap-grid {
            display: grid;
            grid-template-columns: 140px repeat(4, 1fr);
            gap: 2px;
            min-width: 600px;
        }
        .heatmap-axis {
            padding: 12px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
        }
        .heatmap-axis.head {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border);
        }
        .heatmap-cell {
            padding: 16px;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface-hover);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        /* Utilities */
        .mb-4 { margin-bottom: 1rem; }
        .mb-8 { margin-bottom: 2rem; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .gap-4 { gap: 1rem; }
    </style>

    <script>
        // Chart.js Apple-like minimalist defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#64748b';
        Chart.defaults.scale.grid.color = '#e2e8f0';
        Chart.defaults.scale.grid.borderColor = '#cbd5e1';
        Chart.defaults.plugins.tooltip.backgroundColor = '#ffffff';
        Chart.defaults.plugins.tooltip.titleColor = '#0f172a';
        Chart.defaults.plugins.tooltip.bodyColor = '#475569';
        Chart.defaults.plugins.tooltip.borderColor = '#e2e8f0';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.boxPadding = 6;
        Chart.defaults.plugins.tooltip.usePointStyle = true;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        const dashboardUtils = {
            formatCurrency: (val) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val),
            formatPercent: (val) => new Intl.NumberFormat('en-US', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(val / 100),
            formatNumber: (val) => new Intl.NumberFormat('en-US').format(val),
            
            destroyChart: (chart) => { if (chart) chart.destroy(); },
            
            sortRows: (rows, key, dir) => {
                return [...rows].sort((a, b) => {
                    const l = a[key];
                    const r = b[key];
                    if (l === r) return 0;
                    const res = l > r ? 1 : -1;
                    return dir === 'asc' ? res : -res;
                });
            },

            isWithinRange: (periodKey, start, end) => {
                if (start && periodKey < start) return false;
                if (end && periodKey > end) return false;
                return true;
            },

            monthKeyToLabel: (key) => {
                if (!key) return '-';
                const [y, m] = key.split('-');
                const d = new Date(y, m - 1, 1);
                return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            },

            bucketLabel: (key, granularity) => {
                if (granularity === 'month') return dashboardUtils.monthKeyToLabel(key);
                if (granularity === 'quarter') return key.replace('-', ' ');
                return key;
            },

            pickColor: (index) => {
                const palette = [
                    '#2563eb', // Blue
                    '#e11d48', // Rose
                    '#16a34a', // Green
                    '#ea580c', // Orange
                    '#7c3aed', // Violet
                    '#0891b2', // Cyan
                    '#ca8a04', // Amber
                    '#db2777', // Pink
                    '#059669', // Emerald
                    '#dc2626', // Red
                    '#4f46e5', // Indigo
                    '#15803d', // Dark green
                ];
                return palette[index % palette.length];
            },

            pickColorAlpha: (index, alpha = 0.15) => {
                const palette = [
                    `rgba(37, 99, 235, ${alpha})`,
                    `rgba(225, 29, 72, ${alpha})`,
                    `rgba(22, 163, 74, ${alpha})`,
                    `rgba(234, 88, 12, ${alpha})`,
                    `rgba(124, 58, 237, ${alpha})`,
                    `rgba(8, 145, 178, ${alpha})`,
                    `rgba(202, 138, 4, ${alpha})`,
                    `rgba(219, 39, 119, ${alpha})`,
                    `rgba(5, 150, 105, ${alpha})`,
                    `rgba(220, 38, 38, ${alpha})`,
                    `rgba(79, 70, 229, ${alpha})`,
                    `rgba(21, 128, 61, ${alpha})`,
                ];
                return palette[index % palette.length];
            }
        };
    </script>
    @yield('styles')
</head>
<body>

    <header class="app-header">
        <div class="header-brand">Superstore Data Analysis</div>
        <nav class="header-nav">
            <a href="{{ route('insights.index') }}" class="nav-link {{ request()->routeIs('insights.index') ? 'active' : '' }}">Overview</a>
            <a href="{{ route('insights.profitability') }}" class="nav-link {{ request()->routeIs('insights.profitability') ? 'active' : '' }}">Profitability</a>
            <a href="{{ route('insights.discount') }}" class="nav-link {{ request()->routeIs('insights.discount') ? 'active' : '' }}">Discounts</a>
            <a href="{{ route('insights.trend') }}" class="nav-link {{ request()->routeIs('insights.trend') ? 'active' : '' }}">Trends</a>
        </nav>
    </header>

    <main class="app-main">
        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
