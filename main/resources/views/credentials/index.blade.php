<x-layout title="Documents">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ auth()->user()->role === \App\Enums\UserRole::Staff ? 'My documents' : 'Documents' }}</h1></div>
        @can('submit-credentials')<a href="{{ route('credentials.create') }}" class="button"><x-icon name="plus" class="size-4" />Submit evidence</a>@endcan
    </div>
    <x-filter-bar :action="route('credentials.index')">
        <div class="w-full sm:max-w-sm"><label for="credential-search">Employee, qualification or reference</label><input id="credential-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-52"><label for="status">Review status</label><select id="status" name="status" class="field"><option value="">All submissions</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value === 'pending' ? 'Awaiting review' : ucfirst($status->value) }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <x-result-summary :paginator="$documents" noun="submission" :filtered="$filtered" />
        <div class="record-list">
            @forelse($documents as $document)
                <a href="{{ route('credentials.show', $document) }}" class="record-row group flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $document->qualification->name }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $document->employee->name }} &middot; <span class="font-mono text-xs text-slate-400">{{ $document->reference }}</span></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-status-badge :status="$document->status->value" />
                        <x-icon name="arrow" class="size-4 text-slate-400 transition-colors group-hover:text-teal-800" />
                    </div>
                </a>
            @empty
                <x-empty-state :title="$filtered ? 'No submissions match your filters' : 'No submissions yet'" :message="$filtered ? 'Try another search or review status.' : 'Uploaded credentials will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('credentials.index') }}" @click.prevent="reset()">Clear filters</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $documents->links() }}</div>
    </div>
</div>
</x-layout>
