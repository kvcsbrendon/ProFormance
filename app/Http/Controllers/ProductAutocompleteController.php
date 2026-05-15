<?php
namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductAutocompleteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '' || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $q = mb_substr($q, 0, 80);

        $results = ProductVariant::query()
            ->select(['variant_id', 'product_id', 'sku', 'title'])
            ->with(['product:product_id,product_name,is_active'])
            ->where('is_active', 1)
            ->whereHas('product', fn ($p) => $p->where('is_active', 1))
            ->where(function ($query) use ($q) {
                $query->where('sku', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhereHas('product', fn ($p) => $p->where('product_name', 'like', "%{$q}%"));
            })
            ->orderBy('sku')
            ->limit(10)
            ->get()
            ->map(function ($v) {
                $name = $v->product?->product_name ?? 'Unknown product';
                $title = $v->title ? " — {$v->title}" : '';
                return [
                    'variant_id' => $v->variant_id,
                    'product_id' => $v->product_id,
                    'label'      => "{$v->sku} — {$name}{$title}",
                ];
            });

        return response()->json($results);
    }
}
