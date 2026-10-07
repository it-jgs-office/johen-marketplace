@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Navigasi halaman">
        <p class="admin-pagination__summary">
            @if ($paginator->firstItem())
                Menampilkan <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> dari <strong>{{ $paginator->total() }}</strong> hasil
            @else
                <strong>{{ $paginator->count() }}</strong> hasil
            @endif
        </p>

        <div class="admin-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="admin-pagination__control is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">&lsaquo;</span>
            @else
                <a class="admin-pagination__control" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="admin-pagination__control is-dots" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="admin-pagination__control is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="admin-pagination__control" href="{{ $url }}" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="admin-pagination__control" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">&rsaquo;</a>
            @else
                <span class="admin-pagination__control is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
