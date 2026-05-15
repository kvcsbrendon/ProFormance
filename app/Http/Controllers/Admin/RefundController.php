<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Order;
use App\Models\UserMessage;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    /**
     * List all refund requests with filters.
     */
    public function index(Request $request)
    {
        $query = Refund::with(['order'])
            ->orderByRaw("FIELD(refund_status, 'Pending', 'Succeeded', 'Rejected')")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('refund_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('order', function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%");
            });
        }

        $refunds = $query->paginate(20);

        $pendingCount = Refund::where('refund_status', 'Pending')->count();
        $totalRefunded = Refund::where('refund_status', 'Succeeded')->sum('amount_penny');

        return view('admin.refunds.index', compact('refunds', 'pendingCount', 'totalRefunded'));
    }

    /**
     * Show a single refund request with full details.
     */
    public function show($refundId)
    {
        $refund = Refund::with(['order', 'order.items', 'items.orderItem'])->findOrFail($refundId);

        // Get customer info
        $customer = null;
        if ($refund->order && $refund->order->user_id) {
            $customer = \App\Models\User::with('loginDetail')->find($refund->order->user_id);
        }

        // Other refunds for the same order
        $otherRefunds = Refund::where('order_id', $refund->order_id)
            ->where('refund_id', '!=', $refund->refund_id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.refunds.show', compact('refund', 'customer', 'otherRefunds'));
    }

    /**
     * Approve a refund — with adjustable amount (fixed or percentage).
     */
    public function approve(Request $request, $refundId)
    {
        $refund = Refund::with('order')->findOrFail($refundId);

        if ($refund->refund_status !== 'Pending') {
            return back()->withErrors(['refund' => 'This refund has already been processed.']);
        }

        $data = $request->validate([
            'refund_type'   => 'required|in:full,percentage,fixed',
            'refund_value'  => 'required_unless:refund_type,full|nullable|numeric|min:0',
            'admin_note'    => 'nullable|string|max:1000',
        ]);

        $orderTotal = $refund->order->total_penny ?? $refund->amount_penny;

        // Calculate actual refund amount
        switch ($data['refund_type']) {
            case 'percentage':
                $percentage = min(100, max(0, (float) $data['refund_value']));
                $approvedAmount = (int) round($orderTotal * ($percentage / 100));
                break;
            case 'fixed':
                $approvedAmount = (int) round(((float) $data['refund_value']) * 100);
                $approvedAmount = min($approvedAmount, $orderTotal); // can't refund more than order total
                break;
            default: // full
                $approvedAmount = $orderTotal;
                break;
        }

        $refund->update([
            'refund_status' => 'Succeeded',
            'amount_penny'  => $approvedAmount,
        ]);

        // Update order status
        $order = $refund->order;
        if ($order) {
            $order->update(['order_status' => 'Refunded']);
        }

        // Notify customer via message centre
        if ($order && $order->user_id) {
            $amountFormatted = '£' . number_format($approvedAmount / 100, 2);
            $note = !empty($data['admin_note']) ? "\n\n" . $data['admin_note'] : '';

            UserMessage::send(
                $order->user_id,
                'order',
                'Refund Approved — ' . $order->order_number,
                "Your refund request for order {$order->order_number} has been approved.\n\nRefund amount: {$amountFormatted}\n\nPlease allow 5–10 business days for the refund to appear on your statement.{$note}",
                route('account.orders.show', $order->order_id),
                'View Order'
            );
        }

        return redirect()->route('admin.refunds.show', $refund->refund_id)
            ->with('success', "Refund approved — £" . number_format($approvedAmount / 100, 2) . " refunded.");
    }

    /**
     * Reject a refund request with an optional note.
     */
    public function reject(Request $request, $refundId)
    {
        $refund = Refund::with('order')->findOrFail($refundId);

        if ($refund->refund_status !== 'Pending') {
            return back()->withErrors(['refund' => 'This refund has already been processed.']);
        }

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $refund->update([
            'refund_status' => 'Rejected',
        ]);

        // Notify customer
        $order = $refund->order;
        if ($order && $order->user_id) {
            $note = !empty($data['admin_note']) ? "\n\nReason: " . $data['admin_note'] : '';

            UserMessage::send(
                $order->user_id,
                'order',
                'Refund Request Update — ' . $order->order_number,
                "Your refund request for order {$order->order_number} has been reviewed and was not approved at this time.{$note}\n\nIf you have any questions, please contact our support team.",
                route('account.orders.show', $order->order_id),
                'View Order'
            );
        }

        return redirect()->route('admin.refunds.show', $refund->refund_id)
            ->with('success', "Refund #{$refund->refund_id} rejected.");
    }

    /**
     * Send a message to the customer about their refund.
     */
    public function reply(Request $request, $refundId)
    {
        $refund = Refund::with('order')->findOrFail($refundId);

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $order = $refund->order;
        if ($order && $order->user_id) {
            UserMessage::send(
                $order->user_id,
                'order',
                'Regarding Your Refund — ' . $order->order_number,
                $data['message'],
                route('account.orders.show', $order->order_id),
                'View Order'
            );
        }

        return back()->with('success', 'Message sent to customer.');
    }
}
