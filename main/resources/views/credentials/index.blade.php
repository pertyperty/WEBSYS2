<x-layout title="Credentials">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Credentials</h1>
            <p class="mt-3 text-sm text-slate-600">Track submissions, review decisions, and renewals.</p>
        </div>
        @can('submit-credentials')<a href="{{ route('credentials.create') }}" class="button">Submit evidence</a>@endcan
    </div>
    <x-filter-bar :action="route('credentials.index')">
        <div class="w-full sm:max-w-sm"><label for="credential-search">Employee, qualification or reference</label><input id="credential-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div><div class="w-full sm:w-52"><label for="status">Review status</label><select id="status" name="status" class="field"><option value="">All submissions</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->value === 'pending' ? 'Awaiting review' : ucfirst($status->value) }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    <x-result-summary :paginator="$documents" noun="submission" :filtered="$filtered" />
    <div class="record-list">
        @forelse($documents as $document)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $document->qualification->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $document->employee->name }}</p>
                    <div class="mt-2"><x-status-badge :status="$document->status->value" /></div>
                    <details class="mt-2 text-xs text-slate-500"><summary class="cursor-pointer">Reference</summary><p class="mt-2 break-all font-mono">{{ $document->reference }}</p></details>
                </div>
                <a href="{{ route('credentials.show', $document) }}" class="text-sm font-semibold text-teal-800 underline">View submission</a>
            </section>
        @empty
            <x-empty-state :title="$filtered ? 'No submissions match your filters' : 'No submissions yet'" :message="$filtered ? 'Try another employee, qualification or review status.' : 'Submitted evidence and its review status will appear here.'">
                @if($filtered)<a class="text-link" href="{{ route('credentials.index') }}" @click.prevent="reset()">Clear filters</a>@endif
            </x-empty-state>
        @endforelse
    </div>
    <div class="mt-6">{{ $documents->links() }}</div>
    </div>
</div>
</x-layout>
