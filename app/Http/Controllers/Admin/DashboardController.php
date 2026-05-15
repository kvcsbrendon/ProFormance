<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\CustomerContact;
use App\Models\Review;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use App\Models\ReviewReport;

class DashboardController extends Controller
{
    public function index()
    {
        $todayOrders   = Order::whereDate('created_at', today())->count();
        $todayRevenue  = Order::whereDate('created_at', today())->sum('total_penny');
        $monthOrders   = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $monthRevenue  = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_penny');
        $totalOrders   = Order::count();
        $totalRevenue  = Order::sum('total_penny');
        $pendingReports = ReviewReport::where('status', 'pending')->count();

        $ordersByStatus = Order::select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')->pluck('count', 'order_status')->toArray();
        $pendingOrders = ($ordersByStatus['Pending'] ?? 0) + ($ordersByStatus['Paid'] ?? 0);

        $totalCustomers    = User::where('user_role', 'customer')->count();
        $newCustomersMonth = User::where('user_role', 'customer')
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $totalProducts  = Product::count();
        $activeProducts = Product::where('is_active', true)->count();

        $lowStock = Inventory::where('available_stock', '>', 0)
            ->whereColumn('available_stock', '<=', DB::raw('COALESCE(reorder_point, 5)'))
            ->with('variant.product')->limit(10)->get();
        $outOfStock = Inventory::where('available_stock', '<=', 0)
            ->with('variant.product')->limit(10)->get();

        $recentOrders   = Order::with('items')->orderByDesc('created_at')->limit(10)->get();
        $pendingTickets = CustomerContact::where('contact_status', 'Pending')->count();
        $pendingReviews = Review::where('is_approved', false)->count();
        $pendingRefunds = Refund::where('refund_status', 'Pending')->count();

        return view('admin.dashboard', compact(
            'todayOrders', 'todayRevenue', 'monthOrders', 'monthRevenue',
            'totalOrders', 'totalRevenue', 'ordersByStatus', 'pendingOrders',
            'totalCustomers', 'newCustomersMonth', 'totalProducts', 'activeProducts',
            'lowStock', 'outOfStock', 'recentOrders', 'pendingTickets', 'pendingReviews',
            'pendingRefunds',
            'pendingReviews', 'pendingRefunds', 'pendingReports'
        ));
    }
}
