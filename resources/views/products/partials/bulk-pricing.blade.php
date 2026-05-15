{{-- resources/views/products/partials/bulk-pricing.blade.php --}}

<div class="kb-bulk-pricing" id="bulk-pricing-section" style="{{ empty($defaultBulkTiers) ? 'display:none;' : '' }}">
    <div class="kb-bulk-header">
        <i class="bi bi-box-seam"></i>
        <span class="kb-bulk-title">Buy More, Save More</span>
    </div>
    <div class="kb-bulk-tiers" id="bulk-tiers">
        @foreach($defaultBulkTiers as $tier)
        <div class="kb-bulk-tier">
            <span class="kb-bulk-tier-qty">{{ $tier['min_quantity'] }}+</span>
            <span class="kb-bulk-tier-price">{{ $symbol ?? '£' }}{{ number_format($tier['price'], 2) }} each</span>
        </div>
        @endforeach
    </div>
</div>

<script>
    window.__bulkTiers = @json($bulkTiers ?? []);
</script>
