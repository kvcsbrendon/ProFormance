<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\UserSubscription;
use App\Models\SubscriptionPayment;
use App\Models\SavedCard;
use App\Models\UserMessage;

class RenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:renew';
    protected $description = 'Auto-renew active subscriptions that have expired';

    public function handle()
    {
        // Find active subscriptions where expires_at is today or in the past
        $due = UserSubscription::where('status', 'Active')
            ->where('expires_at', '<=', now())
            ->with(['user', 'plan'])
            ->get();

        $renewed = 0;
        $failed  = 0;

        foreach ($due as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            if (!$user || !$plan || !$plan->is_active) {
                $subscription->update(['status' => 'Expired']);
                $failed++;
                continue;
            }

            // Find user's default card
            $card = SavedCard::where('user_id', $user->user_id)
                ->where('is_default', true)
                ->where('expiry_year', '>=', now()->year)
                ->first();

            if (!$card) {
                // No valid card — expire the subscription
                $subscription->update(['status' => 'Expired']);

                UserMessage::send(
                    $user->user_id,
                    'order',
                    'Subscription Payment Failed',
                    "We couldn't renew your {$plan->name} subscription because no valid payment card was found. "
                        . "Please add a card and resubscribe to continue enjoying your benefits.",
                    route('account.subscription'),
                    'Manage Subscription'
                );

                $failed++;
                continue;
            }

            // Simulate payment
            $newExpiry = now()->addMonth();

            SubscriptionPayment::create([
                'subscription_id' => $subscription->subscription_id,
                'user_id'         => $user->user_id,
                'plan_id'         => $plan->plan_id,
                'amount_penny'    => $plan->monthly_price_penny,
                'currency_code'   => 'GBP',
                'payment_method'  => 'card',
                'card_id'         => $card->card_id,
                'provider_ref'    => 'SUB-' . strtoupper(Str::random(12)),
                'status'          => 'Paid',
                'period_start'    => $subscription->expires_at->toDateString(),
                'period_end'      => $newExpiry->toDateString(),
            ]);

            $subscription->update([
                'expires_at' => $newExpiry,
            ]);

            $amount = '£' . number_format($plan->monthly_price_penny / 100, 2);
            $cardLabel = ucfirst($card->card_brand) . ' ending ' . $card->last_four;

            UserMessage::send(
                $user->user_id,
                'order',
                'Subscription Renewed',
                "Your {$plan->name} subscription has been renewed. "
                    . "{$amount} was charged to your {$cardLabel}. "
                    . "Next renewal: {$newExpiry->format('d M Y')}.",
                route('account.subscription'),
                'View Subscription'
            );

            $renewed++;
        }

        $this->info("Renewals: {$renewed} succeeded, {$failed} failed/expired.");
    }
}
