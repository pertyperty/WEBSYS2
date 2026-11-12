@props(['paginator', 'noun', 'filtered' => false])
<p class="mb-3 text-sm text-slate-600" data-filter-summary>
    <span class="font-semibold text-slate-900">{{ number_format($paginator->total()) }} {{ \Illuminate\Support\Str::plural($noun, $paginator->total()) }}</span>{{ $filtered ? ' found with these filters.' : '.' }}
    @if($paginator->hasPages())<span class="ml-1">Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }}.</span>@endif
</p>
