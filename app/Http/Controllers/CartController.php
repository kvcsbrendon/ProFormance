<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\VariantCurrencyPrice;

class CartController extends Controller
{
    protected function currency(): string
    {
        return session('currency', 'gbp');
    }

    protected function repriceCart(array $cart): array
    {
        $currency = $this->currency();
        $vatRate = $this->displayVatRateForCurrency(strtoupper($currency));

        $variantIds = array_values(array_unique(array_column($cart, 'variant_id')));
        if (empty($variantIds)) return $cart;

        $rows = DB::table('variant_currency_prices as vcp')
            ->join('currencies as c', 'c.currency_code', '=', 'vcp.currency_code')
            ->whereIn('vcp.variant_id', $variantIds)
            ->where('vcp.currency_code', $currency)
            ->select('vcp.variant_id', 'vcp.price_penny', 'vcp.was_price_penny', 'c.symbol')
            ->get()
            ->keyBy('variant_id');

        foreach ($cart as &$line) {
            $row = $rows[$line['variant_id']] ?? null;
            $qty = (int)($line['quantity'] ?? 1);

            $line['currency'] = $currency;
            $line['symbol'] = $row->symbol ?? '£';

            $standardPricePenny = $row ? $row->price_penny : 0;

            // Check for bulk pricing
            $bulkPricePenny = \App\Models\BulkPricing::getPriceForQty(
                $line['variant_id'], strtoupper($currency), $qty
            );

            $netPricePenny = ($bulkPricePenny !== null) ? $bulkPricePenny : $standardPricePenny;
            $netPrice = $netPricePenny / 100;
            $netWas = ($row && $row->was_price_penny) ? ($row->was_price_penny / 100) : null;

            // Store pricing info
            $line['price_penny'] = $netPricePenny;
            $line['price_ex_vat'] = $netPrice;
            $line['was_ex_vat'] = $netWas;
            $line['price'] = $this->applyVatToDecimal($netPrice, $vatRate);
            $line['was'] = $netWas !== null ? $this->applyVatToDecimal($netWas, $vatRate) : null;
            $line['vat_label'] = $vatRate > 0 ? 'incl. VAT' : 'excl. VAT';
            $line['is_bulk_price'] = ($bulkPricePenny !== null);

            // Standard price for comparison (incl VAT)
            if ($bulkPricePenny !== null && $standardPricePenny > 0) {
                $line['standard_price'] = $this->applyVatToDecimal($standardPricePenny / 100, $vatRate);
            } else {
                $line['standard_price'] = null;
            }
        }
        unset($line);

        $this->saveCart($cart);
        return $cart;
    }

    protected function displayVatRateForCurrency(string $currency): float
    {
        return strtoupper($currency) === 'GBP' ? 0.20 : 0.00;
    }

    protected function applyVatToDecimal(float $price, float $vatRate): float
    {
        return round($price * (1 + $vatRate), 2);
    }

    protected function getCart(): array
    {
        return Session::get('cart', []);
    }

    protected function saveCart(array $cart): void
    {
        Session::put('cart', $cart);
    }

    protected function computeTotals(array $cart): array
    {
        $total = 0;
        $totalItems = 0;

        foreach ($cart as $line) {
            $lineTotal = ($line['price'] ?? 0) * ($line['quantity'] ?? 0);
            $total += $lineTotal;
            $totalItems += ($line['quantity'] ?? 0);
        }

        return [$total, $totalItems];
    }

    public function index()
    {
        $cart = $this->getCart();
        $cart = $this->repriceCart($cart);
        [$total, $totalItems] = $this->computeTotals($cart);
        $currency = strtoupper($cart[0]['currency'] ?? $this->currency());
        $vatRate  = $this->displayVatRateForCurrency($currency);
        $vatLabel = $vatRate > 0 ? 'incl. VAT' : 'excl. VAT';

        return view('cart.index', [
            'lines'      => $cart,
            'total'      => $total,
            'totalItems' => $totalItems,
            'vatLabel'   => $vatLabel,
        ]);
    }
    
    public function add(Request $request)
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer',
            'quantity'   => 'nullable|integer|min:0',
        ]);

        $variantId = $validated['variant_id'];
        $quantity  = $validated['quantity'] ?? 1;
        $isGiftAddition = $request->input('_gift', false);
        
        // ── 1. Fetch Variant with Inventory ──
        $variant = \App\Models\ProductVariant::with([
            'product', 
            'inventory', 
            'images' => function($q) { $q->orderBy('sort_order'); }
        ])->find($variantId);

        if (! $variant) {
            if ($request->expectsJson()) return response()->json(['ok' => false, 'message' => 'Product not found.'], 404);
            return back()->with('error', 'Product not found.');
        }

        $inv = $variant->inventory;
        $stock = $inv ? $inv->in_stock : 0; 

        $priceRow = \App\Models\VariantCurrencyPrice::where('variant_id', $variantId)
            ->where('currency_code', $this->currency())
            ->first();
        $price = $priceRow?->price ?? 0;

        $cart = $this->getCart();
        
        if (!$isGiftAddition) {
            $giftOrders = session('gift_orders', []);
            if (isset($giftOrders[$variantId])) {
                unset($giftOrders[$variantId]);
                session(['gift_orders' => $giftOrders]);
            }
        }

        $foundIndex = null;
        foreach ($cart as $idx => $line) {
            if ($line['variant_id'] == $variantId) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($quantity > $stock) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => "Only {$stock} items in stock."], 400);
            }
            return back()->with('error', "Cannot set quantity. Only {$stock} items in stock.");
        }

        if ($quantity <= 0) {
            if ($foundIndex !== null) {
                unset($cart[$foundIndex]);
                $cart = array_values($cart);
            }
        } else {
            if ($foundIndex !== null) {
                $cart[$foundIndex]['quantity'] = $quantity;
            } else {
                $existingIds = array_column($cart, 'id');
                $newId = empty($existingIds) ? 1 : (max($existingIds) + 1);
                $firstImage = $variant->images->first();

                $cart[] = [
                    'id'         => $newId,
                    'variant_id' => $variant->variant_id,
                    'product_id' => $variant->product_id,
                    'name'       => $variant->product->product_name ?? 'Unknown',
                    'image'      => $firstImage ? ('images/' . $firstImage->image_url) : null,
                    'price'      => $price,
                    'quantity'   => $quantity,
                    'currency'   => $this->currency(),
                ];
            }
        }

        $this->saveCart($cart);
        $cart = $this->repriceCart($cart); 
        [$total, $totalItems] = $this->computeTotals($cart);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'totalItems' => (int)$totalItems,
                'total' => (float)$total,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated.');
    }

    public function update(Request $request, int $lineId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->getCart();
        $errorMsg = null;

        foreach ($cart as $idx => &$line) {
            if ($line['id'] == $lineId) {
                $qty = $validated['quantity'];

                // ── Fetch stock dynamically using find() and your accessor ──
                $inv = \App\Models\Inventory::find($line['variant_id']);
                $stock = $inv ? $inv->in_stock : 0;

                if ($qty > $stock) {
                    $qty = $stock; 
                    $errorMsg = "Quantity adjusted. Only {$stock} items in stock.";
                }

                if ($qty <= 0) {
                    unset($cart[$idx]);
                } else {
                    $line['quantity'] = $qty;
                }

                break;
            }
        }
        unset($line);

        $cart = array_values($cart);
        $this->saveCart($cart);
        $cart = $this->repriceCart($cart); 

        [$total, $totalItems] = $this->computeTotals($cart);

        if ($request->expectsJson()) {
            $updatedLine = collect($cart)->firstWhere('id', $lineId);
            
            $resp = [
                'ok' => true,
                'total' => $total,
                'totalItems' => $totalItems,
            ];

            if ($errorMsg) $resp['message'] = $errorMsg;
            if ($updatedLine) {
                $resp['line_price'] = $updatedLine['price'];
                $resp['line_total'] = $updatedLine['price'] * $updatedLine['quantity'];
            }

            return response()->json($resp, $errorMsg ? 400 : 200);
        }

        if ($errorMsg) {
            return redirect()->route('cart.index')->with('error', $errorMsg);
        }

        return redirect()->route('cart.index')->with('success', 'Basket updated.');
    }

    public function remove(int $lineId)
    {
        $cart = $this->getCart();

        foreach ($cart as $idx => $line) {
            if ($line['id'] == $lineId) {
                unset($cart[$idx]);
                break;
            }
        }

        $cart = array_values($cart);
        $this->saveCart($cart);

        return redirect()->route('cart.index');
    }

    public function clear()
    {
        $this->saveCart([]);

        return redirect()->route('cart.index');
    }

    public function preview()
    {
        $cart = $this->getCart();
        $cart = $this->repriceCart($cart);
        [$total, $totalItems] = $this->computeTotals($cart);

        $lines = array_map(function ($line) {
            return [
                'id'         => $line['id'],
                'name'       => $line['name'] ?? '',
                'variant_id' => $line['variant_id'],
                'image'      => $line['image'] ?? null,
                'price'      => (float)($line['price'] ?? 0),
                'quantity'   => (int)($line['quantity'] ?? 0),
                'symbol'     => $line['symbol'] ?? '£',
            ];
        }, $cart);

        return response()->json([
            'ok'         => true,
            'total'      => (float)$total,
            'totalItems' => (int)$totalItems,
            'currency'   => $cart[0]['currency'] ?? $this->currency(),
            'symbol'     => $cart[0]['symbol'] ?? '£',
            'lines'      => $lines,
        ]);
    }
}