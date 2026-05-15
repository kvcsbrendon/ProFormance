<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscribeSaveItem;
use App\Models\SubscribeSaveSettings;
use App\Models\SavedCard;
use App\Models\UserMessage;

class SubscriptionController extends Controller
{
    /**
     * Subscription management page.
     */
    public function index()
    {
        $user = Auth::user();
        $plan = SubscriptionPlan::activePlan();
        $subscription = UserSubscription::activeFor($user->user_id);
        $ssItems = SubscribeSaveItem::where('user_id', $user->user_id)
            ->where('is_active', true)
            ->with('variant.product')
            ->orderByDesc('created_at')
            ->get();
        $ssDiscount = SubscribeSaveSettings::discountPercent();

        // Saved cards for payment
        $savedCards = SavedCard::where('user_id', $user->user_id)
            ->where('expiry_year', '>=', now()->year)
            ->orderByDesc('is_default')
            ->get();

        // Recent subscription payments
        $recentPayments = SubscriptionPayment::where('user_id', $user->user_id)
            ->with(['plan', 'savedCard'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('account.subscription', compact(
            'plan', 'subscription', 'ssItems', 'ssDiscount',
            'savedCards', 'recentPayments'
        ));
    }

    /**
     * Subscribe to the plan.
     * Requires payment — either a saved card ID or new card details.
     */
    public function subscribe(Request $request)
    {
        $user = Auth::user();
        $plan = SubscriptionPlan::activePlan();

        if (!$plan) {
            return $this->respond($request, false, 'No subscription plan is currently available.', 422);
        }

        $existing = UserSubscription::activeFor($user->user_id);
        if ($existing) {
            return $this->respond($request, false, 'You already have an active subscription.', 422);
        }

        // ── Resolve payment method ──
        $savedCard = null;
        $paymentMethod = 'card';

        if ($request->filled('saved_card_id')) {
            // Using a saved card
            $savedCard = SavedCard::where('card_id', $request->saved_card_id)
                ->where('user_id', $user->user_id)
                ->first();

            if (!$savedCard) {
                return $this->respond($request, false, 'Saved card not found.', 422);
            }
        } else {
            // Try default saved card
            $savedCard = SavedCard::where('user_id', $user->user_id)
                ->where('is_default', true)
                ->where('expiry_year', '>=', now()->year)
                ->first();

            if (!$savedCard) {
                return $this->respond(
                    $request, false,
                    'Please add a payment card to your account before subscribing.',
                    422
                );
            }
        }

        // ── Create subscription ──
        $subscription = UserSubscription::create([
            'user_id'    => $user->user_id,
            'plan_id'    => $plan->plan_id,
            'status'     => 'Active',
            'started_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        // ── Record the first payment ──
        $symbol = $this->currencySymbol();
        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->subscription_id,
            'user_id'         => $user->user_id,
            'plan_id'         => $plan->plan_id,
            'amount_penny'    => $plan->monthly_price_penny,
            'currency_code'   => strtoupper(session('currency', 'GBP')),
            'payment_method'  => $paymentMethod,
            'card_id'         => $savedCard->card_id,
            'provider_ref'    => 'SUB-' . strtoupper(Str::random(12)),
            'status'          => 'Paid',
            'period_start'    => now()->toDateString(),
            'period_end'      => now()->addMonth()->toDateString(),
        ]);

        // ── Notify user ──
        $formattedAmount = $symbol . number_format($plan->monthly_price_penny / 100, 2);
        $cardLabel = ucfirst($savedCard->card_brand) . ' ending ' . $savedCard->last_four;

        UserMessage::send(
            $user->user_id,
            'order',
            'Welcome to ' . $plan->name . '!',
            "Your {$plan->name} subscription is now active.\n\n"
                . "Payment of {$formattedAmount} charged to {$cardLabel}.\n"
                . "You'll enjoy free shipping and {$plan->order_discount_percent}% off every order.\n"
                . "Your subscription renews on " . $subscription->expires_at->format('d M Y') . ".",
            route('account.subscription'),
            'Manage Subscription'
        );

        return $this->respond($request, true, "Welcome to {$plan->name}! Your subscription is now active.");
    }

    /**
     * Cancel subscription.
     */
    public function cancel()
    {
        $user = Auth::user();
        $subscription = UserSubscription::activeFor($user->user_id);

        if (!$subscription) {
            return back()->withErrors(['subscription' => 'No active subscription found.']);
        }

        $subscription->update([
            'status'       => 'Cancelled',
            'cancelled_at' => now(),
        ]);

        UserMessage::send(
            $user->user_id,
            'order',
            'Subscription Cancelled',
            "Your subscription has been cancelled. You'll continue to have access until " . $subscription->expires_at->format('d M Y') . ".",
            route('account.subscription'),
            'View Subscription'
        );

        return back()->with('success', 'Your subscription has been cancelled. Benefits remain active until ' . $subscription->expires_at->format('d M Y') . '.');
    }

    /**
     * Add/update a Subscribe & Save item.
     */
    public function saveSsItem(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'variant_id'      => 'required|integer|exists:product_variants,variant_id',
            'quantity'         => 'required|integer|min:1|max:99',
            'frequency_weeks' => 'required|integer|in:1,2,4,8,12',
        ]);

        $ssItem = SubscribeSaveItem::updateOrCreate(
            [
                'user_id'    => $user->user_id,
                'variant_id' => $data['variant_id'],
            ],
            [
                'quantity'         => $data['quantity'],
                'frequency_weeks'  => $data['frequency_weeks'],
                'next_delivery_at' => now()->addWeeks($data['frequency_weeks']),
                'is_active'        => true,
                'suspended_at'     => null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'item' => $ssItem]);
        }

        return back()->with('success', 'Subscribe & Save updated.');
    }

    /**
     * Update an existing S&S item (quantity/frequency).
     */
    public function updateSsItem(Request $request, $ssItemId)
    {
        $user = Auth::user();

        $item = SubscribeSaveItem::where('ss_item_id', $ssItemId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $data = $request->validate([
            'quantity'         => 'required|integer|min:1|max:99',
            'frequency_weeks' => 'required|integer|in:1,2,4,8,12',
        ]);

        $item->update($data);

        return back()->with('success', 'Subscription updated.');
    }

    /**
     * Cancel a Subscribe & Save item.
     */
    public function cancelSsItem($ssItemId)
    {
        $user = Auth::user();

        $item = SubscribeSaveItem::where('ss_item_id', $ssItemId)
            ->where('user_id', $user->user_id)
            ->firstOrFail();

        $item->update(['is_active' => false]);

        return back()->with('success', 'Subscribe & Save cancelled for this item.');
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function respond(Request $request, bool $ok, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : $status);
        }

        if ($ok) {
            return back()->with('success', $message);
        }

        return back()->withErrors(['subscription' => $message]);
    }

    private function currencySymbol(): string
    {
        $symbols = ['gbp' => '£', 'usd' => '$', 'eur' => '€'];
        return $symbols[strtolower(session('currency', 'gbp'))] ?? '£';
    }
}