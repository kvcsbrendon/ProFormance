<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\Address;

class WishlistController extends Controller
{

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Please sign in to view your wishlists.');
        }

        $user = Auth::user();

        $wishlists = Wishlist::where('user_id', $user->user_id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get();

        return view('wishlist.index', compact('wishlists'));
    }

    public function show($wishlistId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $wishlist = Wishlist::where('user_id', Auth::user()->user_id)
            ->where('wishlist_id', $wishlistId)
            ->firstOrFail();

        $items = WishlistItem::where('wishlists_id', $wishlist->wishlist_id)
            ->with(['variant.product.brand', 'variant.images', 'variant.prices'])
            ->orderByDesc('created_at')
            ->get();

        $addresses = Address::where('user_id', Auth::user()->user_id)->get();

        $shareUrl = $wishlist->getShareUrl();

        return view('wishlist.show', compact('wishlist', 'items', 'addresses', 'shareUrl'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'wishlist_name' => 'required|string|max:100',
            'slug'          => 'nullable|string|max:150',
        ]);

        $slug = !empty($data['slug'])
            ? Wishlist::generateUniqueSlug($data['slug'])
            : Wishlist::generateUniqueSlug($data['wishlist_name']);

        Wishlist::create([
            'user_id'       => Auth::user()->user_id,
            'wishlist_name' => $data['wishlist_name'],
            'is_public'     => false,
            'slug'          => $slug,
        ]);

        return back()->with('success', 'Wishlist created!');
    }

    public function update(Request $request, $wishlistId)
    {
        $wishlist = Wishlist::where('user_id', Auth::user()->user_id)
            ->where('wishlist_id', $wishlistId)
            ->firstOrFail();

        $data = $request->validate([
            'wishlist_name'       => 'required|string|max:100',
            'slug'                => 'nullable|string|max:150',
            'is_public'           => 'sometimes|boolean',
            'delivery_address_id' => 'nullable|integer|exists:addresses,address_id',
        ]);

        $newSlug = $data['slug'] ?? null;
        if ($newSlug && $newSlug !== $wishlist->slug) {
            $newSlug = Str::slug($newSlug);
            if (empty($newSlug)) {
                $newSlug = Wishlist::generateUniqueSlug($data['wishlist_name'], $wishlist->wishlist_id);
            } else {
                $newSlug = Wishlist::generateUniqueSlug($newSlug, $wishlist->wishlist_id);
            }
        } elseif (!$wishlist->slug) {
            $newSlug = Wishlist::generateUniqueSlug($data['wishlist_name'], $wishlist->wishlist_id);
        } else {
            $newSlug = $wishlist->slug;
        }

        if (!empty($data['delivery_address_id'])) {
            $addressBelongsToUser = Address::where('address_id', $data['delivery_address_id'])
                ->where('user_id', Auth::user()->user_id)
                ->exists();

            if (!$addressBelongsToUser) {
                return back()->withErrors(['delivery_address_id' => 'Invalid address.']);
            }
        }

        $wishlist->update([
            'wishlist_name'       => $data['wishlist_name'],
            'slug'                => $newSlug,
            'is_public'           => $data['is_public'] ?? $wishlist->is_public,
            'delivery_address_id' => $data['delivery_address_id'] ?? null,
        ]);

        return back()->with('success', 'Wishlist updated!');
    }


    public function destroy($wishlistId)
    {
        $wishlist = Wishlist::where('user_id', Auth::user()->user_id)
            ->where('wishlist_id', $wishlistId)
            ->firstOrFail();

        WishlistItem::where('wishlists_id', $wishlist->wishlist_id)->delete();
        $wishlist->delete();

        return redirect()->route('wishlist.index')->with('success', 'Wishlist deleted.');
    }


    public function toggleShare($wishlistId)
    {
        $wishlist = Wishlist::where('user_id', Auth::user()->user_id)
            ->where('wishlist_id', $wishlistId)
            ->firstOrFail();

        if (!$wishlist->is_public && !$wishlist->slug) {
            $wishlist->slug = Wishlist::generateUniqueSlug($wishlist->wishlist_name, $wishlist->wishlist_id);
        }

        $wishlist->is_public = !$wishlist->is_public;
        $wishlist->save();

        return back()->with('success',
            $wishlist->is_public
                ? 'Wishlist is now public — share the link!'
                : 'Wishlist is now private.'
        );
    }

    public function toggle(Request $request)
    {
        if (!Auth::check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Please sign in'], 401);
            }
            return redirect()->route('login')
                ->with('error', 'Please sign in to use the wishlist.');
        }

        $request->validate([
            'variant_id'  => 'required|exists:product_variants,variant_id',
            'wishlist_id' => 'nullable|integer',
        ]);

        $wishlist = $this->resolveWishlist($request->wishlist_id);

        $existing = WishlistItem::where('wishlists_id', $wishlist->wishlist_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message    = 'Removed from wishlist';
            $inWishlist = false;
        } else {
            WishlistItem::create([
                'wishlists_id' => $wishlist->wishlist_id,
                'variant_id'   => $request->variant_id,
            ]);
            $message    = 'Added to wishlist';
            $inWishlist = true;
        }

        $count = WishlistItem::where('wishlists_id', $wishlist->wishlist_id)->count();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'message'        => $message,
                'wishlist_count' => $count,
                'in_wishlist'    => $inWishlist,
            ]);
        }

        return back()->with('success', $message);
    }

    public function remove(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'variant_id'  => 'required|exists:product_variants,variant_id',
            'wishlist_id' => 'required|integer',
        ]);

        $wishlist = Wishlist::where('user_id', Auth::user()->user_id)
            ->where('wishlist_id', $request->wishlist_id)
            ->firstOrFail();

        WishlistItem::where('wishlists_id', $wishlist->wishlist_id)
            ->where('variant_id', $request->variant_id)
            ->delete();

        return back()->with('success', 'Removed from wishlist');
    }


    public function shared($slug)
    {
        $wishlist = Wishlist::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        $items = WishlistItem::where('wishlists_id', $wishlist->wishlist_id)
            ->with(['variant.product.brand', 'variant.images', 'variant.prices'])
            ->orderByDesc('created_at')
            ->get();

        $owner = $wishlist->user;

        $canGift = $wishlist->delivery_address_id !== null;

        return view('wishlist.shared', compact('wishlist', 'items', 'owner', 'canGift'));
    }

    public function giftAddToCart(Request $request)
    {
        $request->validate([
            'variant_id'  => 'required|exists:product_variants,variant_id',
            'wishlist_id' => 'required|integer',
        ]);

        $wishlist = Wishlist::where('wishlist_id', $request->wishlist_id)
            ->where('is_public', true)
            ->whereNotNull('delivery_address_id')
            ->firstOrFail();

        $address = $wishlist->deliveryAddress;
        if (!$address) {
            return back()->withErrors(['gift' => 'The wishlist owner has not set a delivery address.']);
        }

        // ── Store gift address in session ──
        $giftData = session()->get('gift_orders', []);
        $giftData[(string) $request->variant_id] = [
            'wishlist_id'         => $wishlist->wishlist_id,
            'wishlist_owner_name' => $wishlist->user->first_name,
            'address_id'          => $address->address_id,
            'recipient_name'      => $address->recipient_name,
            'house_number'        => $address->house_number,
            'address_line_one'    => $address->address_line_one,
            'address_line_two'    => $address->address_line_two,
            'city'                => $address->city,
            'county'              => $address->county,
            'postcode'            => $address->postcode,
            'country_code'        => $address->country_code,
            'phone_number'        => $address->phone_number,
        ];
        session()->put('gift_orders', $giftData);

        // ── Add to cart directly (no synthetic Request) ──
        $variantId = (int) $request->variant_id;
        $variant = \App\Models\ProductVariant::with([
            'product',
            'inventory',
            'images' => fn($q) => $q->orderBy('sort_order'),
        ])->find($variantId);

        if (!$variant) {
            return back()->with('error', 'Product not found.');
        }

        // Stock check
        $stock = $variant->inventory ? $variant->inventory->in_stock : 0;
        if ($stock <= 0) {
            return back()->with('error', 'Sorry, this item is currently out of stock.');
        }

        // Get price
        $currency = session('currency', 'gbp');
        $priceRow = \App\Models\VariantCurrencyPrice::where('variant_id', $variantId)
            ->where('currency_code', $currency)
            ->first();
        $price = $priceRow?->price ?? 0;
        $symbol = \App\Models\Currency::where('currency_code', strtoupper($currency))->first()?->symbol ?? '£';

        // Read cart, check if item already exists
        $cart = session('cart', []);
        $foundIndex = null;
        foreach ($cart as $idx => $line) {
            if (($line['variant_id'] ?? null) == $variantId) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex !== null) {
            // Already in cart — increment by 1
            $newQty = $cart[$foundIndex]['quantity'] + 1;
            if ($newQty > $stock) {
                return back()->with('error', "Only {$stock} items available.");
            }
            $cart[$foundIndex]['quantity'] = $newQty;
        } else {
            // New item
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
                'quantity'   => 1,
                'currency'   => $currency,
                'symbol'     => $symbol,
            ];
        }

        session()->put('cart', $cart);
        session()->save();
        return redirect()->route('cart.index')
            ->with('success', 'Gift item added to basket! It will be delivered to ' . $wishlist->user->first_name . '.');
    }
    private function resolveWishlist(?int $wishlistId = null): Wishlist
    {
        $user = Auth::user();

        if ($wishlistId) {
            $wishlist = Wishlist::where('user_id', $user->user_id)
                ->where('wishlist_id', $wishlistId)
                ->first();

            if ($wishlist) return $wishlist;
        }

        $wishlist = Wishlist::where('user_id', $user->user_id)
            ->orderBy('created_at')
            ->first();

        if (!$wishlist) {
            $wishlist = Wishlist::create([
                'user_id'       => $user->user_id,
                'wishlist_name' => 'My Wishlist',
                'is_public'     => false,
                'slug'          => Wishlist::generateUniqueSlug('my-wishlist'),
            ]);
        }

        return $wishlist;
    }

    /**
     * AJAX: Return the user's wishlists for the picker modal.
     */
    public function picker(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Please sign in'], 401);
        }

        $variantId = $request->input('variant_id');

        $wishlists = Wishlist::where('user_id', Auth::user()->user_id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($wl) use ($variantId) {
                $containsItem = false;
                if ($variantId) {
                    $containsItem = WishlistItem::where('wishlists_id', $wl->wishlist_id)
                        ->where('variant_id', $variantId)
                        ->exists();
                }

                return [
                    'id'            => $wl->wishlist_id,
                    'name'          => $wl->wishlist_name,
                    'items_count'   => $wl->items_count,
                    'contains_item' => $containsItem,
                ];
            });

        return response()->json([
            'success'   => true,
            'wishlists' => $wishlists,
        ]);
    }

    /**
     * AJAX: Create a new wishlist and return it.
     */
    public function quickCreate(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Please sign in'], 401);
        }

        $data = $request->validate([
            'wishlist_name' => 'required|string|max:100',
        ]);

        $slug = Wishlist::generateUniqueSlug($data['wishlist_name']);

        $wishlist = Wishlist::create([
            'user_id'       => Auth::user()->user_id,
            'wishlist_name' => $data['wishlist_name'],
            'is_public'     => false,
            'slug'          => $slug,
        ]);

        return response()->json([
            'success'  => true,
            'wishlist' => [
                'id'          => $wishlist->wishlist_id,
                'name'        => $wishlist->wishlist_name,
                'items_count' => 0,
            ],
        ]);
    }
}