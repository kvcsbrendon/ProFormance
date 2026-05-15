<?php

namespace App\Services\Checkout;

use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\SubscribeSaveItem;
use App\Models\SubscribeSaveSettings;
use App\Models\SubscriptionPlan;
use App\Models\TaxProfile;
use App\Models\UserSubscription;

class PricingService
{
    // ─────────────────────────────────────────
    // LOW-LEVEL HELPERS
    // ─────────────────────────────────────────

    /**
     * VAT rate for a destination country.
     * UK = 20%, everything else = 0%.
     * Overridden to 0% if user is tax-exempt.
     */
    public function vatRateForCountry(string $countryCode, ?TaxProfile $taxProfile = null): float
    {
        $countryCode = strtoupper(trim($countryCode));
        $rate = ($countryCode === 'GB') ? 0.20 : 0.00;

        if ($taxProfile && (int) $taxProfile->tax_exempt === 1) {
            $rate = 0.00;
        }

        return $rate;
    }

    /**
     * Extract ex-VAT base price from a price that includes UK VAT (20%).
     */
    public function extractBasePrice(float $priceInclVat): float
    {
        return $priceInclVat / 1.20;
    }

    /**
     * Look up net shipping cost from the DB.
     */
    public function shippingPennyFor(string $countryCode, string $shippingMethod): int
    {
        return ShippingRate::getPenny($countryCode, $shippingMethod);
    }

    // ─────────────────────────────────────────
    // GIFT DETECTION
    // ─────────────────────────────────────────

    /**
     * Categorise cart lines into gift vs non-gift.
     *
     * Also cleans up stale gift_orders that no longer match the cart
     * and writes the cleaned array back to the session.
     *
     * @return array{
     *   giftItems: array,
     *   nonGiftItems: array,
     *   isGift: bool,
     *   hasMixedCart: bool,
     *   giftOrders: array
     * }
     */
    public function detectGifts(array $cart, array $giftOrders): array
    {
        $cartVariantIds = collect($cart)
            ->pluck('variant_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Remove gift orders for items no longer in the cart
        foreach ($giftOrders as $variantId => $data) {
            if (!in_array((string) $variantId, $cartVariantIds, true)) {
                unset($giftOrders[$variantId]);
            }
        }

        session(['gift_orders' => $giftOrders]);

        $giftItems = [];
        $nonGiftItems = [];

        foreach ($cart as $line) {
            $variantKey = (string) ($line['variant_id'] ?? '');
            if ($variantKey !== '' && isset($giftOrders[$variantKey])) {
                $giftItems[] = $line;
            } else {
                $nonGiftItems[] = $line;
            }
        }

        $hasGift    = !empty($giftItems);
        $hasNonGift = !empty($nonGiftItems);

        return [
            'giftItems'    => $giftItems,
            'nonGiftItems' => $nonGiftItems,
            'isGift'       => $hasGift && !$hasNonGift,
            'hasMixedCart'  => $hasGift && $hasNonGift,
            'giftOrders'   => $giftOrders,
        ];
    }

    // ─────────────────────────────────────────
    // SHIPPING DESTINATIONS
    // ─────────────────────────────────────────

    /**
     * Build the list of shipping destinations (each with a country + VAT rate).
     */
    public function buildDestinations(
        array $giftItems,
        array $nonGiftItems,
        array $giftOrders,
        string $purchaserCountry,
        ?TaxProfile $taxProfile = null
    ): array {
        $destinations = [];
        $isGift      = !empty($giftItems) && empty($nonGiftItems);
        $hasMixed    = !empty($giftItems) && !empty($nonGiftItems);

        if ($isGift) {
            $firstGift    = reset($giftOrders);
            $giftCountry  = $firstGift['country_code'] ?? 'GB';
            $destinations[] = [
                'type'     => 'gift',
                'country'  => $giftCountry,
                'vat_rate' => $this->vatRateForCountry($giftCountry, $taxProfile),
            ];
        } elseif ($hasMixed) {
            $destinations[] = [
                'type'     => 'purchaser',
                'country'  => $purchaserCountry,
                'vat_rate' => $this->vatRateForCountry($purchaserCountry, $taxProfile),
            ];

            $giftCountries = [];
            foreach ($giftItems as $item) {
                $vid = $item['variant_id'] ?? null;
                if ($vid && isset($giftOrders[$vid])) {
                    $giftCountries[] = $giftOrders[$vid]['country_code'] ?? 'GB';
                }
            }
            foreach (array_unique($giftCountries) as $country) {
                $destinations[] = [
                    'type'     => 'gift',
                    'country'  => $country,
                    'vat_rate' => $this->vatRateForCountry($country, $taxProfile),
                ];
            }
        } else {
            $destinations[] = [
                'type'     => 'purchaser',
                'country'  => $purchaserCountry,
                'vat_rate' => $this->vatRateForCountry($purchaserCountry, $taxProfile),
            ];
        }

        return $destinations;
    }

    // ─────────────────────────────────────────
    // ITEMS BREAKDOWN (per-line VAT)
    // ─────────────────────────────────────────

    /**
     * Calculate ex-VAT subtotal, VAT, and per-item breakdown.
     *
     * @return array{
     *   subtotalExVatPenny: int,
     *   totalVatPenny: int,
     *   items: array
     * }
     */
    public function calculateItemsBreakdown(
        array $cart,
        array $giftOrders,
        string $purchaserCountry,
        ?TaxProfile $taxProfile = null
    ): array {
        $subtotalExVatPenny = 0;
        $totalVatPenny      = 0;
        $items              = [];

        foreach ($cart as $line) {
            $variantId = $line['variant_id'] ?? null;
            $qty       = (int) ($line['quantity'] ?? 1);

            // Determine destination
            $itemCountry = $purchaserCountry;
            if ($variantId && isset($giftOrders[$variantId])) {
                $itemCountry = $giftOrders[$variantId]['country_code'] ?? 'GB';
            }

            $vatRate       = $this->vatRateForCountry($itemCountry, $taxProfile);
            $unitBasePrice = $this->extractBasePrice((float) ($line['price'] ?? 0));

            $lineExVat = $unitBasePrice * $qty;
            $lineVat   = ($vatRate > 0) ? ($lineExVat * $vatRate) : 0;

            $lineExVatPenny = (int) round($lineExVat * 100);
            $lineVatPenny   = (int) round($lineVat * 100);

            $subtotalExVatPenny += $lineExVatPenny;
            $totalVatPenny      += $lineVatPenny;

            $items[] = [
                'line'         => $line,
                'ex_vat_penny' => $lineExVatPenny,
                'vat_penny'    => $lineVatPenny,
                'vat_rate'     => $vatRate,
                'country'      => $itemCountry,
            ];
        }

        return [
            'subtotalExVatPenny' => $subtotalExVatPenny,
            'totalVatPenny'      => $totalVatPenny,
            'items'              => $items,
        ];
    }

    // ─────────────────────────────────────────
    // SHIPPING COST
    // ─────────────────────────────────────────

    /**
     * Calculate shipping net + VAT across all destinations.
     *
     * @return array{netPenny: int, vatPenny: int}
     */
    public function calculateShipping(
        array $destinations,
        string $shippingMethod,
        ?string $giftShippingMethod = null,
        ?TaxProfile $taxProfile = null
    ): array {
        $giftShippingMethod = $giftShippingMethod ?: $shippingMethod;
        $netPenny = 0;
        $vatPenny = 0;

        foreach ($destinations as $dest) {
            $method      = (($dest['type'] ?? '') === 'gift') ? $giftShippingMethod : $shippingMethod;
            $destNet     = $this->shippingPennyFor($dest['country'], $method);
            $netPenny   += $destNet;

            $vatRate = $dest['vat_rate'] ?? $this->vatRateForCountry($dest['country'], $taxProfile);
            if ($vatRate > 0) {
                $vatPenny += (int) round($destNet * $vatRate);
            }
        }

        return [
            'netPenny' => $netPenny,
            'vatPenny' => $vatPenny,
        ];
    }

    // ─────────────────────────────────────────
    // DISCOUNT
    // ─────────────────────────────────────────

    /**
     * Resolve and calculate discount from session data.
     *
     * @return array{penny: int, discount: ?Discount, applied: ?array}
     */
    public function calculateDiscount(int $subtotalExVatPenny, ?array $sessionDiscount, ?int $userId = null): array
    {
        if (!$sessionDiscount) {
            return ['penny' => 0, 'discount' => null, 'applied' => null];
        }

        $discount = Discount::find($sessionDiscount['discount_id']);
        if (!$discount) {
            return ['penny' => 0, 'discount' => null, 'applied' => null];
        }

        // On store(), re-validate; on show()/totals(), just calculate
        $penny = $discount->calculatePenny($subtotalExVatPenny);

        return [
            'penny'    => $penny,
            'discount' => $discount,
            'applied'  => $sessionDiscount,
        ];
    }

    /**
     * Re-validate discount before placing order.
     */
    public function validateDiscount(Discount $discount, int $subtotalExVatPenny, int $userId): array
    {
        $valid = $discount->isValid($subtotalExVatPenny, $userId);
        if ($valid !== true) {
            return ['penny' => 0, 'error' => $valid];
        }

        return ['penny' => $discount->calculatePenny($subtotalExVatPenny), 'error' => null];
    }

    // ─────────────────────────────────────────
    // SUBSCRIPTION & S&S SAVINGS
    // ─────────────────────────────────────────

    /**
     * Calculate subscription plan discount + Subscribe & Save item discounts.
     *
     * @return array{
     *   activeSubscription: ?UserSubscription,
     *   plan: ?SubscriptionPlan,
     *   subDiscountPenny: int,
     *   subFreeShipping: bool,
     *   ssDiscountPenny: int,
     *   ssDiscountPercent: int,
     *   ssActiveItems: \Illuminate\Support\Collection,
     *   potentialSavings: int
     * }
     */
    public function calculateSubscriptionSavings(
        array $cart,
        $user,
        int $subtotalExVatPenny,
        int $shippingNetPenny
    ): array {
        $plan               = SubscriptionPlan::activePlan();
        $ssDiscountPercent   = SubscribeSaveSettings::discountPercent();
        $activeSubscription = null;
        $subDiscountPenny   = 0;
        $subFreeShipping    = false;
        $ssDiscountPenny    = 0;
        $ssActiveItems      = collect();

        if ($user) {
            $activeSubscription = UserSubscription::activeFor($user->user_id);
            $ssActiveItems      = SubscribeSaveItem::activeFor($user->user_id);

            // Plan discount
            if ($activeSubscription && $activeSubscription->plan) {
                $subPlan = $activeSubscription->plan;
                if ($subPlan->order_discount_percent > 0) {
                    $subDiscountPenny = (int) round($subtotalExVatPenny * ($subPlan->order_discount_percent / 100));
                }
                if ($subPlan->free_shipping) {
                    $subFreeShipping = true;
                }
            }

            // S&S per-item discount
            if ($ssDiscountPercent > 0 && $ssActiveItems->isNotEmpty()) {
                foreach ($cart as $line) {
                    $vid = $line['variant_id'] ?? null;
                    if ($vid && $ssActiveItems->where('variant_id', $vid)->isNotEmpty()) {
                        $unitBase = $this->extractBasePrice((float) ($line['price'] ?? 0));
                        $qty      = (int) ($line['quantity'] ?? 1);
                        $ssDiscountPenny += (int) round($unitBase * $qty * ($ssDiscountPercent / 100) * 100);
                    }
                }
            }
        }

        // Potential savings for non-subscribers
        $potentialSavings = 0;
        if (!$activeSubscription && $plan && $plan->is_active) {
            if ($plan->free_shipping && $shippingNetPenny > 0) {
                $potentialSavings += $shippingNetPenny;
            }
            if ($plan->order_discount_percent > 0) {
                $potentialSavings += (int) round($subtotalExVatPenny * ($plan->order_discount_percent / 100));
            }
        }

        return [
            'activeSubscription' => $activeSubscription,
            'plan'               => $plan,
            'subDiscountPenny'   => $subDiscountPenny,
            'subFreeShipping'    => $subFreeShipping,
            'ssDiscountPenny'    => $ssDiscountPenny,
            'ssDiscountPercent'  => $ssDiscountPercent,
            'ssActiveItems'      => $ssActiveItems,
            'potentialSavings'   => $potentialSavings,
        ];
    }

    // ─────────────────────────────────────────
    // ASSEMBLE TOTALS
    // ─────────────────────────────────────────

    /**
     * Apply subscription savings and compute the final total.
     *
     * @return array{
     *   totalPenny: int,
     *   combinedVatPenny: int,
     *   shippingNetPenny: int,
     *   originalShippingNetPenny: int
     * }
     */
    public function applySubscriptionAndTotal(
        int $subtotalExVatPenny,
        int $discountPenny,
        int $shippingNetPenny,
        int $shippingVatPenny,
        int $productVatPenny,
        int $subDiscountPenny,
        bool $subFreeShipping,
        int $ssDiscountPenny,
        string $shippingMethod = 'standard'
    ): array {
        $combinedVatPenny          = $productVatPenny + $shippingVatPenny;
        $originalShippingNetPenny  = $shippingNetPenny;
        $taxableExVat              = max(0, $subtotalExVatPenny - $discountPenny);
        $totalPenny                = $taxableExVat + $shippingNetPenny + $combinedVatPenny;

        // Free shipping from subscription — standard method only
        if ($subFreeShipping && $shippingMethod === 'standard') {
            $totalPenny       -= ($shippingNetPenny + $shippingVatPenny);
            $combinedVatPenny -= $shippingVatPenny;
            $shippingNetPenny  = 0;
        } elseif ($subFreeShipping) {
            // Plan offers free shipping but user chose premium method
            $subFreeShipping = false;
        }

        // Plan discount
        if ($subDiscountPenny > 0) {
            $totalPenny -= $subDiscountPenny;
        }

        // S&S discount
        if ($ssDiscountPenny > 0) {
            $totalPenny -= $ssDiscountPenny;
        }

        return [
            'totalPenny'               => $totalPenny,
            'combinedVatPenny'         => $combinedVatPenny,
            'shippingNetPenny'         => $shippingNetPenny,
            'originalShippingNetPenny' => $originalShippingNetPenny,
            'subFreeShipping'          => $subFreeShipping,
        ];
    }

    // ─────────────────────────────────────────
    // CONVENIENCE — FULL CALCULATION
    // ─────────────────────────────────────────

    /**
     * Run the complete pricing pipeline.
     *
     * Used by CheckoutController::show(), CheckoutAjaxController::totals(),
     * and CheckoutController::store().
     */
    public function calculateAll(array $params): array
    {
        $cart                = $params['cart'];
        $giftOrders          = $params['giftOrders'];
        $purchaserCountry    = $params['purchaserCountry'];
        $taxProfile          = $params['taxProfile'] ?? null;
        $shippingMethod      = $params['shippingMethod'] ?? 'standard';
        $giftShippingMethod  = $params['giftShippingMethod'] ?? $shippingMethod;
        $sessionDiscount     = $params['sessionDiscount'] ?? null;
        $userId              = $params['userId'] ?? null;
        $user                = $params['user'] ?? null;

        // 1. Gift detection
        $gifts = $this->detectGifts($cart, $giftOrders);
        if ($gifts['isGift']) {
            $giftShippingMethod = $shippingMethod;
        }

        // 2. Destinations
        $destinations = $this->buildDestinations(
            $gifts['giftItems'],
            $gifts['nonGiftItems'],
            $gifts['giftOrders'],
            $purchaserCountry,
            $taxProfile
        );

        // 3. Per-item breakdown
        $itemsResult = $this->calculateItemsBreakdown(
            $cart,
            $gifts['giftOrders'],
            $purchaserCountry,
            $taxProfile
        );

        // 4. Discount
        $discountResult = $this->calculateDiscount(
            $itemsResult['subtotalExVatPenny'],
            $sessionDiscount,
            $userId
        );

        // 5. Shipping
        $shippingResult = $this->calculateShipping(
            $destinations,
            $shippingMethod,
            $giftShippingMethod,
            $taxProfile
        );

        // 6. Subscription savings
        $subResult = $this->calculateSubscriptionSavings(
            $cart,
            $user,
            $itemsResult['subtotalExVatPenny'],
            $shippingResult['netPenny']
        );

        // 7. Final totals
        $totals = $this->applySubscriptionAndTotal(
            $itemsResult['subtotalExVatPenny'],
            $discountResult['penny'],
            $shippingResult['netPenny'],
            $shippingResult['vatPenny'],
            $itemsResult['totalVatPenny'],
            $subResult['subDiscountPenny'],
            $subResult['subFreeShipping'],
            $subResult['ssDiscountPenny'],
            $gifts['isGift'] ? $giftShippingMethod : $shippingMethod
        );

        return [
            // Gift
            'giftItems'      => $gifts['giftItems'],
            'nonGiftItems'   => $gifts['nonGiftItems'],
            'isGift'         => $gifts['isGift'],
            'hasMixedCart'    => $gifts['hasMixedCart'],
            'giftOrders'     => $gifts['giftOrders'],

            // Destinations
            'destinations'   => $destinations,

            // Items
            'subtotalExVatPenny' => $itemsResult['subtotalExVatPenny'],
            'totalVatPenny'      => $itemsResult['totalVatPenny'],
            'itemsBreakdown'     => $itemsResult['items'],

            // Discount
            'discountPenny'   => $discountResult['penny'],
            'discount'        => $discountResult['discount'],
            'appliedDiscount' => $discountResult['applied'],

            // Shipping
            'shippingNetPenny'         => $totals['shippingNetPenny'],
            'shippingVatPenny'         => $shippingResult['vatPenny'],
            'originalShippingNetPenny' => $totals['originalShippingNetPenny'],

            // VAT
            'combinedVatPenny' => $totals['combinedVatPenny'],
            'vatRate'          => $destinations[0]['vat_rate'] ?? 0,

            // Total
            'totalPenny' => $totals['totalPenny'],

            // Subscription
            'activeSubscription' => $subResult['activeSubscription'],
            'plan'               => $subResult['plan'],
            'subDiscountPenny'   => $subResult['subDiscountPenny'],
            'subFreeShipping'    => $totals['subFreeShipping'],
            'ssDiscountPenny'    => $subResult['ssDiscountPenny'],
            'ssDiscountPercent'  => $subResult['ssDiscountPercent'],
            'ssActiveItems'      => $subResult['ssActiveItems'],
            'potentialSavings'   => $subResult['potentialSavings'],
        ];
    }
}
