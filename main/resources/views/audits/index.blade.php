<x-layout title="Audits">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Audits</h1>
            <p class="page-intro">Readiness captured at a point in time.</p>
        </div>
        @can('conduct-audits')
            <a class="button" href="{{ route('audits.create') }}">Capture audit</a>
        @endcan
    </div>
    <x-filter-bar :action="route('audits.index')">
        <div class="w-full sm:max-w-sm"><label for="audit-search">Audit title or location</label><input id="audit-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    <x-result-summary :paginator="$audits" noun="audit" :filtered="$filtered" />
    <div class="record-list">
        @forelse($audits as $audit)
            <a class="record-row group flex flex-wrap items-center justify-between gap-4" href="{{ route('audits.show', $audit) }}">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $audit->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $audit->snapshot['facility_name'] ?? $audit->snapshot['region_name'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $audit->audited_on->format('M j, Y') }} &middot; {{ $audit->author->name }}</p>
                </div>
                <span class="shrink-0 text-sm text-slate-600">{{ $audit->findings_count }} findings <span aria-hidden="true">&rarr;</span>
                </span>
            </a>
        @empty
            <x-empty-state :title="$filtered ? 'No audits match your search' : 'No audits yet'" :message="$filtered ? 'Try another audit title or location.' : 'Captured audits will appear here for your assigned scope.'">
                @if($filtered)<a class="text-link" href="{{ route('audits.index') }}" @click.prevent="reset()">Clear filters</a>@endif
            </x-empty-state>
        @endforelse
    </div>
    <div class="mt-6">{{ $audits->links() }}</div>
    </div>
</div>
</x-layout>
