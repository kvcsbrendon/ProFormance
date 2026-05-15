<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\SavedCard;
use App\Models\ShippingRate;
use App\Models\TaxProfile;
use App\Services\Checkout\OrderService;
use App\Services\Checkout\PricingService;

use App\Rules\Luhn;
use App\Rules\CardNumberBrand;
use App\Rules\CardExpiry;
use App\Rules\CardCvv;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(
        protected PricingService $pricing,
        protected OrderService $orders,
    ) {}

    protected function currency(): string
    {
        return session('currency', 'gbp');
    }

    protected function getCart(): array
    {
        return session('cart', []);
    }

    // ─────────────────────────────────────────
    // SHOW CHECKOUT PAGE
    // ─────────────────────────────────────────

    public function show(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please sign in to proceed to checkout.');
        }

        $cart = $this->getCart();
        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Your basket is empty.');
        }

        $user       = Auth::user();
        $addresses  = Address::where('user_id', $user->user_id)
            ->orderByDesc('is_default_shipping_address')
            ->get();
        $defaultAddress = $addresses->first();
        $taxProfile     = TaxProfile::where('user_id', $user->user_id)->first();

        // Run the full pricing pipeline
        $p = $this->pricing->calculateAll([
            'cart'               => $cart,
            'giftOrders'         => session('gift_orders', []),
            'purchaserCountry'   => $defaultAddress?->country_code ?? 'GB',
            'taxProfile'         => $taxProfile,
            'shippingMethod'     => $request->input('shipping_method', 'standard'),
            'giftShippingMethod' => $request->input('gift_shipping_method', 'standard'),
            'sessionDiscount'    => session('checkout_discount'),
            'userId'             => $user->user_id,
            'user'               => $user,
        ]);

        // Gift shipping preview (minimal info for display)
        $giftShippingPreview = null;
        if ($p['isGift'] && !empty($p['giftItems'])) {
            $firstGiftVariant = $p['giftItems'][0]['variant_id'] ?? null;
            if ($firstGiftVariant && isset($p['giftOrders'][$firstGiftVariant])) {
                $giftShippingPreview = [
                    'recipient_name' => 'Gift Recipient',
                    'country_code'   => $p['giftOrders'][$firstGiftVariant]['country_code'] ?? 'GB',
                ];
            }
        }

        // Shipping methods for the primary destination
        $primaryCountryCode = $p['destinations'][0]['country'] ?? 'GB';
        $shippingMethods    = ShippingRate::methodsFor($primaryCountryCode);

        // Gift shipping methods for mixed carts
        $giftShippingMethods = collect();
        $giftCountryCode     = $primaryCountryCode;
        if ($p['hasMixedCart'] && !empty($p['giftItems'])) {
            $firstGiftVariant = $p['giftItems'][0]['variant_id'] ?? null;
            $giftCountryCode  = ($firstGiftVariant && isset($p['giftOrders'][$firstGiftVariant]))
                ? ($p['giftOrders'][$firstGiftVariant]['country_code'] ?? 'GB')
                : 'GB';
            $giftShippingMethods = ShippingRate::methodsFor($giftCountryCode);
        }

        // Saved cards
        $savedCards = SavedCard::where('user_id', $user->user_id)
            ->where('expiry_year', '>=', now()->year)
            ->orderByDesc('is_default')
            ->get();

        $symbol = $cart[0]['symbol'] ?? '£';
        $countries = \Illuminate\Support\Facades\DB::table('countries')
            ->orderBy('country_name')
            ->pluck('country_name', 'country_code');

        return view('checkout.show', [
            'lines'                    => $cart,
            'addresses'                => $addresses,
            'user'                     => $user,
            'countries'                => $countries,
            'appliedDiscount'          => $p['appliedDiscount'],
            'isGift'                   => $p['isGift'],
            'hasMixedCart'             => $p['hasMixedCart'],
            'giftShippingPreview'      => $giftShippingPreview,
            'giftItems'                => $p['giftItems'],
            'nonGiftItems'             => $p['nonGiftItems'],
            'subtotalExVatPenny'       => $p['subtotalExVatPenny'],
            'discountPenny'            => $p['discountPenny'],
            'shippingNetPenny'         => $p['shippingNetPenny'],
            'originalShippingNetPenny' => $p['originalShippingNetPenny'],
            'combinedVatPenny'         => $p['combinedVatPenny'],
            'totalPenny'               => $p['totalPenny'],
            'vatRate'                  => $p['vatRate'],
            'destinations'             => $p['destinations'],
            'totalItems'               => array_sum(array_column($cart, 'quantity')),
            'symbol'                   => $symbol,
            'shipCountryCode'          => $primaryCountryCode,
            'selectedShippingMethod'   => $request->input('shipping_method', 'standard'),
            'savedCards'               => $savedCards,
            'shippingMethods'          => $shippingMethods,
            'giftShippingMethods'      => $giftShippingMethods,
            'giftCountryCode'          => $giftCountryCode,
            'activeSubscription'       => $p['activeSubscription'],
            'subDiscountPenny'         => $p['subDiscountPenny'],
            'subFreeShipping'          => $p['subFreeShipping'],
            'ssDiscountPenny'          => $p['ssDiscountPenny'],
            'ssDiscountPercent'        => $p['ssDiscountPercent'],
            'ssActiveItems'            => $p['ssActiveItems'],
            'plan'                     => $p['plan'],
            'potentialSavings'         => $p['potentialSavings'],
        ]);
    }

    // ─────────────────────────────────────────
    // PLACE ORDER
    // ─────────────────────────────────────────

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $cart = $this->getCart();

        $totalItems = array_sum(array_map(fn($l) => (int) ($l['quantity'] ?? 0), $cart));
        if ($totalItems === 0) {
            return redirect()->route('cart.index')->with('error', 'Your basket is empty.');
        }

        // ── Validation flags ──
        $usingSavedAddress = $request->filled('address_id');
        $billingSame       = $request->has('billing_same');
        $usingSavedBilling = !$billingSame && $request->filled('billing_address_id');
        $method            = $request->input('payment_method', 'card');
        $usingSavedCard    = $request->filled('saved_card_id');

        // Gift detection for skip-shipping logic
        $giftOrders    = session('gift_orders', []);
        $cartVids      = collect($cart)->pluck('variant_id')->filter()->map(fn($id) => (string) $id)->toArray();
        $hasGiftItems  = !empty(array_intersect_key($giftOrders, array_flip($cartVids)));
        $nonGiftLines  = collect($cart)->filter(fn($line) => !isset($giftOrders[(string) ($line['variant_id'] ?? '')]));
        $isGiftOnly    = $hasGiftItems && $nonGiftLines->isEmpty();
        $skipShipValid = $usingSavedAddress || $isGiftOnly;

        // ── Validate ──
        $data = $request->validate([
            'address_id'            => 'nullable|integer',
            'ship_recipient_name'   => ($skipShipValid ? 'nullable' : 'required') . '|string|max:200',
            'ship_house_number'     => 'nullable|string|max:10',
            'ship_address_line_one' => ($skipShipValid ? 'nullable' : 'required') . '|string|max:100',
            'ship_address_line_two' => 'nullable|string|max:100',
            'ship_city'             => ($skipShipValid ? 'nullable' : 'required') . '|string|max:100',
            'ship_county'           => 'nullable|string|max:100',
            'ship_postcode'         => ($skipShipValid ? 'nullable' : 'required') . '|string|max:100',
            'ship_country_code'     => ($skipShipValid ? 'nullable' : 'required') . '|string|max:2',
            'ship_phone_number'     => 'nullable|string|max:30',

            'billing_same'          => 'sometimes|in:1',
            'billing_address_id'    => 'nullable|integer',
            'bill_recipient_name'   => (($billingSame || $usingSavedBilling) ? 'nullable' : 'required') . '|string|max:200',
            'bill_house_number'     => 'nullable|string|max:10',
            'bill_address_line_one' => (($billingSame || $usingSavedBilling) ? 'nullable' : 'required') . '|string|max:100',
            'bill_address_line_two' => 'nullable|string|max:100',
            'bill_city'             => (($billingSame || $usingSavedBilling) ? 'nullable' : 'required') . '|string|max:100',
            'bill_county'           => 'nullable|string|max:100',
            'bill_postcode'         => (($billingSame || $usingSavedBilling) ? 'nullable' : 'required') . '|string|max:100',
            'bill_country_code'     => (($billingSame || $usingSavedBilling) ? 'nullable' : 'required') . '|string|max:2',
            'bill_phone_number'     => 'nullable|string|max:30',

            'payment_method' => ['required', 'in:card,paypal'],
            'card_name'   => ($method === 'paypal' || $usingSavedCard) ? ['nullable'] : ['required', 'string', 'max:200'],
            'card_number' => ($method === 'paypal' || $usingSavedCard) ? ['nullable'] : ['required', 'string', 'max:30', new Luhn(), new CardNumberBrand()],
            'card_expiry' => ($method === 'paypal' || $usingSavedCard) ? ['nullable'] : ['required', 'string', 'max:7', new CardExpiry()],
            'card_cvv'    => ($method === 'paypal' || $usingSavedCard) ? ['nullable'] : ['required', 'string', 'max:4', new CardCvv($request->input('card_number'))],

            'order_notes'          => 'nullable|string|max:1000',
            'shipping_method'      => ['required', Rule::in(ShippingRate::query()->where('is_active', true)->pluck('method_key')->unique()->toArray())],
            'gift_shipping_method' => ['nullable', Rule::in(ShippingRate::query()->where('is_active', true)->pluck('method_key')->unique()->toArray())],
            'save_address'         => 'sometimes|in:1',
            'save_card'            => 'sometimes|in:1',
            'saved_card_id'        => 'nullable|integer|exists:saved_cards,card_id,user_id,' . Auth::id(),
        ]);

        // ── Resolve addresses ──
        $taxProfile = TaxProfile::where('user_id', $user->user_id)->first();

        $gifts        = $this->pricing->detectGifts($cart, $giftOrders);
        $giftShipping = null;
        if (!empty($gifts['giftItems']) && empty($gifts['nonGiftItems'])) {
            $firstGiftVariant = $gifts['giftItems'][0]['variant_id'];
            $giftShipping     = $giftOrders[$firstGiftVariant] ?? null;
        }

        $shipping = $giftShipping
            ? $this->resolveGiftShipping($giftShipping)
            : $this->resolveShippingAddress($data, $user);

        $billing = $this->resolveBillingAddress($data, $shipping, $user);

        // ── Purchaser country ──
        $defaultAddress   = Address::where('user_id', $user->user_id)->where('is_default_shipping_address', true)->first();
        $purchaserCountry = $defaultAddress?->country_code ?? 'GB';

        // ── Calculate pricing ──
        $p = $this->pricing->calculateAll([
            'cart'               => $cart,
            'giftOrders'         => $gifts['giftOrders'],
            'purchaserCountry'   => $purchaserCountry,
            'taxProfile'         => $taxProfile,
            'shippingMethod'     => $data['shipping_method'] ?? 'standard',
            'giftShippingMethod' => $data['gift_shipping_method'] ?? ($data['shipping_method'] ?? 'standard'),
            'sessionDiscount'    => session('checkout_discount'),
            'userId'             => $user->user_id,
            'user'               => $user,
        ]);

        // Re-validate discount if present
        if ($p['discount']) {
            $discountCheck = $this->pricing->validateDiscount($p['discount'], $p['subtotalExVatPenny'], $user->user_id);
            if ($discountCheck['error']) {
                return back()->withErrors(['discount_code' => $discountCheck['error']]);
            }
        }

        // Add shipping method to pricing for order record
        $p['shippingMethod'] = $data['shipping_method'] ?? 'standard';

        // ── Place order ──
        $order = $this->orders->placeOrder([
            'user'              => $user,
            'cart'              => $cart,
            'shipping'          => $shipping,
            'billing'           => $billing,
            'pricing'           => $p,
            'paymentMethod'     => $data['payment_method'],
            'paymentData'       => [
                'card_name'     => $data['card_name'] ?? null,
                'card_number'   => $data['card_number'] ?? null,
                'card_expiry'   => $data['card_expiry'] ?? null,
            ],
            'giftOrders'        => $gifts['giftOrders'],
            'currency'          => $this->currency(),
            'orderNotes'        => $data['order_notes'] ?? null,
            'saveAddress'       => $request->has('save_address'),
            'saveCard'          => $request->has('save_card'),
            'usingSavedAddress' => $usingSavedAddress,
        ]);

        // Clear sessions
        $request->session()->forget(['cart', 'checkout_discount', 'gift_orders']);

        return redirect()->route('account.orders')
            ->with('success', 'Your order has been placed successfully!');
    }

    // ─────────────────────────────────────────
    // ADDRESS HELPERS
    // ─────────────────────────────────────────

    private function resolveShippingAddress(array $data, $user): array
    {
        if (!empty($data['address_id'])) {
            $address = Address::where('user_id', $user->user_id)
                ->where('address_id', $data['address_id'])
                ->first();

            if ($address) {
                return [
                    'address_id'       => $address->address_id,
                    'recipient_name'   => $address->recipient_name,
                    'house_number'     => $address->house_number,
                    'address_line_one' => $address->address_line_one,
                    'address_line_two' => $address->address_line_two,
                    'city'             => $address->city,
                    'county'           => $address->county,
                    'postcode'         => $address->postcode,
                    'country_code'     => $address->country_code,
                    'phone_number'     => $address->phone_number,
                ];
            }
        }

        return [
            'address_id'       => null,
            'recipient_name'   => $data['ship_recipient_name'],
            'house_number'     => $data['ship_house_number'] ?? null,
            'address_line_one' => $data['ship_address_line_one'],
            'address_line_two' => $data['ship_address_line_two'] ?? null,
            'city'             => $data['ship_city'],
            'county'           => $data['ship_county'] ?? null,
            'postcode'         => $data['ship_postcode'],
            'country_code'     => $data['ship_country_code'],
            'phone_number'     => $data['ship_phone_number'] ?? null,
        ];
    }

    private function resolveGiftShipping(array $giftData): array
    {
        return [
            'address_id'       => $giftData['address_id'] ?? null,
            'recipient_name'   => $giftData['recipient_name'] ?? null,
            'house_number'     => $giftData['house_number'] ?? null,
            'address_line_one' => $giftData['address_line_one'] ?? null,
            'address_line_two' => $giftData['address_line_two'] ?? null,
            'city'             => $giftData['city'] ?? null,
            'county'           => $giftData['county'] ?? null,
            'postcode'         => $giftData['postcode'] ?? null,
            'country_code'     => $giftData['country_code'] ?? null,
            'phone_number'     => $giftData['phone_number'] ?? null,
        ];
    }

    private function resolveBillingAddress(array $data, array $shipping, $user): array
    {
        if (!empty($data['billing_same'])) {
            return $shipping;
        }

        if (!empty($data['billing_address_id'])) {
            $addr = Address::where('address_id', $data['billing_address_id'])
                ->where('user_id', $user->user_id)
                ->firstOrFail();

            return [
                'address_id'       => $addr->address_id,
                'recipient_name'   => $addr->recipient_name,
                'house_number'     => $addr->house_number,
                'address_line_one' => $addr->address_line_one,
                'address_line_two' => $addr->address_line_two,
                'city'             => $addr->city,
                'county'           => $addr->county,
                'postcode'         => $addr->postcode,
                'country_code'     => $addr->country_code,
                'phone_number'     => $addr->phone_number,
            ];
        }

        return [
            'address_id'       => null,
            'recipient_name'   => $data['bill_recipient_name'],
            'house_number'     => $data['bill_house_number'] ?? null,
            'address_line_one' => $data['bill_address_line_one'],
            'address_line_two' => $data['bill_address_line_two'] ?? null,
            'city'             => $data['bill_city'],
            'county'           => $data['bill_county'] ?? null,
            'postcode'         => $data['bill_postcode'],
            'country_code'     => $data['bill_country_code'],
            'phone_number'     => $data['bill_phone_number'] ?? null,
        ];
    }
}
