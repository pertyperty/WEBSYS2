<x-layout title="People">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading"><div><h1 class="text-3xl font-bold">People</h1><p class="page-intro">Find an employee and check their readiness.</p></div><a class="button-secondary" href="{{ route('qualification-matrix') }}"><x-icon name="matrix" />Qualification matrix</a></div>
    <x-filter-bar :action="route('employees.index')">
        <div class="w-full sm:max-w-sm"><label for="employee-search">Name, job or facility</label><input id="employee-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div><div class="w-full sm:w-64"><label for="facility_id">Facility</label><select class="field" id="facility_id" name="facility_id"><option value="">All assigned facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('facility_id');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    <x-result-summary :paginator="$employees" noun="employee" :filtered="$filtered" />
    <div class="record-list">
        @forelse($employees as $employee)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0"><h2 class="font-semibold">{{ $employee->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $employee->jobRole?->name ?? 'No job assigned' }}</p><p class="mt-1 text-xs text-slate-500">{{ $employee->facility?->name ?? 'No facility' }} &middot; {{ $employee->facility?->region?->name ?? 'No region' }}</p></div>
                <div class="flex flex-wrap items-center gap-4"><x-status-badge :status="$employee->is_active ? 'active' : 'inactive'" /><a href="{{ route('employees.show', $employee) }}" class="text-link" aria-label="View readiness for {{ $employee->name }}">View readiness<x-icon name="arrow" class="size-4" /></a></div>
            </section>
        @empty
            <x-empty-state :title="$filtered ? 'No employees match your filters' : 'No employees in your assigned scope.'" :message="$filtered ? 'Try another name, job or facility.' : 'Employees will appear once they are assigned to your team.'">
                @if($filtered)<a class="text-link" href="{{ route('employees.index') }}" @click.prevent="reset()">Clear filters</a>@endif
            </x-empty-state>
        @endforelse
    </div>
    <div class="mt-6">{{ $employees->links() }}</div>
    </div>
</div>
</x-layout>
