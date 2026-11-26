@props(['paginator', 'noun', 'filtered' => false])
<p class="mb-3 text-sm text-slate-500" data-filter-summary>
    <span class="font-semibold text-slate-900">{{ number_format($paginator->total()) }} {{ \Illuminate\Support\Str::plural($noun, $paginator->total()) }}</span>@if($filtered)<span> matching</span>@endif
    @if($paginator->hasPages())<span> &middot; {{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</span>@endif
</p>
