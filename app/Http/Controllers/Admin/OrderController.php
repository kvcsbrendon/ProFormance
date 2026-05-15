<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\UserMessage;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('ship_recipient_name', 'like', "%{$s}%")
                  ->orWhere('bill_recipient_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $orders = $query->paginate(20)->withQueryString();
        return view('admin.orders.index', compact('orders'));
    }

    public function show($orderId)
    {
        $order = Order::with('items')->where('order_id', $orderId)->firstOrFail();
        $user = User::find($order->user_id);
        return view('admin.orders.show', compact('order', 'user'));
    }

    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'order_status' => 'required|in:Pending,Paid,Processing,Shipped,Delivered,Fulfilled,Cancelled,Refunded'
        ]);

        $order = Order::where('order_id', $orderId)->firstOrFail();
        $old = $order->order_status;
        $order->order_status = $request->order_status;
        $order->save();

        // Notify customer
        if ($order->user_id) {
            $msgs = [
                'Paid'      => 'Your payment has been confirmed.',
                'Fulfilled' => 'Your order has been dispatched!',
                'Cancelled' => 'Your order has been cancelled.',
                'Refunded'  => 'Your order has been refunded.',
            ];
            if (isset($msgs[$request->order_status])) {
                UserMessage::send(
                    $order->user_id,
                    UserMessage::CAT_ORDER,
                    "Order {$order->order_number} — {$request->order_status}",
                    $msgs[$request->order_status],
                    route('account.orders.show', $order->order_id),
                    'View Order'
                );
            }
        }

        return back()->with('success', "Status updated: {$old} → {$request->order_status}");
    }
}
