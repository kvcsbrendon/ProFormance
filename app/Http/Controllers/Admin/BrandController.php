<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::withCount('products')
            ->orderBy('brand_name')
            ->get();

        return view('admin.brands.index', compact('brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_name'       => 'required|string|max:100|unique:brands,brand_name',
            'brand_description'=> 'nullable|string|max:500',
            'is_active'        => 'sometimes|in:1',
        ]);

        Brand::create([
            'brand_name'        => $data['brand_name'],
            'slug'              => Str::slug($data['brand_name']),
            'brand_description' => $data['brand_description'] ?? null,
            'is_active'         => $request->has('is_active'),
        ]);

        return back()->with('success', "Brand \"{$data['brand_name']}\" created.");
    }

    public function update(Request $request, $brandId)
    {
        $brand = Brand::where('brand_id', $brandId)->firstOrFail();

        $data = $request->validate([
            'brand_name'       => 'required|string|max:100|unique:brands,brand_name,' . $brand->brand_id . ',brand_id',
            'brand_description'=> 'nullable|string|max:500',
            'is_active'        => 'sometimes|in:1',
        ]);

        $brand->update([
            'brand_name'        => $data['brand_name'],
            'slug'              => Str::slug($data['brand_name']),
            'brand_description' => $data['brand_description'] ?? null,
            'is_active'         => $request->has('is_active'),
        ]);

        return back()->with('success', "Brand updated.");
    }

    public function destroy($brandId)
    {
        $brand = Brand::withCount('products')->where('brand_id', $brandId)->firstOrFail();

        if ($brand->products_count > 0) {
            return back()->withErrors(['brand' => "Cannot delete \"{$brand->brand_name}\" — it has {$brand->products_count} product(s). Reassign them first."]);
        }

        $brand->delete();
        return back()->with('success', "Brand \"{$brand->brand_name}\" deleted.");
    }
}