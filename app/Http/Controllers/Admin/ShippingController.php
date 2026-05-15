<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingRate;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index()
    {
        $rates = ShippingRate::orderBy('country_code')
            ->orderBy('method_key')
            ->orderBy('sort_order')
            ->get();

        // Group by country for display
        $grouped = $rates->groupBy('country_code');

        return view('admin.shipping.index', compact('rates', 'grouped'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'zone_name'     => 'required|string|max:100',
            'country_code'  => 'required|string|max:4',
            'method_key'    => 'required|string|max:50',
            'method_label'  => 'required|string|max:100',
            'price_penny'   => 'required|integer|min:0',
            'is_active'     => 'sometimes|boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        ShippingRate::create($data);

        return back()->with('success', 'Shipping rate added.');
    }

    public function update(Request $request, int $rate)
    {
        $shippingRate = ShippingRate::findOrFail($rate);

        $data = $request->validate([
            'zone_name'     => 'required|string|max:100',
            'method_label'  => 'required|string|max:100',
            'price_penny'   => 'required|integer|min:0',
            'is_active'     => 'sometimes|boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $shippingRate->update($data);

        return back()->with('success', 'Shipping rate updated.');
    }

    public function destroy(int $rate)
    {
        $shippingRate = ShippingRate::findOrFail($rate);
        $shippingRate->delete();

        return back()->with('success', 'Shipping rate deleted.');
    }
}
