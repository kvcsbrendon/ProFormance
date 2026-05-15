@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <div class="kb-admin-page-header">
        <h1 class="kb-admin-title">Information Pages</h1>
    </div>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Route</th>
                    <th>Sections</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                <tr>
                    <td>
                        <div style="font-weight: 600;">{{ $page->title }}</div>
                        <div class="kb-admin-muted">{{ $page->slug }}</div>
                    </td>
                    <td>
                        <code>{{ $page->route_name }}</code>
                    </td>
                    <td>
                        {{ count($page->sections) }} sections
                    </td>
                    <td>
                        @if($page->is_active)
                            <span class="kb-admin-pill kb-pill-green">Active</span>
                        @else
                            <span class="kb-admin-pill kb-pill-grey">Hidden</span>
                        @endif
                    </td>
                    <td>
                        {{ $page->updated_at->format('d M Y') }}
                    </td>
                    <td>
                        <div class="kb-admin-actions">
                            <a href="{{ route('admin.info-pages.edit', $page->page_id) }}" class="kb-admin-btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="{{ route('page.show', $page->slug) }}" class="kb-admin-btn-sm-outline" target="_blank">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection