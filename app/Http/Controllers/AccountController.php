<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Address;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\NewsletterSubscriber;
use App\Models\LoginDetail;

class AccountController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        $recentOrders = Order::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $totalOrders   = Order::where('user_id', $user->user_id)->count();
        $addressCount  = Address::where('user_id', $user->user_id)->count();
        $reviewCount   = Review::where('user_id', $user->user_id)->count();

        $wishlistCount = 0;
        $wishlists = Wishlist::where('user_id', $user->user_id)->with('items')->get();
        foreach ($wishlists as $wl) {
            $wishlistCount += $wl->items->count();
        }

        return view('account.dashboard', compact(
            'user', 'recentOrders', 'totalOrders',
            'wishlistCount', 'reviewCount', 'addressCount'
        ));
    }

    public function profile()
    {
        $user = Auth::user();
        $login = LoginDetail::where('user_id', $user->user_id)->first();
        $email = strtolower(trim($login?->email_address ?? ''));

        $newsletterSubscribed = false;
        if ($email !== '') {
            $sub = NewsletterSubscriber::where('email_address', $email)->first();
            $newsletterSubscribed = $sub && $sub->status === NewsletterSubscriber::STATUS_SUBSCRIBED;
        }

        return view('account.profile', compact('user', 'newsletterSubscribed'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'company_name'       => 'nullable|string|max:255',
            'country_phone_code' => 'required|integer',
            'phone_number'       => 'required|string|max:20',
            'subscribed'         => 'required|boolean',
        ]);

        $subscribed = $request->boolean('subscribed');

        unset($data['subscribed']);

        $user->update($data);


        $login = LoginDetail::where('user_id', $user->user_id)->first();
        $email = strtolower(trim($login?->email_address ?? ''));

        if ($email !== '') {
            $subscriber = NewsletterSubscriber::firstOrNew(['email_address' => $email]);
            $subscriber->user_id = $subscriber->user_id ?: $user->user_id;

            if ($subscribed) {
                $subscriber->status = NewsletterSubscriber::STATUS_SUBSCRIBED;
                $subscriber->subscribed_at = $subscriber->subscribed_at ?: now();
                $subscriber->unsubscribed_at = null;
            } else {
                $subscriber->status = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
                $subscriber->subscribed_at = $subscriber->subscribed_at ?: now();
                $subscriber->unsubscribed_at = now();
            }

            $subscriber->save();
        }


        return redirect()->route('account.profile')
            ->with('success', 'Profile updated successfully.');
    }

}
