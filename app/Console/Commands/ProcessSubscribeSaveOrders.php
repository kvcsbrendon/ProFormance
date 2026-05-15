<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\SubscribeSaveItem;
use App\Models\SubscribeSaveSettings;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\Address;
use App\Models\SavedCard;
use App\Models\UserMessage;
use App\Models\VariantCurrencyPrice;

class ProcessSubscribeSaveOrders extends Command
{
    protected $signature = 'ss:process';
    protected $description = 'Place orders for Subscribe & Save items that are due for delivery';

    public function handle()
    {
        $discountPercent = SubscribeSaveSettings::discountPercent();

        // Find all active, non-suspended items where next_delivery_at is today or past
        $dueItems = SubscribeSaveItem::where('is_active', true)
            ->whereNull('suspended_at')
            ->where('next_delivery_at', '<=', now())
            ->with(['user', 'variant.product', 'variant.inventory'])
            ->get();

        // Group by user so we create one order per user
        $grouped = $dueItems->groupBy('user_id');

        $ordersPlaced = 0;
        $itemsProcessed = 0;
        $failures = 0;

        foreach ($grouped as $userId => $items) {
            $user = $items->first()->user;
            if (!$user) continue;

            // Get user's default shipping address
            $address = Address::where('user_id', $userId)
                ->where('is_default_shipping_address', true)
                ->first();

            if (!$address) {
                // Skip — no address
                foreach ($items as $item) {
                    $this->warn("User {$userId}: No default address, skipping item {$item->ss_item_id}");
                }
                $failures += $items->count();
                continue;
            }

            // Get user's default card
            $card = SavedCard::where('user_id', $userId)
                ->where('is_default', true)
                ->where('expiry_year', '>=', now()->year)
                ->first();

            if (!$card) {
                // Notify user
                UserMessage::send(
                    $userId,
                    'order',
                    'Subscribe & Save Delivery Failed',
                    "We couldn't process your Subscribe & Save delivery because no valid payment card was found. "
                        . "Please update your payment details.",
                    route('account.subscription'),
                    'Manage Subscription'
                );
                $failures += $items->count();
                continue;
            }

            // Build the order
            try {
                $this->placeOrder($user, $items, $address, $card, $discountPercent);
                $ordersPlaced++;
                $itemsProcessed += $items->count();
            } catch (\Exception $e) {
                $this->error("User {$userId}: Order failed — {$e->getMessage()}");
                $failures += $items->count();
            }
        }

        $this->info("S&S Orders: {$ordersPlaced} placed ({$itemsProcessed} items), {$failures} failed.");
    }

    private function placeOrder($user, $items, $address, $card, int $discountPercent): void
    {
        DB::transaction(function () use ($user, $items, $address, $card, $discountPercent) {

            $subtotalExVatPenny = 0;
            $totalVatPenny = 0;
            $orderItems = [];
            $countryCode = $address->country_code ?? 'GB';
            $vatRate = ($countryCode === 'GB') ? 0.20 : 0.00;

            foreach ($items as $ssItem) {
                $variant = $ssItem->variant;
                if (!$variant) continue;

                // Get price
                $priceRow = VariantCurrencyPrice::where('variant_id', $variant->variant_id)
                    ->where('currency_code', 'GBP')
                    ->first();

                $priceInclVat = $priceRow ? $priceRow->price_penny / 100 : 0;
                $basePrice = $priceInclVat / 1.20; // Extract ex-VAT
                $qty = $ssItem->quantity;

                // Apply S&S discount
                $discountedBase = $basePrice * (1 - ($discountPercent / 100));
                $lineExVat = $discountedBase * $qty;
                $lineVat = ($vatRate > 0) ? $lineExVat * $vatRate : 0;

                $subtotalExVatPenny += (int) round($lineExVat * 100);
                $totalVatPenny += (int) round($lineVat * 100);

                $orderItems[] = [
                    'ssItem'       => $ssItem,
                    'variant'      => $variant,
                    'qty'          => $qty,
                    'ex_vat_penny' => (int) round($lineExVat * 100),
                    'vat_penny'    => (int) round($lineVat * 100),
                    'vat_rate'     => $vatRate,
                ];

                // Check stock
                $inv = $variant->inventory;
                if ($inv && $inv->available_stock < $qty) {
                    throw new \Exception("Insufficient stock for {$variant->sku}");
                }
            }

            // Shipping — use standard rate
            $shippingPenny = \App\Models\ShippingRate::getPenny($countryCode, 'standard');
            $shippingVat = ($vatRate > 0) ? (int) round($shippingPenny * $vatRate) : 0;

            $combinedVat = $totalVatPenny + $shippingVat;
            $totalPenny = $subtotalExVatPenny + $shippingPenny + $combinedVat;

            // Create order
            $order = Order::create([
                'order_number'   => 'SS-' . strtoupper(Str::random(10)),
                'user_id'        => $user->user_id,
                'currency_code'  => 'GBP',
                'order_status'   => 'Paid',
                'subtotal_penny' => $subtotalExVatPenny,
                'shipping_penny' => $shippingPenny,
                'tax_penny'      => $combinedVat,
                'discount_penny' => 0,
                'total_penny'    => $totalPenny,
                'order_notes'    => 'Subscribe & Save auto-delivery',
                'shipping_method' => 'standard',
                'ship_recipient_name'   => $address->recipient_name,
                'ship_house_number'     => $address->house_number,
                'ship_address_line_one' => $address->address_line_one,
                'ship_address_line_two' => $address->address_line_two,
                'ship_city'             => $address->city,
                'ship_county'           => $address->county,
                'ship_postcode'         => $address->postcode,
                'ship_country_code'     => $address->country_code,
                'ship_phone_number'     => $address->phone_number,
                'shipping_address_id'   => $address->address_id,
                'bill_recipient_name'   => $address->recipient_name,
                'bill_house_number'     => $address->house_number,
                'bill_address_line_one' => $address->address_line_one,
                'bill_address_line_two' => $address->address_line_two,
                'bill_city'             => $address->city,
                'bill_county'           => $address->county,
                'bill_postcode'         => $address->postcode,
                'bill_country_code'     => $address->country_code,
                'bill_phone_number'     => $address->phone_number,
                'billing_address_id'    => $address->address_id,
            ]);

            // Create order items + update inventory
            foreach ($orderItems as $oi) {
                OrderItem::create([
                    'order_id'         => $order->order_id,
                    'variant_id'       => $oi['variant']->variant_id,
                    'sku'              => $oi['variant']->sku ?? 'UNKNOWN',
                    'title'            => $oi['variant']->product->product_name ?? 'Product',
                    'unit_price_penny' => (int) round($oi['ex_vat_penny'] / $oi['qty']),
                    'quantity'         => $oi['qty'],
                    'tax_rate'         => $oi['vat_rate'],
                    'tax_penny'        => $oi['vat_penny'],
                    'is_gift'          => 0,
                ]);

                // Update inventory
                $inv = Inventory::where('variant_id', $oi['variant']->variant_id)->first();
                if ($inv) {
                    $inv->available_stock = max(0, $inv->available_stock - $oi['qty']);
                    $inv->save();
                }

                // Update next delivery date
                $oi['ssItem']->update([
                    'next_delivery_at' => now()->addWeeks($oi['ssItem']->frequency_weeks),
                ]);
            }

            // Payment record
            DB::table('payments')->insert([
                'order_id'      => $order->order_id,
                'provider_id'   => ($card->card_brand === 'visa') ? 3 : 4,
                'provider_ref'  => 'SS-' . strtoupper(Str::random(12)),
                'amount_penny'  => $totalPenny,
                'currency_code' => 'GBP',
                'return_status' => 'Authorised',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Notify user
            $itemCount = count($orderItems);
            $amount = '£' . number_format($totalPenny / 100, 2);
            $cardLabel = ucfirst($card->card_brand) . ' •••• ' . $card->last_four;

            UserMessage::send(
                $user->user_id,
                UserMessage::CAT_ORDER ?? 'order',
                'Subscribe & Save Order — ' . $order->order_number,
                "Your Subscribe & Save delivery of {$itemCount} item(s) has been placed! "
                    . "Total: {$amount}, charged to {$cardLabel}. "
                    . "Your items will be shipped to {$address->city}, {$address->postcode}.",
                route('account.orders.show', $order->order_id),
                'View Order'
            );
        });
    }
}
