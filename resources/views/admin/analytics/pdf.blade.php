{{-- resources/views/admin/analytics/pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ProFormance Analytics Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; line-height: 1.5; padding: 40px; }

        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6366f1; padding-bottom: 15px; }
        .report-title { font-size: 22px; font-weight: 700; color: #1a1a1a; }
        .report-subtitle { font-size: 12px; color: #666; margin-top: 4px; }
        .report-date { font-size: 10px; color: #999; margin-top: 2px; }

        .stats-grid { display: table; width: 100%; margin-bottom: 25px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 12px 8px; border: 1px solid #e5e7eb; }
        .stat-value { font-size: 20px; font-weight: 700; color: #1a1a1a; }
        .stat-label { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        .section-title { font-size: 14px; font-weight: 700; color: #1a1a1a; margin: 25px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e5e7eb; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #f9fafb; text-align: left; padding: 8px 10px; font-size: 10px; font-weight: 600; text-transform: uppercase; color: #666; border-bottom: 2px solid #e5e7eb; }
        td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        tr:nth-child(even) td { background: #fafafa; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-grey { background: #f3f4f6; color: #374151; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">ProFormance Analytics Report</div>
        <div class="report-subtitle">Last {{ $range }} days — {{ $startDate->format('d M Y') }} to {{ now()->format('d M Y') }}</div>
        <div class="report-date">Generated: {{ now()->format('d M Y, H:i') }}</div>
    </div>

    {{-- Summary Stats --}}
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-value">&pound;{{ number_format($totalRevenue / 100, 2) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ number_format($totalOrders) }}</div>
            <div class="stat-label">Orders</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">&pound;{{ number_format($avgOrderValue / 100, 2) }}</div>
            <div class="stat-label">Avg Order Value</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ number_format($newCustomers) }}</div>
            <div class="stat-label">New Customers</div>
        </div>
    </div>

    {{-- Orders by Status --}}
    <div class="section-title">Orders by Status</div>
    <table>
        <thead><tr><th>Status</th><th class="text-right">Count</th></tr></thead>
        <tbody>
            @foreach($ordersByStatus as $status => $count)
            <tr>
                <td>
                    @switch($status)
                        @case('Paid') <span class="badge badge-green">{{ $status }}</span> @break
                        @case('Pending') <span class="badge badge-orange">{{ $status }}</span> @break
                        @case('Fulfilled') <span class="badge badge-blue">{{ $status }}</span> @break
                        @case('Cancelled') <span class="badge badge-red">{{ $status }}</span> @break
                        @case('Refunded') <span class="badge badge-grey">{{ $status }}</span> @break
                        @default <span class="badge badge-grey">{{ $status }}</span>
                    @endswitch
                </td>
                <td class="text-right">{{ $count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Top Products --}}
    <div class="section-title">Top Selling Products</div>
    <table>
        <thead><tr><th>#</th><th>Product</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th></tr></thead>
        <tbody>
            @forelse($topProducts as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $p->product_name }}</td>
                <td class="text-right">{{ number_format($p->qty) }}</td>
                <td class="text-right">&pound;{{ number_format($p->rev / 100, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center" style="color:#999;">No sales data for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        ProFormance &copy; {{ date('Y') }} — Confidential Business Report
    </div>
</body>
</html>
