<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::orderByDesc('subscribed_at');
        if ($request->filled('status')) { $query->where('status', $request->status); }
        if ($request->filled('search')) { $query->where('email_address', 'like', "%{$request->search}%"); }
        $subscribers = $query->paginate(30)->withQueryString();
        $totalSubscribed = NewsletterSubscriber::where('status', 'subscribed')->count();
        $totalUnsubscribed = NewsletterSubscriber::where('status', 'unsubscribed')->count();
        return view('admin.newsletters.index', compact('subscribers', 'totalSubscribed', 'totalUnsubscribed'));
    }
}
