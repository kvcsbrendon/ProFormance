{{-- resources/views/layouts/partials/wishlist-picker.blade.php --}}

<div class="kb-wl-picker-overlay" id="wl-picker-overlay" style="display:none;" onclick="closeWishlistPicker()"></div>

<div class="kb-wl-picker" id="wl-picker" style="display:none;">
    <div class="kb-wl-picker-header">
        <h3>Save to Wishlist</h3>
        <button type="button" class="kb-wl-picker-close" onclick="closeWishlistPicker()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="kb-wl-picker-body" id="wl-picker-body">
        <div class="kb-wl-picker-loading" id="wl-picker-loading">
            <i class="bi bi-arrow-repeat kb-spin"></i> Loading wishlists…
        </div>

        <div class="kb-wl-picker-list" id="wl-picker-list" style="display:none;"></div>

        <div class="kb-wl-picker-empty" id="wl-picker-empty" style="display:none;">
            <i class="bi bi-heart"></i>
            <p>You don't have any wishlists yet.</p>
        </div>
    </div>

    {{-- Create new wishlist inline --}}
    <div class="kb-wl-picker-create" id="wl-picker-create">
        <div class="kb-wl-picker-create-row" id="wl-create-row" style="display:none;">
            <input type="text" id="wl-create-input" class="kb-form-input"
                   placeholder="New wishlist name" maxlength="100">
            <button type="button" class="kb-account-btn kb-account-btn-primary kb-account-btn-small"
                    onclick="createWishlist()">Create</button>
            <button type="button" class="kb-account-btn kb-account-btn-outline kb-account-btn-small"
                    onclick="toggleCreateRow(false)">Cancel</button>
        </div>
        <button type="button" class="kb-wl-picker-new-btn" id="wl-create-btn"
                onclick="toggleCreateRow(true)">
            <i class="bi bi-plus-circle"></i> New Wishlist
        </button>
    </div>

    <div class="kb-wl-picker-success" id="wl-picker-success" style="display:none;">
        <i class="bi bi-check-circle-fill"></i>
        <span id="wl-picker-success-text">Saved!</span>
    </div>
</div>
