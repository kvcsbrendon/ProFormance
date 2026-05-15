<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\ReviewHelpfulVote;
use App\Models\ReviewReport;

class ReviewController extends Controller
{
    public function store(Request $request, $productId)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to leave a review.');
        }

        $user = Auth::user();

        if (Review::where('user_id', $user->user_id)->where('product_id', $productId)->exists()) {
            return back()->withErrors(['review' => 'You have already reviewed this product.']);
        }

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'title'    => 'nullable|string|max:200',
            'body'     => 'nullable|string|max:2000',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' // Max 2MB per image
        ]);

        // Check Verified Purchase
        $isVerified = DB::table('orders')
            ->join('order_items', 'orders.order_id', '=', 'order_items.order_id')
            ->join('product_variants', 'order_items.variant_id', '=', 'product_variants.variant_id')
            ->where('orders.user_id', $user->user_id)
            ->where('product_variants.product_id', $productId)
            ->exists();

        $review = Review::create([
            'user_id'              => $user->user_id,
            'product_id'           => $productId,
            'rating'               => $data['rating'],
            'title'                => $data['title'],
            'body'                 => $data['body'],
            'is_approved'          => false,
            'is_verified_purchase' => $isVerified,
        ]);

        // Handle Image Uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/reviews'), $filename);
                
                ReviewImage::create([
                    'review_id'  => $review->review_id,
                    'image_path' => 'reviews/' . $filename
                ]);
            }
        }

        return back()->with('success', 'Thank you for your review! It will be visible once approved.');
    }

    public function markHelpful(Request $request, $reviewId)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Please sign in.'], 401);
        }

        $userId = Auth::user()->user_id;
        $exists = ReviewHelpfulVote::where('review_id', $reviewId)->where('user_id', $userId)->exists();

        if ($exists) {
            ReviewHelpfulVote::where('review_id', $reviewId)->where('user_id', $userId)->delete();
            $action = 'removed';
        } else {
            ReviewHelpfulVote::create(['review_id' => $reviewId, 'user_id' => $userId]);
            $action = 'added';
        }

        $count = ReviewHelpfulVote::where('review_id', $reviewId)->count();

        return response()->json(['success' => true, 'action' => $action, 'count' => $count]);
    }

    public function report(Request $request, $reviewId)
    {
        if (!Auth::check()) return back()->with('error', 'Please sign in to report.');

        $request->validate(['reason' => 'required|string|max:100']);

        ReviewReport::firstOrCreate([
            'review_id' => $reviewId,
            'user_id'   => Auth::user()->user_id,
        ], [
            'reason' => $request->reason,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Review has been reported to moderators.');
    }
}