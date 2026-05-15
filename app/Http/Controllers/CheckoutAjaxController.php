<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Discount;
use App\Models\TaxProfile;
use App\Models\ShippingRate;
use App\Services\Checkout\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutAjaxController extends Controller
{
    public function __construct(
        protected PricingService $pricing,
    ) {}

    protected function getCart(): array
    {
        return session('cart', []);
    }

    // ─────────────────────────────────────────
    // RECALCULATE TOTALS
    // ─────────────────────────────────────────

    public function totals(Request $request)
    {
        $cart = $this->getCart();
        if (empty($cart)) {
            return response()->json(['ok' => false]);
        }

        $user       = Auth::user();
        $taxProfile = $user ? TaxProfile::where('user_id', $user->user_id)->first() : null;

        $defaultAddress = $user
            ? Address::where('user_id', $user->user_id)->where('is_default_shipping_address', true)->first()
            : null;

        // Country priority: explicit country code > saved address lookup > default > GB
        if ($request->filled('ship_country_code')) {
            $countryCode = strtoupper($request->input('ship_country_code'));
        } elseif ($request->filled('address_id') && $user) {
            $selectedAddr = Address::where('user_id', $user->user_id)
                ->where('address_id', $request->input('address_id'))
                ->first();
            $countryCode = $selectedAddr?->country_code ?? ($defaultAddress?->country_code ?? 'GB');
        } else {
            $countryCode = $defaultAddress?->country_code ?? 'GB';
        }

        $p = $this->pricing->calculateAll([
            'cart'               => $cart,
            'giftOrders'         => session('gift_orders', []),
            'purchaserCountry'   => $countryCode,
            'taxProfile'         => $taxProfile,
            'shippingMethod'     => $request->input('shipping_method', 'standard'),
            'giftShippingMethod' => $request->input('gift_shipping_method', 'standard'),
            'sessionDiscount'    => session('checkout_discount'),
            'userId'             => $user?->user_id,
            'user'               => $user,
        ]);
        $shippingMethods = ShippingRate::methodsFor($countryCode);
        $methodsArray = $shippingMethods->map(function ($sm) {
            return [
                'method_key'   => $sm->method_key,
                'method_label' => $sm->method_label,
                'price_penny'  => $sm->price_penny,
            ];
        })->values()->toArray();

        return response()->json([
            'ok'                    => true,
            'subtotalPenny'         => $p['subtotalExVatPenny'],
            'discountPenny'         => $p['discountPenny'],
            'shippingNetPenny'      => $p['shippingNetPenny'],
            'originalShippingPenny' => $p['originalShippingNetPenny'],
            'combinedVatPenny'      => $p['combinedVatPenny'],
            'vatRate'               => $p['vatRate'],
            'totalPenny'            => $p['totalPenny'],
            'subDiscountPenny'      => $p['subDiscountPenny'],
            'subFreeShipping'       => $p['subFreeShipping'],
            'ssDiscountPenny'       => $p['ssDiscountPenny'],
            'ssDiscountPercent'     => $p['ssDiscountPercent'],
            'hasActiveSubscription' => (bool) $p['activeSubscription'],
            'potentialSavings'      => $p['potentialSavings'],
            'countryCode'           => $countryCode,
            'countryCode'       => $countryCode,
            'shippingMethods'   => $methodsArray,
        ]);
    }

    // ─────────────────────────────────────────
    // APPLY DISCOUNT CODE
    // ─────────────────────────────────────────

    public function applyDiscount(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|string|max:50',
        ]);

        $code     = strtoupper(trim($request->discount_code));
        $discount = Discount::where('discount_code', $code)->first();

        if (!$discount) {
            return $this->discountError($request, 'Invalid discount code.');
        }

        // Calculate subtotal for validation
        $cart          = session('cart', []);
        $subtotalPenny = 0;
        foreach ($cart as $item) {
            $subtotalPenny += ($item['price_penny'] ?? 0) * ($item['quantity'] ?? 1);
        }

        $userId = Auth::check() ? Auth::user()->user_id : 0;
        $valid  = $discount->isValid($subtotalPenny, $userId);

        if ($valid !== true) {
            return $this->discountError($request, $valid);
        }

        session(['checkout_discount' => [
            'discount_id'    => $discount->discount_id,
            'discount_code'  => $discount->discount_code,
            'discoun_type'   => $discount->discoun_type,
            'discount_value' => $discount->discount_value,
        ]]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Discount \"{$discount->discount_code}\" applied!",
            ]);
        }

        return back()->with('success', "Discount \"{$discount->discount_code}\" applied!");
    }

    // ─────────────────────────────────────────
    // REMOVE DISCOUNT CODE
    // ─────────────────────────────────────────

    public function removeDiscount(Request $request)
    {
        session()->forget('checkout_discount');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Discount removed.');
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function discountError(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message]);
        }

        return back()->withErrors(['discount_code' => $message]);
    }
}
