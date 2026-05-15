<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Review;
use App\Models\CustomerContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Main analytics dashboard with charts.
     */
    public function index(Request $request)
    {
        $range = $request->input('range', '30'); // days
        $startDate = now()->subDays((int) $range)->startOfDay();
        $endDate = now()->endOfDay();

        // ── Revenue over time ──
        $revenueByDay = Order::where('created_at', '>=', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_penny) as revenue'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Summary stats ──
        $totalRevenue = $revenueByDay->sum('revenue');
        $totalOrders = $revenueByDay->sum('order_count');
        $avgOrderValue = $totalOrders > 0 ? (int) ($totalRevenue / $totalOrders) : 0;

        // Previous period comparison
        $prevStart = $startDate->copy()->subDays((int) $range);
        $prevRevenue = Order::where('created_at', '>=', $prevStart)
            ->where('created_at', '<', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->sum('total_penny');
        $revenueChange = $prevRevenue > 0
            ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($totalRevenue > 0 ? 100 : 0);

        $prevOrders = Order::where('created_at', '>=', $prevStart)
            ->where('created_at', '<', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->count();
        $ordersChange = $prevOrders > 0
            ? round((($totalOrders - $prevOrders) / $prevOrders) * 100, 1)
            : ($totalOrders > 0 ? 100 : 0);

        // ── Orders by status ──
        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status');

        // ── Top products ──
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.variant_id', '=', 'order_items.variant_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->where('orders.created_at', '>=', $startDate)
            ->whereIn('orders.order_status', ['Paid', 'Fulfilled'])
            ->select(
                'products.product_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.unit_price_penny * order_items.quantity) as total_revenue')
            )
            ->groupBy('products.product_id', 'products.product_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ── New customers over time ──
        $newCustomers = User::where('created_at', '>=', $startDate)
            ->where('user_role', 'customer')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalNewCustomers = $newCustomers->sum('count');

        // ── Revenue by payment method ──
        $revenueByPayment = DB::table('payments')
            ->join('orders', 'orders.order_id', '=', 'payments.order_id')
            ->join('payment_providers', 'payment_providers.provider_id', '=', 'payments.provider_id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('payments.return_status', 'Authorised')
            ->select('payment_providers.display_name as provider_name', DB::raw('SUM(payments.amount_penny) as total'))
            ->groupBy('payment_providers.display_name')
            ->get();

        // ── Revenue by country ──
        $revenueByCountry = Order::where('created_at', '>=', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->select('ship_country_code', DB::raw('SUM(total_penny) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('ship_country_code')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Reviews stats ──
        $reviewStats = [
            'total'    => Review::where('created_at', '>=', $startDate)->count(),
            'approved' => Review::where('created_at', '>=', $startDate)->where('is_approved', true)->count(),
            'pending'  => Review::where('created_at', '>=', $startDate)->where('is_approved', false)->count(),
            'avg'      => round(Review::where('created_at', '>=', $startDate)->where('is_approved', true)->avg('rating') ?? 0, 1),
        ];

        // ── Discount usage ──
        $discountUsage = DB::table('discount_redemptions')
            ->join('discounts', 'discounts.discount_id', '=', 'discount_redemptions.discount_id')
            ->where('discount_redemptions.redeemed_at', '>=', $startDate)
            ->select('discounts.discount_code', DB::raw('COUNT(*) as uses'))
            ->groupBy('discounts.discount_id', 'discounts.discount_code')
            ->orderByDesc('uses')
            ->limit(10)
            ->get();

        return view('admin.analytics.index', compact(
            'range', 'startDate', 'endDate',
            'revenueByDay', 'totalRevenue', 'totalOrders', 'avgOrderValue',
            'revenueChange', 'ordersChange',
            'ordersByStatus', 'topProducts', 'newCustomers', 'totalNewCustomers',
            'revenueByPayment', 'revenueByCountry', 'reviewStats', 'discountUsage'
        ));
    }

    /**
     * Export analytics data as CSV.
     */
    public function exportCsv(Request $request)
    {
        $range = $request->input('range', '30');
        $type = $request->input('type', 'revenue');
        $startDate = now()->subDays((int) $range)->startOfDay();

        $filename = "proformance-{$type}-" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($type, $startDate) {
            $file = fopen('php://output', 'w');

            switch ($type) {
                case 'revenue':
                    fputcsv($file, ['Date', 'Orders', 'Revenue (£)']);
                    $data = Order::where('created_at', '>=', $startDate)
                        ->whereIn('order_status', ['Paid', 'Fulfilled'])
                        ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_penny) as revenue'))
                        ->groupBy('date')->orderBy('date')->get();
                    foreach ($data as $row) {
                        fputcsv($file, [$row->date, $row->orders, number_format($row->revenue / 100, 2)]);
                    }
                    break;

                case 'orders':
                    fputcsv($file, ['Order Number', 'Date', 'Customer', 'Status', 'Subtotal (£)', 'Shipping (£)', 'Tax (£)', 'Discount (£)', 'Total (£)']);
                    $orders = Order::where('created_at', '>=', $startDate)
                        ->with('user')
                        ->orderByDesc('created_at')
                        ->get();
                    foreach ($orders as $o) {
                        fputcsv($file, [
                            $o->order_number,
                            $o->created_at->format('Y-m-d H:i'),
                            $o->user ? $o->user->first_name . ' ' . $o->user->last_name : 'Guest',
                            $o->order_status,
                            number_format($o->subtotal_penny / 100, 2),
                            number_format($o->shipping_penny / 100, 2),
                            number_format($o->tax_penny / 100, 2),
                            number_format($o->discount_penny / 100, 2),
                            number_format($o->total_penny / 100, 2),
                        ]);
                    }
                    break;

                case 'products':
                    fputcsv($file, ['Product', 'Qty Sold', 'Revenue (£)']);
                    $products = DB::table('order_items')
                        ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
                        ->join('product_variants', 'product_variants.variant_id', '=', 'order_items.variant_id')
                        ->join('products', 'products.product_id', '=', 'product_variants.product_id')
                        ->where('orders.created_at', '>=', $startDate)
                        ->whereIn('orders.order_status', ['Paid', 'Fulfilled'])
                        ->select('products.product_name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.unit_price_penny * order_items.quantity) as rev'))
                        ->groupBy('products.product_id', 'products.product_name')
                        ->orderByDesc('rev')
                        ->get();
                    foreach ($products as $p) {
                        fputcsv($file, [$p->product_name, $p->qty, number_format($p->rev / 100, 2)]);
                    }
                    break;

                case 'customers':
                    fputcsv($file, ['Date', 'New Customers']);
                    $data = User::where('created_at', '>=', $startDate)
                        ->where('user_role', 'customer')
                        ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                        ->groupBy('date')->orderBy('date')->get();
                    foreach ($data as $row) {
                        fputcsv($file, [$row->date, $row->count]);
                    }
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export analytics report as PDF.
     */
    public function exportPdf(Request $request)
    {
        $range = $request->input('range', '30');
        $startDate = now()->subDays((int) $range)->startOfDay();

        // Gather summary data
        $totalRevenue = Order::where('created_at', '>=', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->sum('total_penny');
        $totalOrders = Order::where('created_at', '>=', $startDate)
            ->whereIn('order_status', ['Paid', 'Fulfilled'])
            ->count();
        $avgOrderValue = $totalOrders > 0 ? (int) ($totalRevenue / $totalOrders) : 0;
        $newCustomers = User::where('created_at', '>=', $startDate)->where('user_role', 'customer')->count();

        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.variant_id', '=', 'order_items.variant_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->where('orders.created_at', '>=', $startDate)
            ->whereIn('orders.order_status', ['Paid', 'Fulfilled'])
            ->select('products.product_name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.unit_price_penny * order_items.quantity) as rev'))
            ->groupBy('products.product_id', 'products.product_name')
            ->orderByDesc('rev')
            ->limit(10)
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.analytics.pdf', compact(
            'range', 'startDate', 'totalRevenue', 'totalOrders', 'avgOrderValue',
            'newCustomers', 'ordersByStatus', 'topProducts'
        ));

        return $pdf->download('proformance-report-' . now()->format('Y-m-d') . '.pdf');
    }
}
