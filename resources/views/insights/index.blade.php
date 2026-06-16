@extends('layouts.insights')

@section('title', 'Overview')

@php
    $summary = $overviewData['summary'] ?? ['documents' => 0];
    $meta = $overviewData['meta'] ?? ['years' => [], 'partial_years' => []];
    $yearLabel = empty($meta['years']) ? 'N/A' : (count($meta['years']) > 1 ? min($meta['years']) . ' - ' . max($meta['years']) : $meta['years'][0]);
    $partialYearLabel = count($meta['partial_years']) > 0
        ? 'Partial: ' . implode(', ', array_map(fn ($item) => $item['year'] . ' (' . $item['month_count'] . 'm)', $meta['partial_years']))
        : '';
@endphp

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1>Dashboard Overview</h1>
            <p class="text-muted">High-level summary of Superstore performance metrics.</p>
        </div>
        
        <!-- Data Management Actions -->
        <div class="flex gap-4">
            <button type="button" class="button button-secondary" onclick="document.getElementById('uploadModal').style.display='flex'">
                Upload Data
            </button>
            <button type="button" class="button button-secondary" style="color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" onclick="clearData()">
                Clear System
            </button>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 480px;">
            <h2 style="margin-bottom: 8px;">Upload Superstore CSV</h2>
            <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 24px;">Upload your dataset. This will automatically clear existing data and regenerate insights.</p>
            
            <form id="uploadForm" onsubmit="handleUpload(event)">
                <div class="field">
                    <input type="file" id="csvFile" accept=".csv" required>
                </div>
                <div class="flex gap-4" style="margin-top: 24px; justify-content: flex-end;">
                    <button type="button" class="button button-secondary" onclick="document.getElementById('uploadModal').style.display='none'">Cancel</button>
                    <button type="submit" class="button button-primary" id="uploadBtn">Upload & Process</button>
                </div>
            </form>
        </div>
    </div>

    @if($summary['documents'] === 0)
        <div class="card" style="text-align: center; padding: 64px 24px;">
            <h2 style="margin-bottom: 8px;">No Data Available</h2>
            <p class="text-muted">The system is currently empty. Please upload the Superstore dataset to generate insights.</p>
        </div>
    @else
        <div class="metric-grid">
            <article class="metric-card">
                <div class="metric-label">Total Sales</div>
                <div class="metric-value">{{ '$' . number_format($summary['total_sales'], 2) }}</div>
                <div class="metric-meta">Across all tracked segments</div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Total Profit</div>
                <div class="metric-value {{ $summary['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ '$' . number_format($summary['total_profit'], 2) }}
                </div>
                <div class="metric-meta">Across all tracked segments</div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Year Coverage</div>
                <div class="metric-value" style="font-size: 1.5rem; line-height: 1.3;">{{ $yearLabel }}</div>
                <div class="metric-meta">{{ $partialYearLabel ?: 'Full year data' }}</div>
            </article>
            <article class="metric-card">
                <div class="metric-label">Insight Documents</div>
                <div class="metric-value">{{ number_format($summary['documents']) }}</div>
                <div class="metric-meta">MongoDB aggregated nodes</div>
            </article>
        </div>

        <div class="dashboard-grid">
            <div style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                <div class="card">
                    <h2>Profitability Highlights</h2>
                    <ul style="list-style: none; display: grid; gap: 16px; margin-top: 20px;">
                        <li>
                            <div class="metric-label">Top Performer</div>
                            <div style="font-weight: 500; margin-top: 4px;">{{ $summary['top_profit_segment']['category'] }} in {{ $summary['top_profit_segment']['region'] }}</div>
                            <div class="text-success" style="font-size: 0.875rem;">{{ number_format($summary['top_profit_segment']['metrics']['profit_margin'], 1) }}% Margin</div>
                        </li>
                        <hr style="border: 0; border-top: 1px solid var(--border-light);">
                        <li>
                            <div class="metric-label">Weakest Link</div>
                            <div style="font-weight: 500; margin-top: 4px;">{{ $summary['weakest_profit_segment']['category'] }} in {{ $summary['weakest_profit_segment']['region'] }}</div>
                            <div class="text-danger" style="font-size: 0.875rem;">{{ number_format($summary['weakest_profit_segment']['metrics']['profit_margin'], 1) }}% Margin</div>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <h2>Discount & Trend Alerts</h2>
                    <ul style="list-style: none; display: grid; gap: 16px; margin-top: 20px;">
                        <li>
                            <div class="metric-label">Fastest Growing</div>
                            <div style="font-weight: 500; margin-top: 4px;">{{ $summary['top_growth_segment']['category'] }} in {{ $summary['top_growth_segment']['region'] }}</div>
                            <div class="text-success" style="font-size: 0.875rem;">Trending Upwards</div>
                        </li>
                        <hr style="border: 0; border-top: 1px solid var(--border-light);">
                        <li>
                            <div class="metric-label">Discount Warning</div>
                            <div style="font-weight: 500; margin-top: 4px;">{{ $summary['watchlist_discount_segment']['sub_category'] }} in {{ $summary['watchlist_discount_segment']['category'] }}</div>
                            <div class="text-danger" style="font-size: 0.875rem;">High discount driving negative margins</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
<script>
    async function handleUpload(e) {
        e.preventDefault();
        const fileInput = document.getElementById('csvFile');
        if (!fileInput.files.length) return;

        const btn = document.getElementById('uploadBtn');
        const originalText = btn.textContent;
        btn.textContent = 'Processing (This may take a minute)...';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csv_file', fileInput.files[0]);

        try {
            const res = await fetch('{{ route('insights.upload') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json();
                alert('Upload failed: ' + (data.error || 'Unknown error'));
            }
        } catch (err) {
            alert('Upload failed: ' + err.message);
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    async function clearData() {
        if (!confirm('Are you sure you want to clear all data? This cannot be undone.')) return;

        try {
            const res = await fetch('{{ route('insights.clear') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (res.ok) {
                window.location.reload();
            } else {
                alert('Clear failed.');
            }
        } catch (err) {
            alert('Clear failed: ' + err.message);
        }
    }
</script>
@endsection
