<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BulkPricing;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class BulkPricingController extends Controller
{
    /**
     * Show bulk pricing tiers for a variant (AJAX or page).
     */
    public function index(int $variantId)
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);
        $tiers = BulkPricing::where('variant_id', $variantId)
            ->orderBy('currency_code')
            ->orderBy('min_quantity')
            ->get();

        return view('admin.products.bulk-pricing', compact('variant', 'tiers'));
    }

    /**
     * Store a new tier.
     */
    public function store(Request $request, int $variantId)
    {
        $variant = ProductVariant::findOrFail($variantId);

        $data = $request->validate([
            'currency_code' => 'required|string|size:3',
            'min_quantity'   => 'required|integer|min:2',
            'price_penny'    => 'required|integer|min:1',
        ]);

        // Check for duplicate tier
        $exists = BulkPricing::where('variant_id', $variantId)
            ->where('currency_code', $data['currency_code'])
            ->where('min_quantity', $data['min_quantity'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['min_quantity' => 'A tier for this quantity already exists.']);
        }

        BulkPricing::create([
            'variant_id'    => $variantId,
            'currency_code' => strtoupper($data['currency_code']),
            'min_quantity'   => $data['min_quantity'],
            'price_penny'    => $data['price_penny'],
            'is_active'      => true,
        ]);

        return back()->with('success', 'Bulk pricing tier added.');
    }

    /**
     * Update a tier.
     */
    public function update(Request $request, int $tierId)
    {
        $tier = BulkPricing::findOrFail($tierId);

        $data = $request->validate([
            'min_quantity' => 'required|integer|min:2',
            'price_penny'  => 'required|integer|min:1',
            'is_active'    => 'sometimes|boolean',
        ]);

        $tier->update($data);

        return back()->with('success', 'Tier updated.');
    }

    /**
     * Delete a tier.
     */
    public function destroy(int $tierId)
    {
        $tier = BulkPricing::findOrFail($tierId);
        $tier->delete();

        return back()->with('success', 'Tier removed.');
    }
}
