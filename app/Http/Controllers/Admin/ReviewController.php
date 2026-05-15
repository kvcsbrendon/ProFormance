<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        // Eager load relations to prevent N+1 queries
        $query = Review::with(['user', 'product', 'images', 'helpfulVotes', 'reports'])
            ->orderByDesc('created_at');

        // Match the "filter" dropdown from the Blade file
        if ($request->filter === 'reported') {
            $query->whereHas('reports', function($q) {
                $q->where('status', 'pending');
            });
        } elseif ($request->filter === 'pending') {
            $query->where('is_approved', false);
        } elseif ($request->filter === 'approved') {
            $query->where('is_approved', true);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('body', 'like', "%{$s}%")
                  ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$s}%"));
            });
        }
        
        $reviews = $query->paginate(20)->withQueryString();
        return view('admin.reviews.index', compact('reviews'));
    }

    public function approve($reviewId)
    {
        Review::where('review_id', $reviewId)->firstOrFail()->update(['is_approved' => true]);
        return back()->with('success', 'Review approved.');
    }

    public function reject($reviewId)
    {
        Review::where('review_id', $reviewId)->firstOrFail()->update(['is_approved' => false]);
        return back()->with('success', 'Review unpublished.');
    }

    public function destroy($reviewId)
    {
        Review::where('review_id', $reviewId)->firstOrFail()->delete();
        return back()->with('success', 'Review deleted.');
    }

    // ── Simple Admin Reply (Using our new column) ──
    public function reply(Request $request, $reviewId)
    {
        $request->validate([
            'admin_reply' => 'nullable|string|max:2000',
        ]);

        $review = Review::findOrFail($reviewId);
        $review->update(['admin_reply' => $request->admin_reply]);

        return back()->with('success', 'Admin reply saved successfully.');
    }

    // ── Report Management ──
    public function markReportReviewed($id)
    {
        $report = ReviewReport::findOrFail($id);
        $report->update(['status' => 'resolved']); // Maps to the Enum we created in SQL
        
        return back()->with('success', 'Report marked as resolved.');
    }

    public function dismissReport($id)
    {
        $report = ReviewReport::findOrFail($id);
        $report->update(['status' => 'dismissed']); // Maps to the Enum we created in SQL
        
        return back()->with('success', 'Report dismissed.');
    }
}