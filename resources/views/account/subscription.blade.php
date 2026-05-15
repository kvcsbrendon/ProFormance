{{-- resources/views/account/subscription.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">Subscription</h1>
    <p class="kb-account-subtitle">Manage your membership and Subscribe &amp; Save items.</p>
</div>

{{-- MEMBERSHIP SECTION --}}
<div class="kb-account-card">
    @if($subscription && $subscription->isActive())
        {{-- Active subscriber --}}
        <div class="kb-sub-active-banner">
            <div class="kb-sub-active-icon"><i class="bi bi-star-fill"></i></div>
            <div>
                <h2 class="kb-sub-active-title">{{ $plan->name ?? 'ProFormance Plus' }}</h2>
                <p class="kb-sub-active-status">Active since {{ $subscription->started_at->format('d M Y') }}</p>
            </div>
        </div>

        <div class="kb-sub-benefits">
            <h3 class="kb-sub-section-title">Your Benefits</h3>
            <div class="kb-sub-benefit-grid">
                @if($plan->free_shipping)
                    <div class="kb-sub-benefit-card">
                        <i class="bi bi-truck"></i>
                        <span>Free Shipping</span>
                        <small>Standard delivery</small>
                    </div>
                @endif
                @if($plan->order_discount_percent > 0)
                    <div class="kb-sub-benefit-card">
                        <i class="bi bi-piggy-bank"></i>
                        <span>{{ $plan->order_discount_percent }}% Off</span>
                        <small>Every order</small>
                    </div>
                @endif
            </div>
        </div>

        <div class="kb-sub-details">
            <div class="kb-sub-detail-row">
                <span>Monthly cost</span>
                <strong>&pound;{{ number_format($plan->monthly_price_penny / 100, 2) }}/mo</strong>
            </div>
            <div class="kb-sub-detail-row">
                <span>Next renewal</span>
                <strong>{{ $subscription->expires_at->format('d M Y') }}</strong>
            </div>
            <div class="kb-sub-detail-row">
                <span>Payment method</span>
                <strong>
                    @php
                        $lastPayment = isset($recentPayments) ? $recentPayments->first() : null;
                    @endphp
                    @if($lastPayment && $lastPayment->savedCard)
                        {{ ucfirst($lastPayment->savedCard->card_brand) }} •••• {{ $lastPayment->savedCard->last_four }}
                    @else
                        Card on file
                    @endif
                </strong>
            </div>
        </div>

        <form method="POST" action="{{ route('account.subscription.cancel') }}"
              onsubmit="return confirm('Are you sure you want to cancel your subscription? Your benefits will remain active until {{ $subscription->expires_at->format('d M Y') }}.')">
            @csrf
            <button type="submit" class="kb-account-btn kb-account-btn-outline" style="color: #dc2626; border-color: #dc2626;">
                <i class="bi bi-x-circle"></i> Cancel Subscription
            </button>
        </form>

    @elseif($plan && $plan->is_active)
        {{-- Not subscribed — show plan with card selector --}}
        <div class="kb-sub-promo">
            <div class="kb-sub-promo-header">
                <h2 class="kb-sub-promo-title">{{ $plan->name }}</h2>
                <div class="kb-sub-promo-price">
                    <span class="kb-sub-price-amount">&pound;{{ number_format($plan->monthly_price_penny / 100, 2) }}</span>
                    <span class="kb-sub-price-period">/month</span>
                </div>
            </div>

            <p class="kb-sub-promo-desc">Unlock exclusive benefits on every order you place.</p>

            <div class="kb-sub-benefit-grid">
                @if($plan->free_shipping)
                    <div class="kb-sub-benefit-card">
                        <i class="bi bi-truck"></i>
                        <span>Free Shipping</span>
                        <small>Standard delivery</small>
                    </div>
                @endif
                @if($plan->order_discount_percent > 0)
                    <div class="kb-sub-benefit-card">
                        <i class="bi bi-piggy-bank"></i>
                        <span>{{ $plan->order_discount_percent }}% Off</span>
                        <small>Every order</small>
                    </div>
                @endif
                <div class="kb-sub-benefit-card">
                    <i class="bi bi-lightning"></i>
                    <span>Priority</span>
                    <small>Order processing</small>
                </div>
            </div>

            <form method="POST" action="{{ route('account.subscription.subscribe') }}">
                @csrf

                {{-- Card selector --}}
                @if(isset($savedCards) && $savedCards->count() > 0)
                    <div class="kb-sub-card-selector">
                        <label class="kb-sub-section-title" style="margin-bottom: 8px; display: block;">Pay with</label>
                        @foreach($savedCards as $sc)
                            <label class="kb-sub-card-option">
                                <input type="radio" name="saved_card_id" value="{{ $sc->card_id }}"
                                       {{ $sc->is_default ? 'checked' : '' }}>
                                <div class="kb-sub-card-option-info">
                                    <i class="bi bi-credit-card"></i>
                                    {{ ucfirst($sc->card_brand) }} •••• {{ $sc->last_four }}
                                    <span style="color: var(--kb-secondary-font); font-size: 11px;">
                                        Exp {{ $sc->expiry_display }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="kb-account-btn kb-account-btn-primary kb-sub-subscribe-btn">
                        <i class="bi bi-star"></i> Subscribe for &pound;{{ number_format($plan->monthly_price_penny / 100, 2) }}/mo
                    </button>
                @else
                    <div class="kb-sub-no-card-notice">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>You need a saved card to subscribe.</span>
                        <a href="{{ route('account.cards') }}" class="kb-account-btn kb-account-btn-primary kb-account-btn-small" style="margin-left: 8px;">
                            <i class="bi bi-plus"></i> Add Card
                        </a>
                    </div>
                @endif
            </form>
        </div>
    @else
        <p class="kb-form-hint">No subscription plan is currently available.</p>
    @endif
</div>

{{-- PAYMENT HISTORY SECTION --}}
@if(isset($recentPayments) && $recentPayments->isNotEmpty())
    <div class="kb-account-card">
        <h2 class="kb-account-card-title"><i class="bi bi-receipt"></i> Payment History</h2>

        <div class="kb-sub-payments-list">
            @foreach($recentPayments as $payment)
                <div class="kb-sub-payment-row">
                    <div class="kb-sub-payment-date">
                        {{ $payment->created_at->format('d M Y') }}
                        <span>{{ $payment->created_at->format('H:i') }}</span>
                    </div>
                    <div class="kb-sub-payment-detail">
                        <strong>{{ $payment->plan->name ?? 'Subscription' }}</strong>
                        @if($payment->period_start && $payment->period_end)
                            <span>{{ $payment->period_start->format('d M') }} – {{ $payment->period_end->format('d M Y') }}</span>
                        @endif
                    </div>
                    <div class="kb-sub-payment-card">
                        @if($payment->savedCard)
                            <i class="bi bi-credit-card"></i>
                            {{ ucfirst($payment->savedCard->card_brand) }} •••• {{ $payment->savedCard->last_four }}
                        @else
                            {{ ucfirst($payment->payment_method) }}
                        @endif
                    </div>
                    <div class="kb-sub-payment-status">
                        @php
                            $statusClass = match($payment->status) {
                                'Paid' => 'kb-badge-success',
                                'Refunded' => 'kb-badge-warning',
                                'Failed' => 'kb-badge-danger',
                                default => 'kb-badge-secondary',
                            };
                        @endphp
                        <span class="kb-badge {{ $statusClass }}">{{ $payment->status }}</span>
                    </div>
                    <div class="kb-sub-payment-amount">
                        &pound;{{ number_format($payment->amount_penny / 100, 2) }}
                    </div>
                </div>
            @endforeach
        </div>

        @if($recentPayments->count() >= 5)
            <a href="{{ route('account.payments') }}" style="display: inline-block; margin-top: 10px; font-size: 13px; color: var(--kb-accent);">
                View all payments →
            </a>
        @endif
    </div>
@endif

{{-- SUBSCRIBE & SAVE SECTION --}}
<div class="kb-account-card">
    <h2 class="kb-account-card-title"><i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save</h2>

    @if($ssDiscount > 0)
        <p class="kb-sub-ss-desc">
            Items set up for Subscribe &amp; Save get <strong>{{ $ssDiscount }}% off</strong> automatically at checkout.
            Adjust quantities and delivery frequency below.
        </p>
    @endif

    @if($ssItems->isEmpty())
        <div class="kb-account-empty" style="padding: 30px 0;">
            <i class="bi bi-arrow-repeat" style="font-size: 28px; color: var(--kb-secondary-font);"></i>
            <p>You don't have any Subscribe &amp; Save items yet.</p>
            <p class="kb-form-hint">You can set up Subscribe &amp; Save on any item during checkout.</p>
        </div>
    @else
        <div class="kb-ss-items-list">
            @foreach($ssItems as $item)
                <div class="kb-ss-item-card {{ $item->suspended_at ? 'kb-ss-item-suspended' : '' }}">
                    <div class="kb-ss-item-info">
                        <h4>
                            {{ $item->variant->product->product_name ?? 'Product' }}
                            @if($item->suspended_at)
                                <span class="kb-ss-suspended-tag"><i class="bi bi-pause-circle"></i> Paused</span>
                            @endif
                        </h4>
                        <p class="kb-ss-item-meta">
                            @if($ssDiscount > 0 && !$item->suspended_at)
                                <span class="kb-ss-discount-tag">{{ $ssDiscount }}% off</span>
                            @endif
                            @if($item->suspended_at)
                                Paused by our team — contact support for details
                            @else
                                Next delivery: {{ $item->next_delivery_at ? $item->next_delivery_at->format('d M Y') : 'TBD' }}
                            @endif
                        </p>
                    </div>

                    @if(!$item->suspended_at)
                        <form method="POST" action="{{ route('account.subscription.ss.update', $item->ss_item_id) }}" class="kb-ss-item-form">
                            @csrf
                            @method('PATCH')
                            <div class="kb-ss-item-controls">
                                <div class="kb-ss-control">
                                    <label>Qty</label>
                                    <select name="quantity" class="kb-form-input kb-ss-select">
                                        @for($i = 1; $i <= 20; $i++)
                                            <option value="{{ $i }}" {{ $item->quantity == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="kb-ss-control">
                                    <label>Every</label>
                                    <select name="frequency_weeks" class="kb-form-input kb-ss-select">
                                        <option value="1" {{ $item->frequency_weeks == 1 ? 'selected' : '' }}>1 week</option>
                                        <option value="2" {{ $item->frequency_weeks == 2 ? 'selected' : '' }}>2 weeks</option>
                                        <option value="4" {{ $item->frequency_weeks == 4 ? 'selected' : '' }}>1 month</option>
                                        <option value="8" {{ $item->frequency_weeks == 8 ? 'selected' : '' }}>2 months</option>
                                        <option value="12" {{ $item->frequency_weeks == 12 ? 'selected' : '' }}>3 months</option>
                                    </select>
                                </div>
                                <button type="submit" class="kb-account-btn kb-account-btn-small kb-account-btn-primary">Save</button>
                            </div>
                        </form>
                    @endif

                    @if(!$item->suspended_at)
                        <form method="POST" action="{{ route('account.subscription.ss.cancel', $item->ss_item_id) }}"
                              onsubmit="return confirm('Cancel Subscribe & Save for this item?')" style="margin-top: 6px;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kb-account-btn kb-account-btn-small kb-account-btn-outline" style="color: #dc2626; border-color: #fca5a5; font-size: 11px;">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    /* Active subscription banner */
    .kb-sub-active-banner { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
    .kb-sub-active-icon { width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .kb-sub-active-title { font-size: 20px; font-weight: 700; color: var(--kb-primary-font, #111827); margin: 0; }
    .kb-sub-active-status { font-size: 13px; color: #16a34a; font-weight: 600; margin: 2px 0 0; }
    .kb-sub-section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--kb-secondary-font, #6b7280); margin-bottom: 10px; }

    /* Benefits grid */
    .kb-sub-benefit-grid { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .kb-sub-benefit-card { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 14px 20px; border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 10px; min-width: 110px; text-align: center; flex: 1; }
    .kb-sub-benefit-card i { font-size: 22px; color: var(--kb-accent, #EB7347); }
    .kb-sub-benefit-card span { font-size: 14px; font-weight: 700; color: var(--kb-primary-font, #111827); }
    .kb-sub-benefit-card small { font-size: 11px; color: var(--kb-secondary-font, #6b7280); }

    /* Subscription details */
    .kb-sub-details { margin-bottom: 16px; }
    .kb-sub-detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--kb-button-border, #e5e7eb); font-size: 14px; }
    .kb-sub-detail-row:last-child { border-bottom: none; }

    /* Promo card */
    .kb-sub-promo { text-align: center; }
    .kb-sub-promo-header { margin-bottom: 8px; }
    .kb-sub-promo-title { font-size: 22px; font-weight: 700; color: var(--kb-primary-font); margin: 0; }
    .kb-sub-price-amount { font-size: 32px; font-weight: 800; color: var(--kb-accent, #EB7347); }
    .kb-sub-price-period { font-size: 14px; color: var(--kb-secondary-font); }
    .kb-sub-promo-desc { font-size: 14px; color: var(--kb-secondary-font); margin-bottom: 20px; }
    .kb-sub-subscribe-btn { font-size: 16px; padding: 12px 32px; }

    /* Card selector */
    .kb-sub-card-selector { text-align: left; margin-bottom: 16px; }
    .kb-sub-card-option { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 8px; margin-bottom: 6px; cursor: pointer; font-size: 13px; transition: border-color 0.15s; }
    .kb-sub-card-option:hover { border-color: var(--kb-button-beige-border, #f59e0b); }
    .kb-sub-card-option input[type="radio"] { accent-color: #f59e0b; }
    .kb-sub-card-option-info { display: flex; align-items: center; gap: 6px; }
    .kb-sub-no-card-notice { display: flex; align-items: center; gap: 8px; padding: 12px; background: #fef3c7; border-radius: 8px; font-size: 13px; color: #92400e; margin-top: 10px; justify-content: center; }

    /* Payment history */
    .kb-sub-payments-list { display: flex; flex-direction: column; gap: 0; }
    .kb-sub-payment-row { display: grid; grid-template-columns: 100px 1fr 150px 70px 80px; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--kb-button-border, #e5e7eb); font-size: 13px; }
    .kb-sub-payment-row:last-child { border-bottom: none; }
    .kb-sub-payment-date { color: var(--kb-secondary-font); }
    .kb-sub-payment-date span { display: block; font-size: 11px; }
    .kb-sub-payment-detail strong { display: block; font-size: 13px; }
    .kb-sub-payment-detail span { font-size: 11px; color: var(--kb-secondary-font); }
    .kb-sub-payment-card { font-size: 12px; color: var(--kb-secondary-font); display: flex; align-items: center; gap: 4px; }
    .kb-sub-payment-amount { font-weight: 700; text-align: right; }
    .kb-badge-success { background: #f0fdf4; color: #16a34a; }
    .kb-badge-warning { background: #fffbeb; color: #d97706; }
    .kb-badge-danger { background: #fef2f2; color: #dc2626; }

    /* S&S */
    .kb-sub-ss-desc { font-size: 13px; color: var(--kb-secondary-font, #6b7280); margin-bottom: 16px; line-height: 1.6; }
    .kb-ss-items-list { display: flex; flex-direction: column; gap: 12px; }
    .kb-ss-item-card { padding: 14px; border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 10px; }
    .kb-ss-item-suspended { opacity: 0.7; border-color: #fde68a; background: #fffbeb; }
    .kb-ss-suspended-tag { display: inline-flex; align-items: center; gap: 3px; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 600; padding: 1px 6px; border-radius: 4px; margin-left: 6px; }
    .kb-ss-item-info h4 { font-size: 15px; font-weight: 700; color: var(--kb-primary-font); margin: 0 0 4px; }
    .kb-ss-item-meta { font-size: 12px; color: var(--kb-secondary-font); }
    .kb-ss-discount-tag { display: inline-block; background: #dcfce7; color: #166534; font-size: 11px; font-weight: 600; padding: 1px 6px; border-radius: 4px; margin-right: 6px; }
    .kb-ss-item-form { margin-top: 10px; }
    .kb-ss-item-controls { display: flex; align-items: flex-end; gap: 10px; flex-wrap: wrap; }
    .kb-ss-control { display: flex; flex-direction: column; gap: 3px; }
    .kb-ss-control label { font-size: 11px; font-weight: 600; color: var(--kb-secondary-font); }
    .kb-ss-select { width: 100px; padding: 5px 8px; font-size: 13px; }

    .kb-account-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-account-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-account-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    @media (max-width: 768px) {
        .kb-sub-payment-row { grid-template-columns: 80px 1fr 60px; }
        .kb-sub-payment-card, .kb-sub-payment-status { display: none; }
    }
</style>
@endsection