<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountRedemption;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $query = Discount::orderByDesc('created_at');
        if ($request->filled('search')) { $query->where('discount_code', 'like', "%{$request->search}%"); }
        if ($request->filled('active')) { $query->where('is_active', $request->active); }
        $discounts = $query->paginate(20)->withQueryString();
        return view('admin.discounts.index', compact('discounts'));
    }

    public function create()
    {
        return view('admin.discounts.form', ['discount' => null, 'redemptions' => 0]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'discount_code'      => 'required|string|max:50|unique:discounts,discount_code',
            'discoun_type'       => 'required|in:percentage,fixed_amount',
            'discount_value'     => 'required|numeric|min:0',
            'starts_at'          => 'nullable|date',
            'ends_at'            => 'nullable|date|after_or_equal:starts_at',
            'usage_limit'        => 'nullable|integer|min:1',
            'per_user_limit'     => 'nullable|integer|min:1',
            'min_subtotal_penny' => 'nullable|integer|min:0',
            'is_active'          => 'sometimes|boolean',
        ]);
        $data['discount_code'] = strtoupper(trim($data['discount_code']));
        $data['is_active'] = $data['is_active'] ?? true;
        Discount::create($data);
        return redirect()->route('admin.discounts.index')->with('success', 'Discount created.');
    }

    public function edit($discountId)
    {
        $discount = Discount::where('discount_id', $discountId)->firstOrFail();
        $redemptions = DiscountRedemption::where('discount_id', $discountId)->count();
        return view('admin.discounts.form', compact('discount', 'redemptions'));
    }

    public function update(Request $request, $discountId)
    {
        $discount = Discount::where('discount_id', $discountId)->firstOrFail();
        $data = $request->validate([
            'discount_code'      => "required|string|max:50|unique:discounts,discount_code,{$discountId},discount_id",
            'discoun_type'       => 'required|in:percentage,fixed_amount',
            'discount_value'     => 'required|numeric|min:0',
            'starts_at'          => 'nullable|date',
            'ends_at'            => 'nullable|date|after_or_equal:starts_at',
            'usage_limit'        => 'nullable|integer|min:1',
            'per_user_limit'     => 'nullable|integer|min:1',
            'min_subtotal_penny' => 'nullable|integer|min:0',
            'is_active'          => 'sometimes|boolean',
        ]);
        $data['discount_code'] = strtoupper(trim($data['discount_code']));
        $data['is_active'] = $data['is_active'] ?? false;
        $discount->update($data);
        return back()->with('success', 'Discount updated.');
    }

    public function toggleActive($discountId)
    {
        $d = Discount::where('discount_id', $discountId)->firstOrFail();
        $d->is_active = !$d->is_active;
        $d->save();
        return back()->with('success', "Discount " . ($d->is_active ? 'activated' : 'deactivated') . ".");
    }
}
