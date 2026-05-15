{{-- resources/views/account/saved-cards.blade.php --}}
@extends('account.layout')
@section('account-content')

<div class="kb-account-section">
    <div class="kb-account-section-header">
        <div>
            <h2 class="kb-account-page-title">Saved Cards</h2>
            <p class="kb-account-page-subtitle">Manage your saved payment methods for faster checkout.</p>
        </div>
        <button type="button" class="kb-account-btn kb-account-btn-primary"
                onclick="document.getElementById('add-card-form').style.display = document.getElementById('add-card-form').style.display === 'none' ? 'block' : 'none'">
            <i class="bi bi-plus"></i> Add Card
        </button>
    </div>
</div>

{{-- ADD CARD FORM --}}
<div id="add-card-form" class="kb-account-card" style="display: none;">
    <h3 class="kb-account-card-title">Add New Card</h3>
    <form method="POST" action="{{ route('account.cards.store') }}">
        @csrf
        <div class="kb-form-group">
            <label class="kb-form-label required">Name on Card</label>
            <input type="text" name="card_name" class="kb-form-input"
                   value="{{ old('card_name', Auth::user()->first_name . ' ' . Auth::user()->last_name) }}"
                   placeholder="John Smith" required>
            @error('card_name') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>

        <div class="kb-form-group">
            <label class="kb-form-label required">Card Number</label>
            <input type="text" name="card_number" class="kb-form-input"
                   value="{{ old('card_number') }}"
                   placeholder="1234 5678 9012 3456" maxlength="19" required>
            @error('card_number') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>

        <div class="kb-form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="kb-form-group">
                <label class="kb-form-label required">Expiry Date</label>
                <input type="text" name="card_expiry" class="kb-form-input"
                       value="{{ old('card_expiry') }}" placeholder="MM/YY" maxlength="5" required>
                @error('card_expiry') <div class="kb-form-error">{{ $message }}</div> @enderror
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label required">CVV</label>
                <input type="text" name="card_cvv" class="kb-form-input"
                       value="{{ old('card_cvv') }}" placeholder="123" maxlength="4" required>
                @error('card_cvv') <div class="kb-form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="kb-checkout-simulated-notice" style="margin-bottom: 1rem;">
            <i class="bi bi-shield-check"></i>
            <span>This is a simulated payment system — no real card details are stored or processed.</span>
        </div>

        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-primary">Save Card</button>
            <button type="button" class="kb-account-btn kb-account-btn-outline"
                    onclick="document.getElementById('add-card-form').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

{{-- CARDS LIST --}}
@if($cards->count() > 0)
<div class="kb-cards-grid">
    @foreach($cards as $card)
    <div class="kb-saved-card {{ $card->is_expired ? 'kb-saved-card-expired' : '' }}">
        <div class="kb-saved-card-top">
            <div class="kb-saved-card-brand">
                @if($card->card_brand === 'visa')
                    <i class="bi bi-credit-card-2-front"></i> Visa
                @elseif($card->card_brand === 'mastercard')
                    <i class="bi bi-credit-card"></i> Mastercard
                @elseif($card->card_brand === 'amex')
                    <i class="bi bi-credit-card-fill"></i> Amex
                @else
                    <i class="bi bi-credit-card"></i> {{ ucfirst($card->card_brand) }}
                @endif
            </div>
            @if($card->is_default)
                <span class="kb-saved-card-badge">Default</span>
            @endif
            @if($card->is_expired)
                <span class="kb-saved-card-badge kb-saved-card-badge-expired">Expired</span>
            @endif
        </div>

        <div class="kb-saved-card-number">{{ $card->masked_number }}</div>

        <div class="kb-saved-card-details">
            <div><span class="kb-saved-card-label">Name</span><span>{{ $card->card_name }}</span></div>
            <div><span class="kb-saved-card-label">Expires</span><span>{{ $card->expiry_display }}</span></div>
        </div>

        <div class="kb-saved-card-actions">
            @if(!$card->is_default && !$card->is_expired)
            <form method="POST" action="{{ route('account.cards.default', $card->card_id) }}">
                @csrf
                <button type="submit" class="kb-account-btn kb-account-btn-outline kb-account-btn-small">Set as Default</button>
            </form>
            @endif
            <form method="POST" action="{{ route('account.cards.destroy', $card->card_id) }}" onsubmit="return confirm('Remove this card?')">
                @csrf @method('DELETE')
                <button type="submit" class="kb-account-btn kb-account-btn-danger kb-account-btn-small">Remove</button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="kb-account-empty">
    <i class="bi bi-credit-card" style="font-size:2rem;opacity:0.3;"></i>
    <p>No saved cards yet.</p>
    <p class="kb-form-hint">Click "Add Card" above to save a card for checkout and subscriptions.</p>
</div>
@endif

@endsection