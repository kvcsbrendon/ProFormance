{{-- checkout/partials/order-notes.blade.php --}}
<div class="kb-checkout-section">
    <div class="kb-checkout-section-header">
        <span class="kb-checkout-step-num">4</span>
        <h2 class="kb-checkout-section-title">Order Notes</h2>
    </div>

    <div class="kb-form-group">
        <label class="kb-form-label">Delivery instructions or special requests <span class="kb-form-optional">(optional)</span></label>
        <textarea name="order_notes" class="kb-form-input kb-form-textarea" rows="3"
                  placeholder="e.g. Leave at the front door, ring doorbell twice...">{{ old('order_notes') }}</textarea>
    </div>
</div>
