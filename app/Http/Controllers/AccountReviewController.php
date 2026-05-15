<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use Illuminate\Http\Request;

use App\Models\ReviewImage;
use Illuminate\Support\Facades\File;
class AccountReviewController extends Controller
{
    /**
     * Display a listing of the user's reviews
     */
    public function index()
    {
        $user = Auth::user();
        
        $reviews = Review::with(['product', 'images', 'helpfulVotes'])
            ->where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('account.reviews', compact('reviews'));
    }

    /**
     * Show the form for editing a review
     */
    public function edit($review_id)
    {
        $user = Auth::user();
        
        $review = Review::where('review_id', $review_id)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
            
        return view('account.reviews-edit', compact('review'));
    }

    /**
     * Update the specified review
     */
    public function update(Request $request, $review_id)
    {
        $user = Auth::user();
        
        $review = Review::where('review_id', $review_id)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'title'    => 'nullable|string|max:200',
            'body'     => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048' // Validate new images
        ]);

        $review->update([
            'rating'      => $request->rating,
            'title'       => $request->title,
            'body'        => $request->body,
            'is_approved' => false // Needs re-approval after edit
        ]);

        if ($request->has('delete_images')) {
            $imagesToDelete = ReviewImage::whereIn('image_id', $request->delete_images)
                                         ->where('review_id', $review->review_id)
                                         ->get();
            
            foreach($imagesToDelete as $img) {
                $path = public_path('images/' . $img->image_path);
                if (File::exists($path)) {
                    File::delete($path);
                }
                $img->delete();
            }
        }

        // Handle New Image Uploads
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

        return redirect()->route('account.reviews')
            ->with('success', 'Review updated successfully. It will be visible after approval.');
    }

    /**
     * Remove the specified review
     */
    public function destroy($review_id)
    {
        $user = Auth::user();
        
        $review = Review::where('review_id', $review_id)
            ->where('user_id', $user->user_id)
            ->firstOrFail();
            
        $review->delete();
        
        return redirect()->route('account.reviews')
            ->with('success', 'Review deleted successfully.');
    }
}