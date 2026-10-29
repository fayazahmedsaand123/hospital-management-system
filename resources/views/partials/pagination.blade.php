@if ($paginator->hasPages())
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2 border-top">
        <div class="text-muted small">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} records
        </div>
        <div>
            {{ $paginator->appends(request()->except('ajax', 'page'))->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endif
