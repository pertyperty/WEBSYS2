<x-layout title="People">
    <div class="page-heading"><div><h1 class="text-3xl font-bold">People</h1><p class="page-intro">Find an employee and check their readiness.</p></div><a class="button-secondary" href="{{ route('qualification-matrix') }}"><x-icon name="matrix" />Qualification matrix</a></div>
    <form method="GET" action="{{ route('employees.index') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-full sm:max-w-sm"><label for="employee-search">Search employees</label><input id="employee-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" placeholder="Employee name"></div>
        <button class="button-secondary" type="submit"><x-icon name="search" />Search</button>
        @if(request('search'))<a class="text-link" href="{{ route('employees.index') }}">Clear search</a>@endif
    </form>
    <div class="record-list">
        @forelse($employees as $employee)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0"><h2 class="font-semibold">{{ $employee->name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $employee->jobRole?->name ?? 'No job assigned' }}</p><p class="mt-1 text-xs text-slate-500">{{ $employee->facility?->name ?? 'No facility' }} &middot; {{ $employee->facility?->region?->name ?? 'No region' }}</p></div>
                <div class="flex flex-wrap items-center gap-4"><x-status-badge :status="$employee->is_active ? 'active' : 'inactive'" /><a href="{{ route('employees.show', $employee) }}" class="text-link" aria-label="View readiness for {{ $employee->name }}">View readiness<x-icon name="arrow" class="size-4" /></a></div>
            </section>
        @empty
            <x-empty-state title="No employees in your assigned scope." message="Try another search or check your team assignments." icon="people" />
        @endforelse
    </div>
    <div class="mt-6">{{ $employees->links() }}</div>
</x-layout>
