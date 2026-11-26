<x-layout title="People">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">People</h1>
            <p class="page-intro">Compliance statuses are system-calculated from assigned requirements; they do not independently authorize deployment.</p>
        </div>
        <a class="button-secondary" href="{{ route('qualification-matrix') }}"><x-icon name="matrix" class="size-4" />Qualification matrix</a>
    </div>
    <x-filter-bar :action="route('employees.index')">
        <div class="w-full sm:max-w-sm"><label for="employee-search">Name, job or facility</label><input id="employee-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-64"><label for="facility_id">Facility</label><select class="field" id="facility_id" name="facility_id"><option value="">All assigned facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('facility_id');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <x-result-summary :paginator="$employees" noun="employee" :filtered="$filtered" />
        <div class="record-list">
            @forelse($employees as $employee)
                @php($result = $assessments[$employee->id])
                <a href="{{ route('employees.show', $employee) }}" class="record-row group flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $employee->name }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $employee->jobRole?->name ?? 'No job' }} &middot; {{ $employee->facility?->name ?? 'No facility' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if($result['required_count'] > 0 && $result['percentage'] !== null)
                            <span class="text-xs font-medium text-slate-500">{{ $result['satisfied_count'] }}/{{ $result['required_count'] }} met</span>
                        @endif
                        <x-status-badge :status="$result['status']" :label="\App\Enums\ComplianceStatus::from($result['status'])->label()" />
                        @if(! $employee->is_active)<x-status-badge status="inactive" />@endif
                        <x-icon name="arrow" class="size-4 text-slate-400 transition-colors group-hover:text-teal-800" />
                    </div>
                </a>
            @empty
                <x-empty-state :title="$filtered ? 'No employees match your filters' : 'No employees in your assigned scope.'" :message="$filtered ? 'Try a different search or facility.' : 'Assigned employees will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('employees.index') }}" @click.prevent="reset()">Clear filters</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $employees->links() }}</div>
    </div>
</div>
</x-layout>
