{{-- resources/views/vendor/pagination/kb-products.blade.php --}}
@if ($paginator->hasPages())
    <nav class="kb-pager" role="navigation" aria-label="Product pagination">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="kb-pager-link kb-pager-disabled">
                <i class="bi bi-chevron-left"></i>
                <span class="kb-pager-label">Prev</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="kb-pager-link" rel="prev">
                <i class="bi bi-chevron-left"></i>
                <span class="kb-pager-label">Prev</span>
            </a>
        @endif

        {{-- Page numbers (smart window) --}}
        @php
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();
            $window = 2; // pages either side of current

            $from = max(1, $current - $window);
            $to = min($last, $current + $window);

            // Always show first and last
            $pages = [];
            if ($from > 1) $pages[] = 1;
            if ($from > 2) $pages[] = '...';
            for ($i = $from; $i <= $to; $i++) $pages[] = $i;
            if ($to < $last - 1) $pages[] = '...';
            if ($to < $last) $pages[] = $last;
        @endphp

        <div class="kb-pager-pages">
            @foreach ($pages as $page)
                @if ($page === '...')
                    <span class="kb-pager-ellipsis">…</span>
                @elseif ($page == $current)
                    <span class="kb-pager-link kb-pager-current">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="kb-pager-link">{{ $page }}</a>
                @endif
            @endforeach
        </div>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="kb-pager-link" rel="next">
                <span class="kb-pager-label">Next</span>
                <i class="bi bi-chevron-right"></i>
            </a>
        @else
            <span class="kb-pager-link kb-pager-disabled">
                <span class="kb-pager-label">Next</span>
                <i class="bi bi-chevron-right"></i>
            </span>
        @endif
    </nav>
@endif