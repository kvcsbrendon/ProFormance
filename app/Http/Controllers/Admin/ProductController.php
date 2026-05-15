<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\Inventory;
use App\Models\VariantCurrencyPrice;
use App\Models\VariantAttribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'variants.inventory'])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")
                  ->orWhereHas('brand', fn($b) => $b->where('brand_name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('brand'))  { $query->where('brand_id', $request->brand); }
        if ($request->filled('active')) { $query->where('is_active', $request->active); }

        $products = $query->paginate(20)->withQueryString();
        $brands = Brand::orderBy('brand_name')->get();
        return view('admin.products.index', compact('products', 'brands'));
    }

    public function create()
    {
        $brands = Brand::orderBy('brand_name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
        return view('admin.products.form', [
            'brands' => $brands, 'categories' => $categories,
            'selectedCategories' => [], 'product' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_name'        => 'required|string|max:255',
            'brand_id'            => 'required|exists:brands,brand_id',
            'product_description' => 'nullable|string',
            'short_description'   => 'nullable|string|max:500',
            'meta_title'          => 'nullable|string|max:200',
            'meta_description'    => 'nullable|string|max:300',
            'is_active'           => 'sometimes|boolean',
            'categories'          => 'nullable|array',
            'categories.*'        => 'exists:categories,category_id',
        ]);

        $product = Product::create([
            'product_name'        => $data['product_name'],
            'brand_id'            => $data['brand_id'],
            'product_description' => $data['product_description'] ?? null,
            'short_description'   => $data['short_description'] ?? null,
            'meta_title'          => $data['meta_title'] ?? null,
            'meta_description'    => $data['meta_description'] ?? null,
            'is_active'           => $data['is_active'] ?? true,
        ]);
        if (!empty($data['categories'])) { $product->categories()->sync($data['categories']); }

        return redirect()->route('admin.products.edit', $product->product_id)
            ->with('success', "Product created. Now add variants, images, and specs.");
    }

    public function edit($productId)
    {
        $product = Product::with([
            'variants.prices', 'variants.inventory', 'variants.images',
            'variants.options.attribute', 'categories',
        ])->where('product_id', $productId)->firstOrFail();

        $brands = Brand::orderBy('brand_name')->get();
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
        $selectedCategories = $product->categories->pluck('category_id')->toArray();
        $currencies = Currency::where('is_active', true)->orderBy('currency_code')->get();
        $specs = ProductSpecification::where('product_id', $productId)->orderBy('sort_order')->get();

        // Load all variant attributes with their options for the option assignment UI
        $variantAttributes = VariantAttribute::where('is_active', true)
            ->with(['options' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('selection_order')
            ->get();

        return view('admin.products.form', compact(
            'product', 'brands', 'categories', 'selectedCategories', 'currencies', 'specs',
            'variantAttributes'
        ));
    }

    public function update(Request $request, $productId)
    {
        $product = Product::where('product_id', $productId)->firstOrFail();
        $data = $request->validate([
            'product_name'        => 'required|string|max:255',
            'brand_id'            => 'required|exists:brands,brand_id',
            'product_description' => 'nullable|string',
            'short_description'   => 'nullable|string|max:500',
            'meta_title'          => 'nullable|string|max:200',
            'meta_description'    => 'nullable|string|max:300',
            'is_active'           => 'sometimes|boolean',
            'categories'          => 'nullable|array',
        ]);

        $product->update([
            'product_name'        => $data['product_name'],
            'brand_id'            => $data['brand_id'],
            'product_description' => $data['product_description'] ?? null,
            'short_description'   => $data['short_description'] ?? null,
            'meta_title'          => $data['meta_title'] ?? null,
            'meta_description'    => $data['meta_description'] ?? null,
            'is_active'           => $data['is_active'] ?? $product->is_active,
        ]);
        $product->categories()->sync($data['categories'] ?? []);
        return redirect()->route('admin.products.edit', $productId)->with('success', 'Product updated.');
    }

    public function toggleActive($productId)
    {
        $product = Product::where('product_id', $productId)->firstOrFail();
        $product->is_active = !$product->is_active;
        $product->save();
        return back()->with('success', "Product " . ($product->is_active ? 'activated' : 'deactivated') . ".");
    }

    // ═══════════════════════════════════════════
    // VARIANT CRUD
    // ═══════════════════════════════════════════

    public function storeVariant(Request $request, $productId)
    {
        $product = Product::where('product_id', $productId)->firstOrFail();
        $data = $request->validate([
            'title'    => 'nullable|string|max:64',
            'sku'      => 'required|string|max:64',
            'barcode'  => 'nullable|string|max:64',
            'is_active'=> 'sometimes|boolean',
            'stock'    => 'required|integer|min:0',
            'reorder'  => 'nullable|integer|min:0',
            'prices'   => 'required|array',
            'prices.*.currency_code' => 'required|string|size:3',
            'prices.*.price_penny'   => 'required|integer|min:0',
            'prices.*.was_price_penny' => 'nullable|integer|min:0',
            'options'  => 'nullable|array',
            'options.*' => 'nullable',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->product_id,
            'title'      => $data['title'] ?? null,
            'sku'        => $data['sku'],
            'barcode'    => $data['barcode'] ?? null,
            'is_active'  => $data['is_active'] ?? true,
        ]);

        Inventory::create([
            'variant_id'      => $variant->variant_id,
            'available_stock'  => $data['stock'],
            'reorder_point'    => $data['reorder'] ?? null,
        ]);

        foreach ($data['prices'] as $p) {
            VariantCurrencyPrice::create([
                'variant_id'      => $variant->variant_id,
                'currency_code'   => $p['currency_code'],
                'price_penny'     => $p['price_penny'],
                'was_price_penny' => $p['was_price_penny'] ?? null,
            ]);
        }

        // Assign variant options (e.g. Size: M, Colour: Black)
        if (!empty($data['options'])) {
            $optionIds = array_filter($data['options']);
            foreach ($optionIds as $optionId) {
                DB::table('variant_combinations')->insert([
                    'variant_id' => $variant->variant_id,
                    'option_id'  => $optionId,
                ]);
            }
        }

        return back()->with('success', "Variant \"{$variant->sku}\" added.");
    }

    public function updateVariant(Request $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('variant_id', $variantId)
            ->where('product_id', $productId)->firstOrFail();

        $data = $request->validate([
            'title'    => 'nullable|string|max:64',
            'sku'      => 'required|string|max:64',
            'barcode'  => 'nullable|string|max:64',
            'is_active'=> 'sometimes|boolean',
            'stock'    => 'required|integer|min:0',
            'reorder'  => 'nullable|integer|min:0',
            'prices'   => 'required|array',
            'prices.*.currency_code'   => 'required|string|size:3',
            'prices.*.price_penny'     => 'required|integer|min:0',
            'prices.*.was_price_penny' => 'nullable|integer|min:0',
            'options'  => 'nullable|array',
            'options.*' => 'nullable',
        ]);

        $variant->update([
            'title'    => $data['title'] ?? null,
            'sku'      => $data['sku'],
            'barcode'  => $data['barcode'] ?? null,
            'is_active'=> $data['is_active'] ?? true,
        ]);

        $variant->inventory()->updateOrCreate(
            ['variant_id' => $variant->variant_id],
            ['available_stock' => $data['stock'], 'reorder_point' => $data['reorder'] ?? null]
        );

        foreach ($data['prices'] as $p) {
            VariantCurrencyPrice::updateOrCreate(
                ['variant_id' => $variant->variant_id, 'currency_code' => $p['currency_code']],
                ['price_penny' => $p['price_penny'], 'was_price_penny' => $p['was_price_penny'] ?? null]
            );
        }

        // Sync variant options
        DB::table('variant_combinations')->where('variant_id', $variant->variant_id)->delete();
        if (!empty($data['options'])) {
            $optionIds = array_filter($data['options']);
            foreach ($optionIds as $optionId) {
                DB::table('variant_combinations')->insert([
                    'variant_id' => $variant->variant_id,
                    'option_id'  => $optionId,
                ]);
            }
        }

        return back()->with('success', "Variant \"{$variant->sku}\" updated.");
    }

    public function destroyVariant($productId, $variantId)
    {
        $variant = ProductVariant::where('variant_id', $variantId)
            ->where('product_id', $productId)->firstOrFail();

        $variant->images()->delete();
        $variant->prices()->delete();
        Inventory::where('variant_id', $variantId)->delete();
        DB::table('variant_combinations')->where('variant_id', $variantId)->delete();
        $variant->delete();

        return back()->with('success', 'Variant deleted.');
    }

    // ═══════════════════════════════════════════
    // IMAGE MANAGEMENT
    // ═══════════════════════════════════════════

    public function storeImage(Request $request, $productId, $variantId)
    {
        $variant = ProductVariant::where('variant_id', $variantId)
            ->where('product_id', $productId)->firstOrFail();

        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        $uploadPath = public_path('images/products');
        if (!File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $maxSort = ProductImage::where('variant_id', $variantId)->max('sort_order') ?? -1;

        foreach ($request->file('images') as $file) {
            $maxSort++;
            $filename = "p{$productId}_v{$variantId}_" . time() . "_{$maxSort}." . $file->getClientOriginalExtension();
            $file->move($uploadPath, $filename);

            ProductImage::create([
                'variant_id' => $variantId,
                'image_url'  => 'products/' . $filename,
                'alt_text'   => $variant->title ?? "Product image",
                'sort_order' => $maxSort,
            ]);
        }

        return back()->with('success', 'Images uploaded.');
    }

    public function destroyImage($productId, $imageId)
    {
        $image = ProductImage::where('image_id', $imageId)->firstOrFail();

        $filePath = public_path('images/' . $image->image_url);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $image->delete();
        return back()->with('success', 'Image deleted.');
    }

    // ═══════════════════════════════════════════
    // SPECIFICATIONS
    // ═══════════════════════════════════════════

    public function updateSpecs(Request $request, $productId)
    {
        Product::where('product_id', $productId)->firstOrFail();

        $data = $request->validate([
            'specs'              => 'nullable|array',
            'specs.*.spec_group' => 'nullable|string|max:100',
            'specs.*.spec_name'  => 'required|string|max:100',
            'specs.*.spec_value' => 'required|string|max:255',
            'specs.*.sort_order' => 'nullable|integer',
        ]);

        ProductSpecification::where('product_id', $productId)->delete();

        foreach (($data['specs'] ?? []) as $i => $spec) {
            ProductSpecification::create([
                'product_id' => $productId,
                'spec_group' => $spec['spec_group'] ?? null,
                'spec_name'  => $spec['spec_name'],
                'spec_value' => $spec['spec_value'],
                'sort_order' => $spec['sort_order'] ?? $i,
            ]);
        }

        return back()->with('success', 'Specifications saved.');
    }
}