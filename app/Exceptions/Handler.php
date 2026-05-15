<?php
use Illuminate\Database\Eloquent\ModelNotFoundException;

$this->renderable(function (ModelNotFoundException $e, $request) {
    $model = $e->getModel();

    return match ($model) {
        \App\Models\Product::class  => response()->view('errors.product-not-found', [], 404),
        \App\Models\Wishlist::class => response()->view('errors.wishlist-not-found', [], 404),
        default                     => response()->view('errors.404', [], 404),
    };
});