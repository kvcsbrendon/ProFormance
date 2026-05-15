@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.info-pages.index') }}" class="kb-admin-back">
        <i class="bi bi-arrow-left"></i> Back to Pages
    </a>
    
    <h1 class="kb-admin-title">Edit: {{ $page->title }}</h1>
</div>

<form method="POST" action="{{ route('admin.info-pages.update', $page->page_id) }}">
    @csrf
    @method('PUT')
    
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title">Basic Information</h3>
        
        <div class="kb-form-group">
            <label class="kb-form-label">Page Title</label>
            <input type="text" name="title" class="kb-form-input" value="{{ old('title', $page->title) }}" required>
        </div>
        
        <div class="kb-form-group">
            <label class="kb-form-label">Intro Text</label>
            <textarea name="intro" class="kb-form-textarea kb-form-input" rows="3">{{ old('intro', $page->intro) }}</textarea>
        </div>
        
        <div class="kb-form-group">
            <label class="kb-form-label">Meta Title (SEO)</label>
            <input type="text" name="meta_title" class="kb-form-input" value="{{ old('meta_title', $page->meta_title) }}">
        </div>
        
        <div class="kb-form-group">
            <label class="kb-form-label">Meta Description</label>
            <textarea name="meta_description" class="kb-form-textarea kb-form-input" rows="2">{{ old('meta_description', $page->meta_description) }}</textarea>
        </div>
        
        <div class="kb-form-group">
            <label class="kb-checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }}>
                Page is active (visible to visitors)
            </label>
        </div>
    </div>
    
    <div class="kb-admin-card">
        <div class="kb-admin-card-header">
            <h3 class="kb-admin-card-title">Sections (Q&A)</h3>
            <button type="button" class="kb-admin-btn-sm" onclick="addSection()">
                <i class="bi bi-plus"></i> Add Section
            </button>
        </div>
        
        <div id="sections-container">
            @php
                // Safely get sections, default to empty array if null
                $sections = old('sections', $page->sections ?? []);
            @endphp
            
            @forelse($sections as $index => $section)
            <div class="kb-admin-variant-block" data-index="{{ $index }}">
                <div class="kb-admin-variant-header">
                    <span style="font-weight: 600;">Section {{ $index + 1 }}</span>
                    <button type="button" class="kb-admin-btn-sm-outline" onclick="removeSection(this)" style="color: var(--kb-red-500);">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                
                <div class="kb-form-group">
                    <label class="kb-form-label">Question</label>
                    <input type="text" 
                           name="sections[{{ $index }}][question]" 
                           class="kb-form-input" 
                           value="{{ $section['question'] ?? $section['q'] ?? '' }}" 
                           required>
                </div>
                
                <div class="kb-form-group">
                    <label class="kb-form-label">Answer</label>
                    <textarea name="sections[{{ $index }}][answer]" 
                              class="kb-form-textarea kb-form-input" 
                              rows="3" 
                              required>{{ $section['answer'] ?? $section['a'] ?? '' }}</textarea>
                </div>
            </div>
            @empty
            <div class="kb-admin-variant-block" id="no-sections-message">
                <p class="kb-admin-muted" style="text-align: center; margin: 1rem 0;">
                    No sections yet. Click "Add Section" to create one.
                </p>
            </div>
            @endforelse
        </div>
    </div>
    
    <div class="kb-admin-form-actions">
        <button type="submit" class="kb-admin-btn">Save Changes</button>
        <a href="{{ route('admin.info-pages.index') }}" class="kb-admin-btn-outline">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
    // Get the current section count, default to 0
    let sectionIndex = {{ count(old('sections', $page->sections ?? [])) }};
    
    function addSection() {
        const container = document.getElementById('sections-container');
        
        // Remove the "no sections" message if it exists
        const noSectionsMsg = document.getElementById('no-sections-message');
        if (noSectionsMsg) {
            noSectionsMsg.remove();
        }
        
        const template = `
            <div class="kb-admin-variant-block" data-index="${sectionIndex}">
                <div class="kb-admin-variant-header">
                    <span style="font-weight: 600;">Section ${sectionIndex + 1}</span>
                    <button type="button" class="kb-admin-btn-sm-outline" onclick="removeSection(this)" style="color: var(--kb-red-500);">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                
                <div class="kb-form-group">
                    <label class="kb-form-label">Question</label>
                    <input type="text" name="sections[${sectionIndex}][question]" class="kb-form-input" required>
                </div>
                
                <div class="kb-form-group">
                    <label class="kb-form-label">Answer</label>
                    <textarea name="sections[${sectionIndex}][answer]" class="kb-form-textarea kb-form-input" rows="3" required></textarea>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', template);
        sectionIndex++;
    }
    
    function removeSection(button) {
        if (confirm('Remove this section?')) {
            const block = button.closest('.kb-admin-variant-block');
            block.remove();
            
            // If no sections left, show the empty message
            const container = document.getElementById('sections-container');
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div class="kb-admin-variant-block" id="no-sections-message">
                        <p class="kb-admin-muted" style="text-align: center; margin: 1rem 0;">
                            No sections yet. Click "Add Section" to create one.
                        </p>
                    </div>
                `;
                sectionIndex = 0;
            }
        }
    }
</script>
@endpush
@endsection