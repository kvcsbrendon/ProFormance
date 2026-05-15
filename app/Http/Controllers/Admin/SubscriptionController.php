<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\SubscribeSaveSettings;
use App\Models\SubscribeSaveItem;
use App\Models\User;
use App\Models\UserMessage;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // ─────────────────────────────────────────
    // SETTINGS PAGE
    // ─────────────────────────────────────────

    public function index()
    {
        $plan = SubscriptionPlan::first() ?? new SubscriptionPlan();
        $ssSettings = SubscribeSaveSettings::first() ?? new SubscribeSaveSettings();
        $activeSubscribers = UserSubscription::where('status', 'Active')
            ->where('expires_at', '>', now())
            ->count();
        $totalSsItems = SubscribeSaveItem::where('is_active', true)->count();

        return view('admin.subscriptions.index', compact('plan', 'ssSettings', 'activeSubscribers', 'totalSsItems'));
    }

    public function updatePlan(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|max:100',
            'monthly_price_penny'    => 'required|integer|min:0',
            'free_shipping'          => 'sometimes|in:1',
            'order_discount_percent' => 'required|integer|min:0|max:100',
            'is_active'              => 'sometimes|in:1',
        ]);

        $plan = SubscriptionPlan::first();
        if (!$plan) {
            $plan = new SubscriptionPlan();
        }

        $plan->name = $data['name'];
        $plan->monthly_price_penny = $data['monthly_price_penny'];
        $plan->free_shipping = $request->has('free_shipping');
        $plan->order_discount_percent = $data['order_discount_percent'];
        $plan->is_active = $request->has('is_active');
        $plan->save();

        return back()->with('success', 'Subscription plan updated.');
    }

    public function updateSsSettings(Request $request)
    {
        $data = $request->validate([
            'discount_percent' => 'required|integer|min:0|max:50',
            'is_active'        => 'sometimes|in:1',
        ]);

        $settings = SubscribeSaveSettings::first();
        if (!$settings) {
            $settings = new SubscribeSaveSettings();
        }

        $settings->discount_percent = $data['discount_percent'];
        $settings->is_active = $request->has('is_active');
        $settings->save();

        return back()->with('success', 'Subscribe & Save settings updated.');
    }

    // ─────────────────────────────────────────
    // SUBSCRIBERS LIST
    // ─────────────────────────────────────────

    public function subscribers(Request $request)
    {
        $query = UserSubscription::with(['user.loginDetail', 'plan'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%");
            })->orWhereHas('user.loginDetail', function ($q) use ($search) {
                $q->where('email_address', 'LIKE', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(20);

        $stats = [
            'active'    => UserSubscription::where('status', 'Active')->where('expires_at', '>', now())->count(),
            'cancelled' => UserSubscription::where('status', 'Cancelled')->count(),
            'expired'   => UserSubscription::where('status', 'Active')->where('expires_at', '<=', now())->count()
                         + UserSubscription::where('status', 'Expired')->count(),
        ];

        return view('admin.subscriptions.subscribers', compact('subscriptions', 'stats'));
    }

    /**
     * View a single subscriber's details.
     */
    public function showSubscriber($subscriptionId)
    {
        $subscription = UserSubscription::with(['user.loginDetail', 'plan'])->findOrFail($subscriptionId);
        $customer = $subscription->user;

        // Get customer's order history
        $orderCount = \App\Models\Order::where('user_id', $customer->user_id)->count();
        $totalSpent = \App\Models\Order::where('user_id', $customer->user_id)->sum('total_penny');

        // Get their S&S items
        $ssItems = SubscribeSaveItem::where('user_id', $customer->user_id)
            ->with('variant.product')
            ->orderByDesc('is_active')
            ->get();

        // Subscription history
        $history = UserSubscription::where('user_id', $customer->user_id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.subscriptions.show-subscriber', compact(
            'subscription', 'customer', 'orderCount', 'totalSpent', 'ssItems', 'history'
        ));
    }

    /**
     * Admin cancels a user's subscription.
     */
    public function cancelSubscription(Request $request, $subscriptionId)
    {
        $subscription = UserSubscription::with(['user', 'plan'])->findOrFail($subscriptionId);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $subscription->update([
            'status'       => 'Cancelled',
            'cancelled_at' => now(),
            'admin_note'   => $data['admin_note'] ?? null,
        ]);

        // Notify customer
        if ($subscription->user) {
            $note = !empty($data['admin_note']) ? "\n\nNote: " . $data['admin_note'] : '';
            UserMessage::send(
                $subscription->user->user_id,
                'order',
                'Subscription Cancelled',
                "Your {$subscription->plan->name} subscription has been cancelled by our team. Your benefits will remain active until {$subscription->expires_at->format('d M Y')}.{$note}\n\nIf you have any questions, please contact support.",
                route('account.subscription'),
                'View Subscription'
            );
        }

        return back()->with('success', 'Subscription cancelled for ' . ($subscription->user->first_name ?? 'user') . '.');
    }

    // ─────────────────────────────────────────
    // SUBSCRIBE & SAVE MANAGEMENT
    // ─────────────────────────────────────────

    /**
     * List all S&S items across all users.
     */
    public function ssItems(Request $request)
    {
        $query = SubscribeSaveItem::with(['user.loginDetail', 'variant.product'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)->whereNull('suspended_at');
            } elseif ($request->status === 'suspended') {
                $query->where('is_active', true)->whereNotNull('suspended_at');
            } elseif ($request->status === 'cancelled') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('first_name', 'LIKE', "%{$search}%")
                       ->orWhere('last_name', 'LIKE', "%{$search}%");
                })->orWhereHas('variant.product', function ($pq) use ($search) {
                    $pq->where('product_name', 'LIKE', "%{$search}%");
                });
            });
        }

        $items = $query->paginate(20);

        $stats = [
            'active'    => SubscribeSaveItem::where('is_active', true)->whereNull('suspended_at')->count(),
            'suspended' => SubscribeSaveItem::where('is_active', true)->whereNotNull('suspended_at')->count(),
            'cancelled' => SubscribeSaveItem::where('is_active', false)->count(),
        ];

        return view('admin.subscriptions.ss-items', compact('items', 'stats'));
    }

    /**
     * Suspend an S&S item (pauses delivery but doesn't cancel).
     */
    public function suspendSsItem(Request $request, $ssItemId)
    {
        $item = SubscribeSaveItem::with(['user', 'variant.product'])->findOrFail($ssItemId);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $item->update([
            'suspended_at' => now(),
            'admin_note'   => $data['admin_note'] ?? null,
        ]);

        // Notify customer
        if ($item->user) {
            $productName = $item->variant->product->product_name ?? 'your item';
            $note = !empty($data['admin_note']) ? "\n\nNote: " . $data['admin_note'] : '';
            UserMessage::send(
                $item->user->user_id,
                'order',
                'Subscribe & Save Paused',
                "Your Subscribe & Save for \"{$productName}\" has been paused by our team.{$note}\n\nYou can resume it from your account or contact support for help.",
                route('account.subscription'),
                'Manage Subscriptions'
            );
        }

        return back()->with('success', 'S&S item suspended.');
    }

    /**
     * Resume a suspended S&S item.
     */
    public function resumeSsItem($ssItemId)
    {
        $item = SubscribeSaveItem::with(['user', 'variant.product'])->findOrFail($ssItemId);

        $item->update([
            'suspended_at' => null,
            'admin_note'   => null,
        ]);

        if ($item->user) {
            $productName = $item->variant->product->product_name ?? 'your item';
            UserMessage::send(
                $item->user->user_id,
                'order',
                'Subscribe & Save Resumed',
                "Your Subscribe & Save for \"{$productName}\" has been resumed. Your next delivery is scheduled for " . ($item->next_delivery_at ? $item->next_delivery_at->format('d M Y') : 'soon') . ".",
                route('account.subscription'),
                'Manage Subscriptions'
            );
        }

        return back()->with('success', 'S&S item resumed.');
    }

    /**
     * Admin cancels an S&S item.
     */
    public function cancelSsItem(Request $request, $ssItemId)
    {
        $item = SubscribeSaveItem::with(['user', 'variant.product'])->findOrFail($ssItemId);

        $data = $request->validate([
            'admin_note' => 'nullable|string|max:500',
        ]);

        $item->update([
            'is_active'  => false,
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        if ($item->user) {
            $productName = $item->variant->product->product_name ?? 'your item';
            $note = !empty($data['admin_note']) ? "\n\nReason: " . $data['admin_note'] : '';
            UserMessage::send(
                $item->user->user_id,
                'order',
                'Subscribe & Save Cancelled',
                "Your Subscribe & Save for \"{$productName}\" has been cancelled.{$note}\n\nYou can set it up again from the product page or during checkout.",
                route('account.subscription'),
                'Manage Subscriptions'
            );
        }

        return back()->with('success', 'S&S item cancelled.');
    }
}
