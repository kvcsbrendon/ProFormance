<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockNotification;
use App\Models\ProductVariant;
use App\Models\Product;

class StockNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = StockNotification::with(['variant.product', 'user'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhereHas('variant.product', function($pq) use ($search) {
                      $pq->where('product_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('variant', function($vq) use ($search) {
                      $vq->where('title', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter (notified / pending)
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('notified', false);
            } elseif ($request->status === 'notified') {
                $query->where('notified', true);
            }
        }

        // Product filter
        if ($request->filled('product_id')) {
            $query->whereHas('variant', function($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $notifications = $query->paginate(20)->withQueryString();

        // Statistics
        $totalPending = StockNotification::where('notified', false)->count();
        $totalNotified = StockNotification::where('notified', true)->count();
        $totalSubscribers = StockNotification::count();
        
        // Products for filter dropdown
        $products = Product::orderBy('product_name')->get(['product_id', 'product_name']);

        return view('admin.stock-notifications.index', compact(
            'notifications', 
            'totalPending', 
            'totalNotified',
            'totalSubscribers',
            'products'
        ));
    }

    public function show($id)
    {
        $notification = StockNotification::with(['variant.product', 'user'])
            ->findOrFail($id);

        return view('admin.stock-notifications.show', compact('notification'));
    }

    public function destroy($id)
    {
        $notification = StockNotification::findOrFail($id);
        $notification->delete();

        return redirect()->route('admin.stock-notifications.index')
            ->with('success', 'Stock notification deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:stock_notifications,notification_id'
        ]);

        StockNotification::whereIn('notification_id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($request->ids) . ' notification(s) deleted successfully.'
        ]);
    }

    public function markAsNotified($id)
    {
        $notification = StockNotification::findOrFail($id);
        
        if (!$notification->notified) {
            $notification->notified = true;
            $notification->notified_at = now();
            $notification->save();
        }

        return redirect()->back()->with('success', 'Notification marked as sent.');
    }

    public function triggerEmails(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,variant_id'
        ]);

        $count = \App\Http\Controllers\StockNotificationController::sendBackInStockEmails($request->variant_id);

        return redirect()->back()->with('success', "Back-in-stock emails sent to {$count} subscriber(s).");
    }

    public function productVariants(Request $request)
    {
        $variants = ProductVariant::where('product_id', $request->product_id)
            ->withCount(['stockNotifications' => function($q) {
                $q->where('notified', false);
            }])
            ->get(['variant_id', 'title', 'sku', 'stock_quantity']);

        return response()->json($variants);
    }
}