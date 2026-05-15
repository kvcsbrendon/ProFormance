<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;
use App\Models\StockNotification;
use App\Models\ProductVariant;

class StockNotificationController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,variant_id',
            'email'      => 'required|email|max:255',
        ]);

        $exists = StockNotification::where('variant_id', $request->variant_id)
            ->where('email', $request->email)
            ->where('notified', false)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'You\'re already signed up for this notification.',
            ]);
        }

        StockNotification::create([
            'variant_id' => $request->variant_id,
            'email'      => $request->email,
            'user_id'    => auth()->id(),
            'notified'   => false,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You\'ll be notified when this item is back in stock.',
        ]);
    }

    public static function sendBackInStockEmails(int $variantId): int
    {
        $variant = ProductVariant::with('product')->find($variantId);
        if (!$variant) return 0;

        $subscribers = StockNotification::where('variant_id', $variantId)
            ->where('notified', false)
            ->get();

        $count = 0;

        foreach ($subscribers as $sub) {
            try {
                Mail::send('emails.back-in-stock', [
                    'productName' => $variant->product->product_name,
                    'variantName' => $variant->title,
                    'productUrl'  => url("/products/{$variant->product_id}"),
                    'email'       => $sub->email,
                ], function ($message) use ($sub) {
                    $message->to($sub->email)
                            ->subject('It\'s Back! Your item is in stock again');
                });

                $sub->notified    = true;
                $sub->notified_at = now();
                $sub->save();
                $count++;

            } catch (\Exception $e) {
                \Log::error("Back-in-stock email failed for {$sub->email}: {$e->getMessage()}");
            }
        }

        return $count;
    }
}
