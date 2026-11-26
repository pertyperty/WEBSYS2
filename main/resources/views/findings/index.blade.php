<x-layout title="Corrective work">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Corrective work</h1></div>
    </div>
    <x-filter-bar :action="route('findings.index')">
        <div class="w-full sm:max-w-sm"><label for="finding-search">Finding, facility or assignee</label><input id="finding-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-52"><label for="status">Status</label><select class="field" id="status" name="status"><option value="">All findings</option>@foreach(['open' => 'Open', 'in_progress' => 'In progress', 'pending_verification' => 'Awaiting verification', 'resolved' => 'Resolved', 'overdue' => 'Overdue'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status');
        $today = now('Asia/Manila')->toDateString();
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <x-result-summary :paginator="$findings" noun="finding" :filtered="$filtered" />
        <div class="record-list">
            @forelse($findings as $finding)
                @php($overdue = $finding->status !== 'resolved' && $finding->due_on->toDateString() < $today)
                <a class="record-row group flex flex-wrap items-center justify-between gap-4" href="{{ route('findings.show', $finding) }}">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $finding->title }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $finding->facility->name }} &middot; {{ $finding->assignee->name }} &middot;
                            <span class="{{ $overdue ? 'font-semibold text-rose-700' : '' }}">{{ $overdue ? 'Overdue &middot; ' : '' }}Due {{ $finding->due_on->format('M j, Y') }}</span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <x-status-badge :status="$finding->status" />
                        <x-icon name="arrow" class="size-4 text-slate-400 transition-colors group-hover:text-teal-800" />
                    </div>
                </a>
            @empty
                <x-empty-state :title="$filtered ? 'No findings match your filters' : 'No findings yet'" :message="$filtered ? 'Try another search or status.' : 'Assigned corrective work will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('findings.index') }}" @click.prevent="reset()">Clear filters</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $findings->links() }}</div>
    </div>
</div>
</x-layout>
