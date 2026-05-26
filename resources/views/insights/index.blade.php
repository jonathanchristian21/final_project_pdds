@extends('layouts.insights')

@section('title', 'Analisis Penjualan Superstore')
@section('accent', '#9a3412')
@section('accent_secondary', '#2563eb')
@section('accent_soft', 'rgba(154, 52, 18, 0.14)')

@php
    $meta = $overviewData['meta'];
    $summary = $overviewData['summary'];
    $rangeLabel = ($meta['date_range']['start'] ?? null) && ($meta['date_range']['end'] ?? null)
        ? \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['start'])->format('M Y') . ' to ' . \Carbon\Carbon::createFromFormat('Y-m', $meta['date_range']['end'])->format('M Y')
        : 'Date range unavailable';
    $yearLabel = count($meta['years']) > 0
        ? implode(', ', $meta['years'])
        : 'No data';
    $partialYearLabel = count($meta['partial_years']) > 0
        ? implode(', ', array_map(fn ($item) => $item['year'] . ' (' . $item['month_count'] . ' months)', $meta['partial_years']))
        : '';
@endphp

@section('hero')
    <section class="hero">
        <div class="hero-grid">
            <span class="hero-kicker">Overview</span>
            <h1>General Information</h1>
            <div class="hero-meta">
                <span class="meta-chip">Coverage: {{ $rangeLabel }}</span>
                <span class="meta-chip">Categories: {{ implode(', ', $meta['categories']) }}</span>
                <span class="meta-chip">Regions: {{ implode(', ', $meta['regions']) }}</span>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <div class="stack">
        <!-- first row -->
        <section class="panel">
            <div class="panel-body">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Summary</h2>
                    </div>
                </div>
                <div class="kpi-grid">
                    <article class="kpi-card">
                        <div class="kpi-label">Insight Docs</div>
                        <div class="kpi-value">{{ number_format($summary['documents']) }}</div>
                        <div class="kpi-meta">MongoDB</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Total Sales</div>
                        <div class="kpi-value">{{ '$' . number_format($summary['total_sales'], 2) }}</div>
                        <div class="kpi-meta">All segments</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Total Profit</div>
                        <div class="kpi-value {{ $summary['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ '$' . number_format($summary['total_profit'], 2) }}
                        </div>
                        <div class="kpi-meta">All segments</div>
                    </article>
                    <article class="kpi-card">
                        <div class="kpi-label">Year Coverage</div>
                        <div class="kpi-value" style="font-size: 1.5rem; line-height: 1.3;">{{ $yearLabel }}</div>
                        <div class="kpi-meta">{{ $partialYearLabel ?: 'Full year data' }}</div>
                    </article>
                </div>
            </div>
        </section>

        <!-- second row -->
        <section class="card-grid">
            <article class="journey-card">
                <span class="spotlight-tag">Analysis 1</span>
                <h3>Profitability</h3>
                <div class="mini-metrics">
                    <span class="mini-metric">{{ $summary['profitable_count'] }} profit</span>
                    <span class="mini-metric">{{ $summary['loss_count'] }} loss</span>
                </div>
                <a href="{{ route('insights.profitability') }}" class="button button-primary">Open dashboard</a>
            </article>

            <article class="journey-card">
                <span class="spotlight-tag">Analysis 2</span>
                <h3>Discount</h3>
                <div class="mini-metrics">
                    <span class="mini-metric">{{ $summary['positive_discount_count'] }} positive</span>
                    <span class="mini-metric">{{ $summary['negative_discount_count'] }} negative</span>
                </div>
                <a href="{{ route('insights.discount') }}" class="button button-primary">Open dashboard</a>
            </article>

            <article class="journey-card">
                <span class="spotlight-tag">Analysis 3</span>
                <h3>Sales Trend</h3>
                <div class="mini-metrics">
                    <span class="mini-metric">Month / Quarter / Year</span>
                    <span class="mini-metric">Growth</span>
                </div>
                <a href="{{ route('insights.trend') }}" class="button button-primary">Open dashboard</a>
            </article>
        </section>
    </div>
@endsection
