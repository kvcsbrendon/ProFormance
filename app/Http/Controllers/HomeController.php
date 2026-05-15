<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



use App\Models\Brand;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index()
    {
        $currency = strtoupper(session('currency', 'GBP'));
        $vatRate = $this->displayVatRateForCurrency($currency);
        $vatLabel = $vatRate > 0 ? 'incl. VAT' : 'excl. VAT';

        $wishlistVariantIds = [];
        if (Auth::check()) {
            $wl = Wishlist::where('user_id', Auth::user()->user_id)->first();
            if ($wl) {
                $wishlistVariantIds = WishlistItem::where('wishlists_id', $wl->wishlist_id)
                    ->pluck('variant_id')
                    ->all();
            }
        }

        $products = DB::table('products as p')
            ->join('product_variants as pv', 'pv.product_id', '=', 'p.product_id')
            ->join('variant_currency_prices as vcp', function ($join) use ($currency) {
                $join->on('vcp.variant_id', '=', 'pv.variant_id')
                     ->where('vcp.currency_code', '=', $currency);
            })
            ->leftJoin('product_images as pi', 'pi.variant_id', '=', 'pv.variant_id')
            ->select(
                'p.product_id',
                'p.product_name',
                'pv.variant_id',
                'vcp.price_penny',
                'pi.image_url'
            )
            ->where('p.is_active', 1)
            ->where('pv.is_active', 1)
            ->groupBy('p.product_id', 'p.product_name', 'pv.variant_id', 'vcp.price_penny', 'pi.image_url')
            ->orderBy('p.product_id')
            ->get();

        $heroProducts = Product::with([
                'activeVariants',
                'activeVariants.prices',
                'activeVariants.images',
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $newProducts = Product::with([
                'activeVariants',
                'activeVariants.prices',
                'activeVariants.images',
            ])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $bestPrice = null;
        if ($newProducts->isNotEmpty()) {
            $firstProduct = $newProducts->first();

            if (method_exists($firstProduct, 'bestPriceForCurrency')) {
                $bestPrice = $firstProduct->bestPriceForCurrency($currency);
            }
        }

        $products = $products->map(function ($p) use ($vatRate) {
            $net = (int) $p->price_penny;
            $p->display_price_penny = (int) round($net * (1 + $vatRate));
            return $p;
        });

        $featuredProducts = Product::where('is_active', true)
            ->with(['brand', 'variants.prices', 'variants.images'])
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $testimonials = Review::where('is_approved', true)
            ->with(['user', 'product.brand'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
        

        return view('home', [
            'products'     => $products,
            'heroProducts' => $heroProducts,
            'newProducts'  => $newProducts,
            'bestPrice'    => $bestPrice,
            'vatLabel'     => $vatLabel,
            'vatRate'      => $vatRate,
            'testimonials' => $testimonials,
        ]);
    }

    private function displayVatRateForCurrency(string $currency): float
    {
        return strtoupper($currency) === 'GBP' ? 0.20 : 0.00;
    }


    private function priceForDisplayPenny(int $netPenny, float $vatRate): int
    {
        return (int) round($netPenny * (1 + $vatRate));
    }

}
