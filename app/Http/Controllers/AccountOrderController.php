<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Address;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RefundItem;

class AccountOrderController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->paginate(10);

        return view('account.orders', compact('orders'));
    }

    public function show($orderId)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->user_id)
            ->where('order_id', $orderId)
            ->with('items')
            ->firstOrFail();

        // Detect gift status from order items
        $giftItems    = $order->items->where('is_gift', true);
        $nonGiftItems = $order->items->where('is_gift', false);

        $hasGiftItems = $giftItems->isNotEmpty();
        $hasNonGiftItems = $nonGiftItems->isNotEmpty();
        $isMixedOrder = $hasGiftItems && $hasNonGiftItems;
        $isPureGift   = $hasGiftItems && !$hasNonGiftItems;

        // Get ALL refund requests for this order
        $allRefunds = Refund::where('order_id', $order->order_id)
            ->with('items.orderItem')
            ->orderByDesc('created_at')
            ->get();

        $hasPendingRefund = $allRefunds->where('refund_status', 'Pending')->isNotEmpty();

        // Calculate already-refunded quantities per order_item_id (succeeded + pending)
        $refundedQty = [];
        foreach ($allRefunds->whereIn('refund_status', ['Succeeded', 'Pending']) as $ref) {
            foreach ($ref->items as $ri) {
                $refundedQty[$ri->order_item_id] = ($refundedQty[$ri->order_item_id] ?? 0) + $ri->quantity;
            }
        }

        // Check if there are any items left to refund
        $hasRefundableItems = false;
        foreach ($order->items as $item) {
            $alreadyRefunded = $refundedQty[$item->order_item_id] ?? 0;
            if ($item->quantity - $alreadyRefunded > 0) {
                $hasRefundableItems = true;
                break;
            }
        }

        // Can request if: eligible status, not already pending, and items remain
        $canRequestRefund = in_array($order->order_status, ['Paid', 'Fulfilled', 'Refunded'])
            && !$hasPendingRefund
            && $hasRefundableItems;

        return view('account.order-detail', compact(
            'order',
            'hasGiftItems',
            'hasNonGiftItems',
            'isMixedOrder',
            'isPureGift',
            'giftItems',
            'nonGiftItems',
            'allRefunds',
            'hasPendingRefund',
            'hasRefundableItems',
            'canRequestRefund',
            'refundedQty'
        ));
    }

    /**
     * Submit an item-level refund request.
     */
    public function requestRefund(Request $request, $orderId)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->user_id)
            ->where('order_id', $orderId)
            ->with('items')
            ->firstOrFail();

        // Validate order is eligible
        if (!in_array($order->order_status, ['Paid', 'Fulfilled', 'Refunded'])) {
            return back()->withErrors(['refund' => 'This order is not eligible for a refund.']);
        }

        // Check for existing pending refund
        $existingPending = Refund::where('order_id', $order->order_id)
            ->where('refund_status', 'Pending')
            ->exists();

        if ($existingPending) {
            return back()->withErrors(['refund' => 'A refund request is already pending for this order.']);
        }

        $data = $request->validate([
            'reason'           => 'required|string|max:1000',
            'refund_items'     => 'required|array|min:1',
            'refund_items.*'   => 'integer|min:1',
        ], [
            'refund_items.required' => 'Please select at least one item to refund.',
            'refund_items.min'      => 'Please select at least one item to refund.',
        ]);

        // Build already-refunded quantities (succeeded + pending)
        $refundedQty = [];
        $existingRefunds = Refund::where('order_id', $order->order_id)
            ->whereIn('refund_status', ['Succeeded', 'Pending'])
            ->with('items')
            ->get();

        foreach ($existingRefunds as $ref) {
            foreach ($ref->items as $ri) {
                $refundedQty[$ri->order_item_id] = ($refundedQty[$ri->order_item_id] ?? 0) + $ri->quantity;
            }
        }

        // Validate selected items belong to this order and quantities are valid
        $orderItemsById = $order->items->keyBy('order_item_id');
        $validItems = [];
        $totalRefundPenny = 0;

        foreach ($data['refund_items'] as $orderItemId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) continue;

            $orderItem = $orderItemsById->get($orderItemId);
            if (!$orderItem) continue;

            // Check remaining refundable quantity
            $alreadyRefunded = $refundedQty[$orderItemId] ?? 0;
            $maxRefundable = $orderItem->quantity - $alreadyRefunded;

            if ($maxRefundable <= 0) continue;

            $qty = min($qty, $maxRefundable);

            $validItems[] = [
                'order_item_id' => $orderItemId,
                'quantity'      => $qty,
            ];

            // Calculate item cost (unit price * qty) + proportional VAT
            $itemNet = $orderItem->unit_price_penny * $qty;
            $itemVat = (int) round($itemNet * ($orderItem->tax_rate ?? 0));
            $totalRefundPenny += $itemNet + $itemVat;
        }

        if (empty($validItems)) {
            return back()->withErrors(['refund' => 'Please select at least one item with a valid quantity.']);
        }

        // Find payment for this order
        $payment = Payment::where('order_id', $order->order_id)->first();

        // Create the refund
        $refund = Refund::create([
            'order_id'      => $order->order_id,
            'payment_id'    => $payment?->payment_id ?? 0,
            'amount_penny'  => $totalRefundPenny,
            'refund_status' => 'Pending',
            'reason'        => $data['reason'],
        ]);

        // Create refund items
        foreach ($validItems as $vi) {
            RefundItem::create([
                'refund_id'     => $refund->refund_id,
                'order_item_id' => $vi['order_item_id'],
                'quantity'      => $vi['quantity'],
            ]);
        }

        return back()->with('success', 'Your refund request has been submitted. We\'ll review it and get back to you.');
    }

    /**
     * Reorder — add all items from a previous order back to the cart.
     */
    public function reorder($orderId)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->user_id)
            ->where('order_id', $orderId)
            ->with('items')
            ->firstOrFail();

        $cart = session('cart', []);
        $added = 0;

        foreach ($order->items as $item) {
            // Check variant still exists
            $variant = \App\Models\ProductVariant::find($item->variant_id);
            if (!$variant) continue;

            // Check if already in cart
            $found = false;
            foreach ($cart as $idx => $line) {
                if (($line['variant_id'] ?? null) == $item->variant_id) {
                    $cart[$idx]['quantity'] += $item->quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $product = $variant->product;
                $image = \Illuminate\Support\Facades\DB::table('product_images')
                    ->where('variant_id', $variant->variant_id)
                    ->orderBy('sort_order')
                    ->value('image_url');

                $priceRow = \App\Models\VariantCurrencyPrice::where('variant_id', $variant->variant_id)
                    ->where('currency_code', session('currency', 'gbp'))
                    ->first();

                $existingIds = array_column($cart, 'id');
                $newId = empty($existingIds) ? 1 : (max($existingIds) + 1);

                $cart[] = [
                    'id'         => $newId,
                    'variant_id' => $variant->variant_id,
                    'product_id' => $variant->product_id,
                    'name'       => $product->product_name ?? $item->title,
                    'image'      => $image ? ('images/' . $image) : null,
                    'price'      => $priceRow->price ?? 0,
                    'quantity'   => $item->quantity,
                    'currency'   => session('currency', 'gbp'),
                ];
            }

            $added++;
        }

        session(['cart' => $cart]);

        if ($added === 0) {
            return back()->withErrors(['reorder' => 'None of the items from this order are currently available.']);
        }

        return redirect()->route('cart.index')
            ->with('success', "{$added} item(s) added to your basket from order {$order->order_number}.");
    }
}
