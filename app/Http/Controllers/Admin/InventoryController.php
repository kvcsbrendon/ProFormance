<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Inventory::with(['variant.product.brand'])
            ->join('product_variants', 'inventory.variant_id', '=', 'product_variants.variant_id')
            ->join('products', 'product_variants.product_id', '=', 'products.product_id')
            ->select('inventory.*');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('products.product_name', 'like', "%{$s}%")
                  ->orWhere('product_variants.sku', 'like', "%{$s}%")
                  ->orWhere('product_variants.title', 'like', "%{$s}%");
            });
        }
        if ($request->filled('stock_filter')) {
            match($request->stock_filter) {
                'out' => $query->where('inventory.available_stock', '<=', 0),
                'low' => $query->where('inventory.available_stock', '>', 0)
                    ->whereColumn('inventory.available_stock', '<=', DB::raw('COALESCE(inventory.reorder_point, 5)')),
                'ok'  => $query->where('inventory.available_stock', '>', 0)
                    ->whereColumn('inventory.available_stock', '>', DB::raw('COALESCE(inventory.reorder_point, 5)')),
                default => null,
            };
        }

        $inventory = $query->orderBy('products.product_name')->paginate(30)->withQueryString();
        $outOfStock = Inventory::where('available_stock', '<=', 0)->count();
        $lowStock = Inventory::where('available_stock', '>', 0)
            ->whereColumn('available_stock', '<=', DB::raw('COALESCE(reorder_point, 5)'))->count();
        $totalVariants = Inventory::count();

        return view('admin.inventory.index', compact('inventory', 'outOfStock', 'lowStock', 'totalVariants'));
    }

    public function update(Request $request, $variantId)
    {
        $data = $request->validate([
            'available_stock' => 'required|integer|min:0',
            'reorder_point'   => 'nullable|integer|min:0',
        ]);
        Inventory::where('variant_id', $variantId)->firstOrFail()->update($data);
        return back()->with('success', 'Stock updated.');
    }
}
