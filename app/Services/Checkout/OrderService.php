<?php

namespace App\Services\Checkout;

use App\Models\Address;
use App\Models\Discount;
use App\Models\DiscountRedemption;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\SavedCard;
use App\Models\UserMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Place a complete order inside a DB transaction.
     *
     * Returns the created Order model.
     */
    public function placeOrder(array $params): Order
    {
        $user               = $params['user'];
        $cart               = $params['cart'];
        $shipping           = $params['shipping'];
        $billing            = $params['billing'];
        $pricing            = $params['pricing'];       // from PricingService::calculateAll()
        $paymentMethod      = $params['paymentMethod'];  // 'card' | 'paypal'
        $paymentData        = $params['paymentData'];    // card fields or saved_card_id
        $giftOrders         = $params['giftOrders'];
        $currency           = $params['currency'];
        $orderNotes         = $params['orderNotes'] ?? null;
        $saveAddress        = $params['saveAddress'] ?? false;
        $saveCard           = $params['saveCard'] ?? false;
        $usingSavedAddress  = $params['usingSavedAddress'] ?? false;

        $order = null;

        DB::transaction(function () use (
            &$order, $user, $cart, $shipping, $billing, $pricing,
            $paymentMethod, $paymentData, $giftOrders, $currency,
            $orderNotes, $saveAddress, $saveCard, $usingSavedAddress
        ) {

            // ── Create the order ──
            $order = Order::create([
                'order_number'   => 'ORD-' . strtoupper(Str::random(10)),
                'user_id'        => $user->user_id,
                'currency_code'  => strtoupper($currency),
                'order_status'   => 'Paid',

                'subtotal_penny' => $pricing['subtotalExVatPenny'],
                'shipping_penny' => $pricing['shippingNetPenny'],
                'tax_penny'      => $pricing['combinedVatPenny'],
                'discount_penny' => $pricing['discountPenny'],
                'total_penny'    => $pricing['totalPenny'],

                'order_notes'    => $orderNotes,
                'shipping_method' => $pricing['shippingMethod'] ?? 'standard',

                'ship_recipient_name'   => $shipping['recipient_name'],
                'ship_house_number'     => $shipping['house_number'],
                'ship_address_line_one' => $shipping['address_line_one'],
                'ship_address_line_two' => $shipping['address_line_two'],
                'ship_city'             => $shipping['city'],
                'ship_county'           => $shipping['county'],
                'ship_postcode'         => $shipping['postcode'],
                'ship_country_code'     => $shipping['country_code'],
                'ship_phone_number'     => $shipping['phone_number'],
                'shipping_address_id'   => $shipping['address_id'] ?? null,

                'bill_recipient_name'   => $billing['recipient_name'],
                'bill_house_number'     => $billing['house_number'],
                'bill_address_line_one' => $billing['address_line_one'],
                'bill_address_line_two' => $billing['address_line_two'],
                'bill_city'             => $billing['city'],
                'bill_county'           => $billing['county'],
                'bill_postcode'         => $billing['postcode'],
                'bill_country_code'     => $billing['country_code'],
                'bill_phone_number'     => $billing['phone_number'],
                'billing_address_id'    => $billing['address_id'] ?? null,
            ]);

            // ── Order items + inventory ──
            foreach ($pricing['itemsBreakdown'] as $itemData) {
                $line = $itemData['line'];
                $qty  = (int) ($line['quantity'] ?? 1);

                $sku = 'UNKNOWN';
                if (!empty($line['variant_id'])) {
                    $variant = ProductVariant::find($line['variant_id']);
                    if ($variant) {
                        $sku = $variant->sku;
                    }
                }

                OrderItem::create([
                    'order_id'         => $order->order_id,
                    'variant_id'       => $line['variant_id'] ?? null,
                    'sku'              => $sku,
                    'title'            => $line['name'] ?? 'Untitled',
                    'unit_price_penny' => $itemData['ex_vat_penny'] / $qty,
                    'quantity'         => $qty,
                    'tax_rate'         => $itemData['vat_rate'],
                    'tax_penny'        => $itemData['vat_penny'],
                    'is_gift'          => isset($giftOrders[(string) ($line['variant_id'] ?? '')]) ? 1 : 0,
                ]);

                // Update inventory
                $variantId = $line['variant_id'] ?? null;
                if ($variantId) {
                    $inventory = Inventory::where('variant_id', $variantId)->first();
                    if ($inventory) {
                        $inventory->available_stock = max(0, $inventory->available_stock - $qty);
                        $inventory->save();
                    }
                }
            }

            // ── Discount redemption ──
            if ($pricing['discount'] && $pricing['discountPenny'] > 0) {
                DiscountRedemption::create([
                    'discount_id' => $pricing['discount']->discount_id,
                    'user_id'     => $user->user_id,
                    'order_id'    => $order->order_id,
                    'redeemed_at' => now(),
                ]);

                DB::table('order_discounts')->insert([
                    'order_id'             => $order->order_id,
                    'discount_id'          => $pricing['discount']->discount_id,
                    'amount_applied_penny' => $pricing['discountPenny'],
                ]);
            }

            // ── Payment record ──
            $providerMap = [
                'visa'       => 3,
                'mastercard' => 4,
                'amex'       => 2,
                'paypal'     => 1,
            ];

            DB::table('payments')->insert([
                'order_id'      => $order->order_id,
                'provider_id'   => $providerMap[$paymentMethod] ?? 3,
                'provider_ref'  => 'SIM-' . strtoupper(Str::random(12)),
                'amount_penny'  => $pricing['totalPenny'],
                'currency_code' => strtoupper($currency),
                'return_status' => 'Authorised',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // ── Save address if requested ──
            if ($saveAddress && !$usingSavedAddress) {
                Address::create([
                    'user_id'            => $user->user_id,
                    'recipient_name'     => $shipping['recipient_name'],
                    'house_number'       => $shipping['house_number'],
                    'address_line_one'   => $shipping['address_line_one'],
                    'address_line_two'   => $shipping['address_line_two'],
                    'city'               => $shipping['city'],
                    'county'             => $shipping['county'],
                    'postcode'           => $shipping['postcode'],
                    'country_code'       => $shipping['country_code'],
                    'country_phone_code' => $user->country_phone_code ?? 44,
                    'phone_number'       => $shipping['phone_number'],
                ]);
            }

            // ── Save card if requested ──
            if ($saveCard && $paymentMethod === 'card' && !empty($paymentData['card_number'])) {
                $this->saveCard($user, $paymentData);
            }
        });

        // ── Order confirmation message (outside transaction) ──
        $this->sendConfirmation($user, $order);

        return $order;
    }

    /**
     * Save card details (deduplicated).
     */
    protected function saveCard($user, array $paymentData): void
    {
        $cardNumber = preg_replace('/\D/', '', $paymentData['card_number'] ?? '');
        $lastFour   = substr($cardNumber, -4);
        $brand      = SavedCard::detectBrand($cardNumber);
        $expParts   = explode('/', $paymentData['card_expiry'] ?? '');
        $expMonth   = (int) ($expParts[0] ?? 0);
        $expYear    = (int) ($expParts[1] ?? 0);
        if ($expYear < 100) {
            $expYear += 2000;
        }

        $exists = SavedCard::where('user_id', $user->user_id)
            ->where('last_four', $lastFour)
            ->where('expiry_month', $expMonth)
            ->where('expiry_year', $expYear)
            ->exists();

        if (!$exists) {
            SavedCard::create([
                'user_id'      => $user->user_id,
                'card_brand'   => $brand,
                'last_four'    => $lastFour,
                'card_name'    => $paymentData['card_name'] ?? null,
                'expiry_month' => $expMonth,
                'expiry_year'  => $expYear,
                'is_default'   => SavedCard::where('user_id', $user->user_id)->count() === 0,
            ]);
        }
    }

    /**
     * Send the order confirmation message.
     */
    protected function sendConfirmation($user, Order $order): void
    {
        $itemCount = $order->items()->count();

        UserMessage::send(
            $user->user_id,
            UserMessage::CAT_ORDER,
            'Order Confirmed — ' . $order->order_number,
            "Thank you for your order! We've received your order of {$itemCount} item(s) totalling £"
                . number_format($order->total_penny / 100, 2)
                . ". We'll notify you when it ships.",
            route('account.orders.show', $order->order_id),
            'View Order'
        );
    }
}
