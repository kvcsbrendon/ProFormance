<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Address;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Download a PDF invoice for the given order.
     */
    public function download(Order $order)
    {
        // Ensure the order belongs to the logged-in user
        if (!Auth::check() || $order->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $order->load('items');

        $symbol = strtoupper($order->currency_code) === 'GBP' ? '£'
                : (strtoupper($order->currency_code) === 'USD' ? '$'
                : (strtoupper($order->currency_code) === 'EUR' ? '€' : $order->currency_code . ' '));

        // Detect gift status from order items
        $hasGiftItems    = $order->items->where('is_gift', true)->isNotEmpty();
        $hasNonGiftItems = $order->items->where('is_gift', false)->isNotEmpty();
        $isMixedOrder    = $hasGiftItems && $hasNonGiftItems;
        $isPureGift      = $hasGiftItems && !$hasNonGiftItems;

        $data = [
            'order'           => $order,
            'items'           => $order->items,
            'symbol'          => $symbol,
            'user'            => Auth::user(),
            'hasGiftItems'    => $hasGiftItems,
            'hasNonGiftItems' => $hasNonGiftItems,
            'isMixedOrder'    => $isMixedOrder,
            'isPureGift'      => $isPureGift,
        ];

        $pdf = Pdf::loadView('invoices.invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'ProFormance-Invoice-' . $order->order_number . '.pdf';

        return $pdf->download($filename);
    }
}