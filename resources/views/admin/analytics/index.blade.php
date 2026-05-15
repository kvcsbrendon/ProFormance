{{-- resources/views/admin/analytics/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')

<div class="kb-admin-section">
    <div class="kb-analytics-header">
        <div>
            <h1 class="kb-admin-title">Analytics</h1>
            <p class="kb-admin-subtitle">Performance overview for the last {{ $range }} days.</p>
        </div>
        <div class="kb-analytics-controls">
            <form method="GET" class="kb-analytics-range-form">
                <select name="range" class="kb-form-input kb-form-input-sm" onchange="this.form.submit()">
                    <option value="7" {{ $range == '7' ? 'selected' : '' }}>Last 7 days</option>
                    <option value="14" {{ $range == '14' ? 'selected' : '' }}>Last 14 days</option>
                    <option value="30" {{ $range == '30' ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ $range == '90' ? 'selected' : '' }}>Last 90 days</option>
                    <option value="365" {{ $range == '365' ? 'selected' : '' }}>Last 12 months</option>
                </select>
            </form>
            <div class="kb-analytics-export-btns">
                <a href="{{ route('admin.analytics.csv', ['range' => $range, 'type' => 'revenue']) }}" class="kb-admin-btn-sm"><i class="bi bi-filetype-csv"></i> Revenue CSV</a>
                <a href="{{ route('admin.analytics.csv', ['range' => $range, 'type' => 'orders']) }}" class="kb-admin-btn-sm"><i class="bi bi-filetype-csv"></i> Orders CSV</a>
                <a href="{{ route('admin.analytics.csv', ['range' => $range, 'type' => 'products']) }}" class="kb-admin-btn-sm"><i class="bi bi-filetype-csv"></i> Products CSV</a>
                <a href="{{ route('admin.analytics.pdf', ['range' => $range]) }}" class="kb-admin-btn-sm kb-admin-btn-sm-accent"><i class="bi bi-file-pdf"></i> PDF Report</a>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="kb-analytics-cards">
    <div class="kb-analytics-stat-card">
        <div class="kb-analytics-stat-label">Revenue</div>
        <div class="kb-analytics-stat-value">&pound;{{ number_format($totalRevenue / 100, 2) }}</div>
        <div class="kb-analytics-stat-change {{ $revenueChange >= 0 ? 'kb-change-up' : 'kb-change-down' }}">
            <i class="bi {{ $revenueChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
            {{ abs($revenueChange) }}% vs previous period
        </div>
    </div>
    <div class="kb-analytics-stat-card">
        <div class="kb-analytics-stat-label">Orders</div>
        <div class="kb-analytics-stat-value">{{ number_format($totalOrders) }}</div>
        <div class="kb-analytics-stat-change {{ $ordersChange >= 0 ? 'kb-change-up' : 'kb-change-down' }}">
            <i class="bi {{ $ordersChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
            {{ abs($ordersChange) }}% vs previous period
        </div>
    </div>
    <div class="kb-analytics-stat-card">
        <div class="kb-analytics-stat-label">Avg Order Value</div>
        <div class="kb-analytics-stat-value">&pound;{{ number_format($avgOrderValue / 100, 2) }}</div>
    </div>
    <div class="kb-analytics-stat-card">
        <div class="kb-analytics-stat-label">New Customers</div>
        <div class="kb-analytics-stat-value">{{ number_format($totalNewCustomers) }}</div>
    </div>
</div>

{{-- Charts Row 1: Revenue + Orders by Status --}}
<div class="kb-analytics-charts-row">
    <div class="kb-admin-card kb-analytics-chart-card kb-analytics-chart-wide">
        <h3 class="kb-admin-card-title">Revenue Over Time</h3>
        <canvas id="revenueChart" height="260"></canvas>
    </div>
    <div class="kb-admin-card kb-analytics-chart-card">
        <h3 class="kb-admin-card-title">Orders by Status</h3>
        <canvas id="statusChart" height="260"></canvas>
    </div>
</div>

{{-- Charts Row 2: Customers + Payment Methods --}}
<div class="kb-analytics-charts-row">
    <div class="kb-admin-card kb-analytics-chart-card kb-analytics-chart-wide">
        <h3 class="kb-admin-card-title">New Customers</h3>
        <canvas id="customersChart" height="260"></canvas>
    </div>
    <div class="kb-admin-card kb-analytics-chart-card">
        <h3 class="kb-admin-card-title">Revenue by Country</h3>
        <canvas id="countryChart" height="260"></canvas>
    </div>
</div>

{{-- Top Products Table --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-trophy"></i> Top Selling Products</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
                @forelse($topProducts as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->product_name }}</td>
                    <td>{{ number_format($p->total_qty) }}</td>
                    <td>&pound;{{ number_format($p->total_revenue / 100, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="kb-admin-muted">No sales data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Discount Usage Table --}}
@if($discountUsage->isNotEmpty())
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-tag"></i> Discount Code Usage</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Code</th><th>Times Used</th><th>Total Discount Given</th></tr></thead>
            <tbody>
                @foreach($discountUsage as $d)
                <tr>
                    <td class="kb-admin-mono">{{ $d->discount_code }}</td>
                    <td>{{ $d->uses }}</td>
                    <td>{{ $d->uses }} redemptions</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Reviews Summary --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-star"></i> Reviews Summary</h3>
    <div class="kb-analytics-mini-stats">
        <div class="kb-analytics-mini-stat">
            <span class="kb-analytics-mini-stat-value">{{ $reviewStats['total'] }}</span>
            <span class="kb-analytics-mini-stat-label">Total Reviews</span>
        </div>
        <div class="kb-analytics-mini-stat">
            <span class="kb-analytics-mini-stat-value">{{ $reviewStats['approved'] }}</span>
            <span class="kb-analytics-mini-stat-label">Approved</span>
        </div>
        <div class="kb-analytics-mini-stat">
            <span class="kb-analytics-mini-stat-value">{{ $reviewStats['pending'] }}</span>
            <span class="kb-analytics-mini-stat-label">Pending</span>
        </div>
        <div class="kb-analytics-mini-stat">
            <span class="kb-analytics-mini-stat-value">{{ $reviewStats['avg'] }} <i class="bi bi-star-fill" style="color:#f59e0b;font-size:0.8em;"></i></span>
            <span class="kb-analytics-mini-stat-label">Avg Rating</span>
        </div>
    </div>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const chartColors = {
    primary: '#6366f1',
    primaryLight: 'rgba(99,102,241,0.15)',
    green: '#22c55e',
    greenLight: 'rgba(34,197,94,0.15)',
    orange: '#f59e0b',
    red: '#ef4444',
    blue: '#3b82f6',
    purple: '#a855f7',
    grey: '#6b7280',
};

const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
const textColor = isDark ? '#ccc' : '#666';

Chart.defaults.color = textColor;
Chart.defaults.borderColor = gridColor;

// ── Revenue Chart ──
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($revenueByDay->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))) !!},
        datasets: [{
            label: 'Revenue (£)',
            data: {!! json_encode($revenueByDay->pluck('revenue')->map(fn($v) => round($v / 100, 2))) !!},
            borderColor: chartColors.primary,
            backgroundColor: chartColors.primaryLight,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { callback: v => '£' + v.toLocaleString() } }
        }
    }
});

// ── Orders by Status Chart ──
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($ordersByStatus->keys()) !!},
        datasets: [{
            data: {!! json_encode($ordersByStatus->values()) !!},
            backgroundColor: [chartColors.orange, chartColors.green, chartColors.primary, chartColors.red, chartColors.grey],
            borderWidth: 2,
            borderColor: isDark ? '#1a1a1a' : '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        cutout: '55%',
    }
});

// ── New Customers Chart ──
new Chart(document.getElementById('customersChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($newCustomers->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))) !!},
        datasets: [{
            label: 'New Customers',
            data: {!! json_encode($newCustomers->pluck('count')) !!},
            backgroundColor: chartColors.greenLight,
            borderColor: chartColors.green,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// ── Revenue by Country Chart ──
new Chart(document.getElementById('countryChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($revenueByCountry->pluck('ship_country_code')) !!},
        datasets: [{
            data: {!! json_encode($revenueByCountry->pluck('total')->map(fn($v) => round($v / 100, 2))) !!},
            backgroundColor: [chartColors.primary, chartColors.green, chartColors.orange, chartColors.blue, chartColors.purple, chartColors.red],
            borderWidth: 2,
            borderColor: isDark ? '#1a1a1a' : '#fff',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        cutout: '55%',
    }
});
</script>
@endsection
