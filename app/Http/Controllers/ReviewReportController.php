<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use App\Models\ReviewReport;

class ReviewReportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'review_id' => 'required|exists:reviews,review_id',
            'reason' => 'required|in:inappropriate,fake,offensive,spam,other',
            'details' => 'nullable|string|max:1000',
            'reporter_email' => 'nullable|email|max:255'
        ]);

        $review = Review::findOrFail($request->review_id);

        // Check if already reported by this user (if logged in)
        if (Auth::check()) {
            $user = Auth::user();
            $existing = ReviewReport::where('review_id', $review->review_id)
                ->where('reporter_user_id', $user->user_id)
                ->whereIn('status', ['pending', 'reviewed'])
                ->first();
                
            if ($existing) {
                return back()->withErrors(['report' => 'You have already reported this review.']);
            }
        }

        $report = ReviewReport::create([
            'review_id' => $review->review_id,
            'reporter_user_id' => Auth::check() ? Auth::user()->user_id : null,
            'reporter_email' => $request->reporter_email,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'pending',
            'created_at' => now()
        ]);

        // Increment report count on review
        $review->increment('report_count');

        return back()->with('success', 'Thank you for reporting this review. Our team will review it shortly.');
    }
}