@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Reviews</h1>
    <p class="kb-admin-subtitle">Moderate customer product reviews, handle reports, and reply to feedback.</p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search reviews…" value="{{ request('search') }}">
        <select name="filter" class="kb-form-input">
            <option value="">All Statuses</option>
            <option value="approved" {{ request('filter') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending" {{ request('filter') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="reported" {{ request('filter') === 'reported' ? 'selected' : '' }}>Reported</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','filter']))
            <a href="{{ route('admin.reviews.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Customer & Product</th>
                    <th>Review Content</th>
                    <th>Status & Reports</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $r)
                <tr>
                    {{-- Customer & Product Column --}}
                    <td style="vertical-align: top;">
                        <strong>{{ $r->user->first_name ?? 'Unknown' }} {{ $r->user->last_name ?? '' }}</strong>
                        @if($r->is_verified_purchase)
                            <br><span style="font-size: 0.75rem; color: #10b981;"><i class="bi bi-patch-check-fill"></i> Verified</span>
                        @endif
                        <br><br>
                        <span class="kb-admin-muted" style="font-size: 0.85rem;">Product:</span><br>
                        <a href="{{ route('products.show', $r->product_id) }}" 
   							target="_blank" 
   							style="font-size: 0.85rem; color: #3b82f6; text-decoration: underline;">
    						{{ Str::limit($r->product->product_name ?? '—', 40) }}
						</a>
                        <br><br>
                        <span class="kb-admin-muted" style="font-size: 0.8rem;">{{ $r->created_at->format('d M Y') }}</span>
                    </td>

                    {{-- Review Content Column --}}
                    <td style="vertical-align: top; max-width: 400px;">
                        <div style="color:var(--kb-amber-500); margin-bottom: 5px;">
                            {{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}
                        </div>
                        @if($r->title)<strong>{{ $r->title }}</strong><br>@endif
                        <p style="font-size: 0.9rem; margin-top: 5px;">{{ $r->body }}</p>
                        
                        {{-- Attached Images --}}
                        @if($r->images && $r->images->isNotEmpty())
                            <div style="display: flex; gap: 8px; margin-top: 10px;">
                                @foreach($r->images as $img)
                                    <a href="{{ asset('images/' . $img->image_path) }}" target="_blank">
                                        <img src="{{ asset('images/' . $img->image_path) }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Existing Admin Reply --}}
                        @if($r->admin_reply)
                            <div style="background: #f8fafc; border-left: 3px solid var(--kb-primary); padding: 10px; margin-top: 15px; font-size: 0.85rem; border-radius: 4px;">
                                <strong>Admin Reply:</strong><br>
                                {{ $r->admin_reply }}
                            </div>
                        @endif
                    </td>

                    {{-- Status & Reports Column --}}
                    <td style="vertical-align: top;">
                        <span class="kb-admin-pill {{ $r->is_approved ? 'kb-pill-green' : 'kb-pill-amber' }}" style="margin-bottom: 10px; display: inline-block;">
                            {{ $r->is_approved ? 'Approved' : 'Pending' }}
                        </span>
                        
                        <div style="font-size: 0.85rem; margin-top: 5px;">
                            <i class="bi bi-hand-thumbs-up"></i> {{ $r->helpfulVotes->count() ?? 0 }} Helpful
                        </div>

                        @if($r->reports && $r->reports->isNotEmpty())
                            <div style="margin-top: 10px; padding: 8px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; font-size: 0.8rem; color: #dc2626;">
                                <strong><i class="bi bi-flag-fill"></i> {{ $r->reports->count() }} Reports</strong>
                                <ul style="margin: 5px 0 0 15px; padding: 0;">
                                    @foreach($r->reports->take(3) as $report)
                                        <li>{{ ucfirst($report->reason) }}</li>
                                    @endforeach
                                    @if($r->reports->count() > 3) <li>...and more</li> @endif
                                </ul>
                            </div>
                        @endif
                    </td>

                    {{-- Actions Column --}}
                    <td class="kb-admin-actions" style="vertical-align: top;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            {{-- Approve / Reject --}}
                            @if(!$r->is_approved)
                                <form method="POST" action="{{ route('admin.reviews.approve', $r->review_id) }}">@csrf
                                    <button class="kb-admin-btn-sm" style="width: 100%;">Approve</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.reviews.reject', $r->review_id) }}">@csrf
                                    <button class="kb-admin-btn-sm kb-admin-btn-sm-outline" style="width: 100%;">Unpublish</button>
                                </form>
                            @endif
                            
                            {{-- Toggle Reply Form --}}
                            <button type="button" class="kb-admin-btn-sm kb-admin-btn-sm-outline" onclick="document.getElementById('reply-form-{{ $r->review_id }}').style.display = 'block';">
                                {{ $r->admin_reply ? 'Edit Reply' : 'Reply' }}
                            </button>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('admin.reviews.destroy', $r->review_id) }}" onsubmit="return confirm('Permanently delete this review?')">@csrf @method('DELETE')
                                <button class="kb-admin-btn-sm kb-admin-btn-sm-danger" style="width: 100%;">Delete</button>
                            </form>
                            @if(isset($r->reports) && $r->reports->where('status', 'pending')->count() > 0)
                                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                                    <strong style="font-size: 0.8rem; color: #dc2626;">Pending Reports:</strong>
                                    
                                    @foreach($r->reports->where('status', 'pending') as $report)
                                        <div style="margin-top: 5px; padding: 6px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px;">
                                            <div style="font-size: 0.75rem; margin-bottom: 6px; color: #991b1b;">
                                                Reason: <strong>{{ ucfirst($report->reason) }}</strong>
                                            </div>
                                            <div style="display: flex; gap: 5px;">
                                                <form method="POST" action="{{ route('admin.reviews.reports.dismiss', $report->report_id) }}" style="display:inline;">
                                                    @csrf
                                                    <button class="kb-admin-btn-sm kb-admin-btn-sm-outline" style="padding: 2px 6px; font-size: 0.7rem;" title="Ignore this report">Dismiss</button>
                                                </form>
                                                
                                                <form method="POST" action="{{ route('admin.reviews.reports.resolve', $report->report_id) }}" style="display:inline;">
                                                    @csrf
                                                    <button class="kb-admin-btn-sm" style="padding: 2px 6px; font-size: 0.7rem; background: #dc2626; color: white; border: none;" title="Mark as resolved">Resolve</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Hidden Reply Form --}}
                        <form method="POST" action="{{ route('admin.reviews.reply', $r->review_id) }}" id="reply-form-{{ $r->review_id }}" style="display: none; margin-top: 10px; background: #fff; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px;">
                            @csrf
                            <label style="font-size: 0.8rem; font-weight: bold; margin-bottom: 5px; display: block;">Your Reply</label>
                            <textarea name="admin_reply" rows="3" class="kb-form-input" style="font-size: 0.85rem; padding: 5px;" placeholder="Write a public reply...">{{ $r->admin_reply }}</textarea>
                            <div style="display: flex; gap: 5px; margin-top: 5px;">
                                <button type="submit" class="kb-admin-btn-sm" style="flex: 1;">Save</button>
                                <button type="button" class="kb-admin-btn-sm kb-admin-btn-sm-outline" onclick="document.getElementById('reply-form-{{ $r->review_id }}').style.display = 'none';">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="kb-admin-empty-row">No reviews found matching your criteria.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $reviews->links() }}</div>
</div>
@endsection