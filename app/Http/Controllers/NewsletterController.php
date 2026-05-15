<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\NewsletterSubscriber;
use App\Models\LoginDetail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email_address' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email_address']));

        $userId = null;

        if (Auth::check()) {
            $userEmail = Auth::user()->loginDetail?->email_address;
            if ($userEmail && strtolower(trim($userEmail)) === $email) {
                $userId = Auth::user()->user_id;
            }
        }

        $subscriber = NewsletterSubscriber::where('email_address', $email)->first();

        if (!$subscriber) {
            NewsletterSubscriber::create([
                'user_id'         => $userId,
                'email_address'   => $email,
                'status'          => NewsletterSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at'   => now(),
                'unsubscribed_at' => null,
            ]);
        } else {
            $updates = [
                'status'          => NewsletterSubscriber::STATUS_SUBSCRIBED,
                'unsubscribed_at' => null,
                'subscribed_at'   => $subscriber->subscribed_at ?: now(),
            ];

            // Only link user_id if it matches AND the subscriber doesn't already have one
            if ($userId && !$subscriber->user_id) {
                $updates['user_id'] = $userId;
            }

            $subscriber->update($updates);
        }

        return back()->with('success', 'Thanks! You\'re subscribed.');
    }

    public function updatePreference(Request $request)
    {
        $data = $request->validate([
            'subscribed' => ['required', 'boolean'],
        ]);

        $user = Auth::user();

        $login = LoginDetail::where('user_id', $user->user_id)->first();
        $email = strtolower(trim($login?->email_address ?? ''));

        if ($email === '') {
            return back()->with('error', 'Could not find your email address.');
        }

        $subscriber = NewsletterSubscriber::firstOrNew(['email_address' => $email]);
        $subscriber->user_id = $subscriber->user_id ?: $user->user_id;

        if ((bool)$data['subscribed']) {
            $subscriber->status          = NewsletterSubscriber::STATUS_SUBSCRIBED;
            $subscriber->subscribed_at   = $subscriber->subscribed_at ?: now();
            $subscriber->unsubscribed_at = null;
        } else {
            $subscriber->status          = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
            $subscriber->subscribed_at   = $subscriber->subscribed_at ?: now();
            $subscriber->unsubscribed_at = now();
        }

        $subscriber->save();

        return back()->with('success', 'Newsletter preference updated.');
    }
}
