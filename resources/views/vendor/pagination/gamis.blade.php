@if ($paginator->hasPages())
<div class="pagi">
  @if ($paginator->onFirstPage())
    <span class="t" aria-disabled="true">‹</span>
  @else
    <a class="t" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
  @endif
  <span class="mut">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
  @if ($paginator->hasMorePages())
    <a class="t" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
  @else
    <span class="t" aria-disabled="true">›</span>
  @endif
</div>
@endif
