<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Review;
use App\Models\ReviewHelpful;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        // Get currency at the very start and ensure it's consistent throughout
        $currency = strtoupper(session('currency', 'GBP'));
        $vatRate  = $currency === 'GBP' ? 0.20 : 0.00;
        $vatLabel = $vatRate > 0 ? 'incl. VAT' : 'excl. VAT';
        
        
       $toNetPennies = function ($value) use ($vatRate) {
            if ($value === null || $value === '') return null;
            
            // Remove any non-numeric characters except decimal point
            $value = preg_replace('/[^\d.]/', '', (string) $value);
            if ($value === '' || $value === '.') return null;
            
            // The value from the frontend includes VAT. 
            // We divide by (1 + vatRate) to get the net price the database expects.
            $netFloat = (float) $value / (1 + $vatRate);
            
            // Round to 2 decimal places, then convert to pennies
            return (int) round($netFloat * 100);
        };

        // Build the base query with currency-specific eager loading
        $query = Product::with([
                'activeVariants',
                'activeVariants.prices' => function ($q) use ($currency) {
                    $q->where('currency_code', $currency);
                },
                'activeVariants.images',
                'categories',
                'brand'
            ])
            ->where('is_active', true);

        // Search filter
        if ($request->filled('search')) {
            $searchTerm = trim($request->input('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->where('product_name', 'like', "%{$searchTerm}%")
                ->orWhere('product_description', 'like', "%{$searchTerm}%")
                ->orWhere('short_description', 'like', "%{$searchTerm}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $input = (array) $request->category;
            $idInputs   = array_filter($input, fn ($c) => is_numeric($c));
            $slugInputs = array_filter($input, fn ($c) => !is_numeric($c));
            
            $baseCategoryIds = Category::query()
                ->when($idInputs, fn ($q) => $q->whereIn('category_id', $idInputs))
                ->when($slugInputs, fn ($q) => $q->orWhereIn('slug', $slugInputs))
                ->pluck('category_id')
                ->all();

            if (!empty($baseCategoryIds)) {
                $allCategoryIds = Category::query()
                    ->whereIn('category_id', $baseCategoryIds)
                    ->orWhereIn('parent_id', $baseCategoryIds)
                    ->pluck('category_id')
                    ->all();

                $query->whereHas('categories', function ($q) use ($allCategoryIds) {
                    $q->whereIn('categories.category_id', $allCategoryIds);
                });
            }
        }

        // Brand filter
        if ($request->filled('brand')) {
            $input = (array) $request->brand;
            $idInputs   = array_filter($input, fn($b) => is_numeric($b));
            $slugInputs = array_filter($input, fn($b) => !is_numeric($b));

            $brandIds = Brand::query()
                ->when($idInputs, fn($q) => $q->whereIn('brand_id', $idInputs))
                ->when($slugInputs, fn($q) => $q->orWhereIn('slug', $slugInputs))
                ->pluck('brand_id')
                ->all();

            if (!empty($brandIds)) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        // Deals filter - ensure it respects currency
        if ($request->boolean('deals')) {
            $query->whereHas('activeVariants.prices', function ($q) use ($currency) {
                $q->where('currency_code', $currency)
                ->whereNotNull('was_price_penny')
                ->whereColumn('was_price_penny', '>', 'price_penny');
            });
            
            // Also ensure we only get products where this variant is the cheapest
            $query->whereExists(function ($subQuery) use ($currency) {
                $subQuery->select(DB::raw(1))
                    ->from('product_variants as pv')
                    ->join('variant_currency_prices as vcp', 'pv.variant_id', '=', 'vcp.variant_id')
                    ->whereColumn('pv.product_id', 'products.product_id')
                    ->where('pv.is_active', true)
                    ->where('vcp.currency_code', $currency)
                    ->whereRaw('vcp.price_penny = (
                        SELECT MIN(vcp2.price_penny)
                        FROM product_variants pv2
                        JOIN variant_currency_prices vcp2 ON vcp2.variant_id = pv2.variant_id
                        WHERE pv2.product_id = products.product_id
                            AND pv2.is_active = 1
                            AND vcp2.currency_code = ?
                    )', [$currency]);
            });
        }

        // Price filter - CRITICAL: Ensure it only considers the current currency
        $minPennies = $toNetPennies($request->input('min_price'));
        $maxPennies = $toNetPennies($request->input('max_price'));

        if ($minPennies !== null || $maxPennies !== null) {
            $priceFilterClosure = function ($sub) use ($currency, $minPennies, $maxPennies) {
                $sub->select(DB::raw('MIN(vcp.price_penny)'))
                    ->from('product_variants as pv')
                    ->join('variant_currency_prices as vcp', 'pv.variant_id', '=', 'vcp.variant_id')
                    ->whereColumn('pv.product_id', 'products.product_id')
                    ->where('pv.is_active', true)
                    ->where('vcp.currency_code', $currency);
                    
                if ($minPennies !== null && $maxPennies !== null) {
                    $sub->havingRaw('MIN(vcp.price_penny) BETWEEN ? AND ?', [$minPennies, $maxPennies]);
                } elseif ($minPennies !== null) {
                    $sub->havingRaw('MIN(vcp.price_penny) >= ?', [$minPennies]);
                } elseif ($maxPennies !== null) {
                    $sub->havingRaw('MIN(vcp.price_penny) <= ?', [$maxPennies]);
                }
            };

            // Apply to the main query
            $query->whereExists($priceFilterClosure);
        }

        // Set page title
        $pageTitle = 'All Products';
        if ($request->filled('search')) {
            $pageTitle = 'Search Results for "' . $request->search . '"';
        } elseif ($request->boolean('deals')) {
            $pageTitle = 'Hot Deals';
        } elseif ($request->filled('category')) {
            $selected = (array) $request->category;
            if (count($selected) === 1) {
                $one = Category::query()
                    ->where('category_id', $selected[0])
                    ->orWhere('slug', $selected[0])
                    ->first();
                $pageTitle = $one?->category_name ?? 'Category';
            } else {
                $pageTitle = 'Selected Categories';
            }
        }

        // Sorting - ensure it uses the correct currency for price sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_low':
            case 'price_high':
                $direction = $sort === 'price_low' ? 'asc' : 'desc';
                
                // Use a subquery to get the minimum price for the current currency
                $query->addSelect([
                    'min_price_penny' => DB::table('product_variants as pv')
                        ->join('variant_currency_prices as vcp', 'pv.variant_id', '=', 'vcp.variant_id')
                        ->whereColumn('pv.product_id', 'products.product_id')
                        ->where('pv.is_active', true)
                        ->where('vcp.currency_code', $currency)
                        ->selectRaw('MIN(vcp.price_penny)')
                ])->orderBy('min_price_penny', $direction);
                break;

            case 'name_asc':
                $query->orderBy('product_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('product_name', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage)->appends($request->query());

        // Get main categories with counts that respect all filters AND currency
        $mainCategories = Category::query()
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Define the count filters closure that respects currency
        $applyCountFilters = function ($countQuery) use ($request, $currency, $toNetPennies) {
            $minPennies = $toNetPennies($request->input('min_price'));
            $maxPennies = $toNetPennies($request->input('max_price'));

            // Apply price filter with currency
            $countQuery->whereHas('activeVariants.prices', function ($q) use ($currency, $minPennies, $maxPennies) {
                $q->where('currency_code', $currency);
                
                if ($minPennies !== null) {
                    $q->where('price_penny', '>=', $minPennies);
                }
                if ($maxPennies !== null) {
                    $q->where('price_penny', '<=', $maxPennies);
                }
            });

            // Apply search filter
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $countQuery->where(function ($q) use ($searchTerm) {
                    $q->where('product_name', 'like', "%{$searchTerm}%")
                    ->orWhere('product_description', 'like', "%{$searchTerm}%")
                    ->orWhere('short_description', 'like', "%{$searchTerm}%");
                });
            }

            // Apply brand filter
            if ($request->filled('brand')) {
                $input = (array) $request->brand;
                $idInputs   = array_filter($input, fn ($b) => is_numeric($b));
                $slugInputs = array_filter($input, fn ($b) => !is_numeric($b));

                $brandIds = Brand::query()
                    ->when($idInputs, fn ($q) => $q->whereIn('brand_id', $idInputs))
                    ->when($slugInputs, fn ($q) => $q->orWhereIn('slug', $slugInputs))
                    ->pluck('brand_id')
                    ->all();

                if (!empty($brandIds)) {
                    $countQuery->whereIn('brand_id', $brandIds);
                }
            }

            // Apply deals filter if active
            if ($request->boolean('deals')) {
                $countQuery->whereHas('activeVariants.prices', function ($q) use ($currency) {
                    $q->where('currency_code', $currency)
                    ->whereNotNull('was_price_penny')
                    ->whereColumn('was_price_penny', '>', 'price_penny');
                });
            }

            return $countQuery;
        };

        // Apply counts to categories
        $mainCategories->each(function ($parent) use ($applyCountFilters) {
            $parent->children->each(function ($child) use ($applyCountFilters) {
                $countQuery = Product::query()
                    ->where('products.is_active', true)
                    ->whereHas('categories', fn ($q) => $q->where('categories.category_id', $child->category_id));

                $child->products_count = $applyCountFilters($countQuery)
                    ->distinct('products.product_id')
                    ->count('products.product_id');
            });

            $parent->products_total = (int) $parent->children->sum('products_count');
        });

        // Get brands
        $brands = Brand::where('is_active', true)->get();
        
        // Get price range for the CURRENT CURRENCY only
        $priceRange = DB::table('variant_currency_prices')
            ->join('product_variants', 'variant_currency_prices.variant_id', '=', 'product_variants.variant_id')
            ->join('products', 'product_variants.product_id', '=', 'products.product_id')
            ->where('products.is_active', true)
            ->where('product_variants.is_active', true)
            ->where('variant_currency_prices.currency_code', $currency)
            ->select(
                DB::raw('MIN(variant_currency_prices.price_penny) / 100 as min_price'),
                DB::raw('MAX(variant_currency_prices.price_penny) / 100 as max_price')
            )
            ->first();

        $minNetFloat = ($dbPriceRange->min_penny ?? 0) / 100;
        $maxNetFloat = ($dbPriceRange->max_penny ?? 100000) / 100;

        $priceRange = (object) [
            'min_price' => round($minNetFloat * (1 + $vatRate), 2),
            'max_price' => round($maxNetFloat * (1 + $vatRate), 2)
        ];

        // Wishlist variant IDs for current user
        $wishlistVariantIds = [];
        if (Auth::check()) {
            $wl = Wishlist::where('user_id', Auth::user()->user_id)->first();
            if ($wl) {
                $wishlistVariantIds = WishlistItem::where('wishlists_id', $wl->wishlist_id)
                    ->pluck('variant_id')
                    ->all();
            }
        }

        return view('products.index', compact(
            'products', 
            'mainCategories', 
            'brands',
            'priceRange',
            'pageTitle',
            'vatRate',
            'vatLabel',
            'wishlistVariantIds',
            'currency' // Pass currency to view for display purposes
        ));
    }

    private function displayVatRateForCurrency(string $currency): float
    {
        return strtoupper($currency) === 'GBP' ? 0.20 : 0.00;
    }

    private function priceForDisplayPenny(int $netPenny, float $vatRate): int
    {
        return (int) round($netPenny * (1 + $vatRate));
    }

    
    public function show(Product $product)
    {
        if (!$product->is_active) {
            return response()->view('errors.404-product', [], 404);
        }

        $currency = strtoupper(session('currency', 'GBP'));
        $vatRate  = $currency === 'GBP' ? 0.20 : 0.00;
        $vatLabel = $vatRate > 0 ? 'incl. VAT' : 'excl. VAT';

        $product->load([
            'activeVariants.options.attribute',
            'activeVariants.images',
            'activeVariants.prices',
            'activeVariants.inventory',
            'brand',
            'categories',
        ]);

        $variants = $product->activeVariants;
        $defaultVariant = $variants->first();
        $bestPrice = $product->bestPriceForCurrency($currency);

        $attributeMap = [];
        foreach ($variants as $variant) {
            foreach ($variant->options as $option) {
                $attrName = $option->attribute->display_name;
                $attrId   = $option->attribute->attribute_id;
                $optValue = $option->display_value;
                $optId    = $option->option_id;

                if (!isset($attributeMap[$attrId])) {
                    $attributeMap[$attrId] = [
                        'name'    => $attrName,
                        'order'   => $option->attribute->selection_order,
                        'options' => [],
                    ];
                }

                if (!isset($attributeMap[$attrId]['options'][$optId])) {
                    $attributeMap[$attrId]['options'][$optId] = [
                        'value'      => $optValue,
                        'sort_order' => $option->sort_order,
                    ];
                }
            }
        }

        uasort($attributeMap, fn($a, $b) => $a['order'] <=> $b['order']);
        foreach ($attributeMap as &$attr) {
            uasort($attr['options'], fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        }
        unset($attr);

        $variantsJson = $variants->map(function ($v) use ($currency, $vatRate) {
            $price = $v->priceForCurrency($currency);
            $inv   = $v->inventory;
            $stock = $inv ? max(0, $inv->available_stock - $inv->stock_allocated) : 0;

            return [
                'id'         => $v->variant_id,
                'sku'        => $v->sku,
                'title'      => $v->title,
                'stock'      => $stock,
                'options'    => $v->options->pluck('option_id')->toArray(),
                'price'      => $price ? round(($price->price_penny / 100) * (1 + $vatRate), 2) : null,
                'was_price'  => ($price && $price->was_price_penny) ? round(($price->was_price_penny / 100) * (1 + $vatRate), 2) : null,
                'images'     => $v->images->sortBy('sort_order')->map(fn($img) => [
                    'url' => $img->image_url,
                    'alt' => $img->alt_text ?? $v->title,
                ])->values()->toArray(),
            ];
        })->values()->toArray();

        $allImages = $variants->flatMap(function ($v) {
            return $v->images->map(fn($img) => [
                'url'        => $img->image_url,
                'alt'        => $img->alt_text ?? 'Product image',
                'variant_id' => $v->variant_id,
                'sort_order' => $img->sort_order,
            ]);
        })->sortBy('sort_order')->values()->toArray();

        if (empty($allImages)) {
            $allImages = [['url' => 'placeholders/product-placeholder.jpg', 'alt' => 'No image available', 'variant_id' => null, 'sort_order' => 0]];
        }

        $specifications = DB::table('product_specifications')
            ->where('product_id', $product->product_id)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('spec_group');
        $defaultStock = 0;
        if ($defaultVariant && $defaultVariant->inventory) {
            $defaultStock = max(0, $defaultVariant->inventory->available_stock - $defaultVariant->inventory->stock_allocated);
        }
        
        $sort = request('review_sort', 'newest');

        $reviewsQuery = Review::where('product_id', $product->product_id)
            ->where('is_approved', true)
            ->with(['user', 'helpfulVotes', 'images'])
            ->withCount('helpfulVotes'); // Count votes for the "Most Helpful" sort

        match ($sort) {
            'oldest'         => $reviewsQuery->orderBy('created_at', 'asc'),
            'highest_rating' => $reviewsQuery->orderBy('rating', 'desc')->orderBy('created_at', 'desc'),
            'lowest_rating'  => $reviewsQuery->orderBy('rating', 'asc')->orderBy('created_at', 'desc'),
            'most_helpful'   => $reviewsQuery->orderBy('helpful_votes_count', 'desc')->orderBy('created_at', 'desc'),
            default          => $reviewsQuery->orderBy('created_at', 'desc'), // 'newest' fallback
        };

        $reviews = $reviewsQuery->get();

        if (Auth::check()) {
            $userId = Auth::user()->user_id;
            foreach ($reviews as $review) {
                // Check if a vote record exists for this user on this review
                $hasVoted = $review->helpfulVotes->where('user_id', $userId)->isNotEmpty();
                $review->user_vote = $hasVoted; // Will be true or false
            }
        }

        $reviewStats = [
            'count' => $reviews->count(),
            'avg'   => $reviews->count() > 0 ? round($reviews->avg('rating'), 1) : 0,
        ];

        $userReview = null;
        if (Auth::check()) {
            $userReview = Review::where('user_id', Auth::user()->user_id)
                ->where('product_id', $product->product_id)
                ->first();
        }
        $inWishlist = false;
        if (Auth::check() && $defaultVariant) {
            $wishlist = Wishlist::where('user_id', Auth::user()->user_id)->first();
            if ($wishlist) {
                $inWishlist = WishlistItem::where('wishlists_id', $wishlist->wishlist_id)
                    ->where('variant_id', $defaultVariant->variant_id)
                    ->exists();
            }
        }
        $categoryIds = $product->categories->pluck('category_id')->toArray();

        $parentCategoryIds = $product->categories
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->toArray();

        $siblingCategoryIds = [];
        if (!empty($parentCategoryIds)) {
            $siblingCategoryIds = \App\Models\Category::whereIn('parent_id', $parentCategoryIds)
                ->where('is_active', true)
                ->pluck('category_id')
                ->toArray();
        }

        $allRelatedCategoryIds = array_unique(array_merge($categoryIds, $siblingCategoryIds, $parentCategoryIds));

        $relatedProducts = Product::where('is_active', true)
            ->where('product_id', '!=', $product->product_id)
            ->whereHas('categories', function ($q) use ($allRelatedCategoryIds) {
                $q->whereIn('categories.category_id', $allRelatedCategoryIds);
            })
            ->with(['activeVariants.prices', 'activeVariants.images', 'brand'])
            ->limit(8)
            ->inRandomOrder()
            ->get()
            ->map(function ($p) use ($currency, $vatRate) {
                $price = $p->bestPriceForCurrency($currency);
                $firstImage = $p->activeVariants->flatMap->images->sortBy('sort_order')->first();

                return [
                    'product_id'   => $p->product_id,
                    'product_name' => $p->product_name,
                    'brand_name'   => $p->brand?->brand_name,
                    'slug'         => $p->product_id,
                    'image'        => $firstImage?->image_url ?? 'placeholders/product-placeholder.jpg',
                    'price'        => $price ? round($price->price * (1 + $vatRate), 2) : null,
                    'was_price'    => ($price && $price->was) ? round($price->was * (1 + $vatRate), 2) : null,
                    'symbol'       => $price->symbol ?? '£',
                ];
            });


        $bulkTiers = [];
        foreach ($variants as $v) {
            $tiers = \App\Models\BulkPricing::getTiers($v->variant_id, $currency);
            if ($tiers->isNotEmpty()) {
                $bulkTiers[$v->variant_id] = $tiers->map(function ($t) use ($vatRate) {
                    return [
                        'min_quantity' => $t->min_quantity,
                        'price_penny'  => $t->price_penny,
                        'price'        => round(($t->price_penny / 100) * (1 + $vatRate), 2),
                    ];
                })->toArray();
            }
        }

        $defaultBulkTiers = $bulkTiers[$defaultVariant?->variant_id] ?? [];


        return view('products.show', compact(
            'product',
            'bestPrice',
            'vatRate',
            'vatLabel',
            'variants',
            'defaultVariant',
            'attributeMap',
            'variantsJson',
            'allImages',
            'specifications',
            'defaultStock',
            'currency',
            'reviews',
            'reviewStats',
            'userReview',
            'inWishlist',
            'relatedProducts',
            'bulkTiers',
            'defaultBulkTiers'
        ));
    }

    public function getVariant(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,variant_id',
        ]);

        $currency = strtoupper(session('currency', 'GBP'));
        $vatRate  = $currency === 'GBP' ? 0.20 : 0.00;

        $variant = ProductVariant::with(['images', 'prices', 'inventory', 'options'])->find($request->variant_id);
        $price   = $variant->priceForCurrency($currency);
        $inv     = $variant->inventory;
        $stock   = $inv ? max(0, $inv->available_stock - $inv->stock_allocated) : 0;

        return response()->json([
            'id'        => $variant->variant_id,
            'sku'       => $variant->sku,
            'title'     => $variant->title,
            'stock'     => $stock,
            'price'     => $price ? round(($price->price_penny / 100) * (1 + $vatRate), 2) : null,
            'was_price' => ($price && $price->was_price_penny) ? round(($price->was_price_penny / 100) * (1 + $vatRate), 2) : null,
            'images'    => $variant->images->sortBy('sort_order')->map(fn($img) => [
                'url' => $img->image_url,
                'alt' => $img->alt_text ?? $variant->title,
            ])->values()->toArray(),
        ]);
    }

    public function category(Request $request, string $slug)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        
        $request->merge([
            'category' => [$category->category_id],
        ]);

        return $this->index($request);
    }
}
