{{-- resources/views/account/reviews.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">My Reviews</h1>
    <p class="kb-account-subtitle">Manage reviews you've left on products.</p>
</div>

@if($reviews->isEmpty())
    <div class="kb-account-empty">
        <i class="bi bi-star"></i>
        <p>You haven't written any reviews yet.</p>
        <a href="{{ route('products.index') }}" class="kb-account-btn kb-account-btn-primary">Browse Products</a>
    </div>
@else
    <div class="kb-account-reviews-list">
        @foreach($reviews as $review)
            <div class="kb-account-card kb-review-card">
                <div class="kb-review-header">
                    <div>
                        <a href="{{ route('products.show', $review->product->product_id ?? '#') }}" class="kb-review-product-name">
                            {{ $review->product->product_name ?? 'Unknown Product' }}
                        </a>
                        <div class="kb-review-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                            @endfor
                            <span class="kb-review-date">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                    <div class="kb-review-actions">
                        <button type="button" class="kb-icon-btn" title="Edit"
                                onclick="toggleReviewEdit({{ $review->review_id }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('account.reviews.destroy', $review->review_id) }}"
                              onsubmit="return confirm('Delete this review?')" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kb-icon-btn kb-icon-btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                @if($review->title)
                    <h3 class="kb-review-title">{{ $review->title }}</h3>
                @endif

                @if($review->body)
                    <p class="kb-review-body">{{ $review->body }}</p>
                @endif

                {{-- Display Uploaded Images --}}
                @if($review->images && $review->images->count() > 0)
                    <div class="kb-review-images" style="display:flex; gap:10px; margin-top:10px;">
                        @foreach($review->images as $img)
                            <a href="{{ asset('images/' . $img->image_path) }}" target="_blank">
                                <img src="{{ asset('images/' . $img->image_path) }}" style="width:60px; height:60px; object-fit:cover; border-radius:4px; border:1px solid #e5e7eb;">
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Helpful Votes & Status Info --}}
                <div style="margin-top: 15px; display: flex; align-items: center; gap: 15px; font-size: 0.85rem;">
                    @if(!$review->is_approved)
                        <span class="kb-badge kb-badge-pending" style="background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 4px;">Pending Approval</span>
                    @endif
                    <span style="color: #4b5563;"><i class="bi bi-hand-thumbs-up-fill" style="color: var(--kb-primary);"></i> {{ $review->helpfulVotes ? $review->helpfulVotes->count() : 0 }} Helpful Votes</span>
                </div>

                {{-- Admin Reply --}}
                @if($review->admin_reply)
                    <div class="kb-admin-reply-account" style="border-left: 3px solid var(--kb-primary); padding: 10px; margin-top: 15px; font-size: 0.85rem; border-radius: 4px;">
                        <strong>Response from ProFormance:</strong>
                        <p style="margin: 5px 0 0 0;">{{ $review->admin_reply }}</p>
                    </div>
                @endif

                {{-- INLINE EDIT FORM --}}
                <div id="edit-review-{{ $review->review_id }}" class="kb-review-edit-form" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <form method="POST" action="{{ route('account.reviews.update', $review->review_id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="kb-form-group">
                            <label class="kb-form-label">Rating</label>
                            <select name="rating" class="kb-form-input">
                                @for($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" {{ $review->rating == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ str_repeat('★', $i) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="kb-form-group">
                            <label class="kb-form-label">Title <span class="kb-form-optional">(optional)</span></label>
                            <input type="text" name="title" class="kb-form-input" value="{{ $review->title }}">
                        </div>

                        <div class="kb-form-group">
                            <label class="kb-form-label">Review</label>
                            <textarea name="body" class="kb-form-input kb-form-textarea" rows="4">{{ $review->body }}</textarea>
                        </div>

                        {{-- Manage Existing Images --}}
                        @if($review->images && $review->images->count() > 0)
                            <div class="kb-form-group">
                                <label class="kb-form-label">Manage Existing Images</label>
                                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                    @foreach($review->images as $img)
                                        <label style="display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer;">
                                            <img src="{{ asset('images/' . $img->image_path) }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <div style="font-size: 0.8rem; color: #dc2626;">
                                                <input type="checkbox" name="delete_images[]" value="{{ $img->image_id }}"> Delete
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Add New Images --}}
                        <div class="kb-form-group">
                            <label class="kb-form-label">Add New Images <span class="kb-form-optional">(optional)</span></label>
                            <input type="file" name="images[]" multiple accept="image/jpeg, image/png, image/webp" class="kb-form-input" style="padding: 8px; background: #fff;">
                        </div>

                        <div class="kb-form-actions">
                            <button type="submit" class="kb-account-btn kb-account-btn-primary">Update Review</button>
                            <button type="button" class="kb-account-btn kb-account-btn-outline"
                                    onclick="toggleReviewEdit({{ $review->review_id }})">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="kb-admin-pagination">{{ $reviews->links() }}</div>
@endif

@endsection

@section('scripts')
<script>
    function toggleReviewEdit(id) {
        const form = document.getElementById('edit-review-' + id);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endsection