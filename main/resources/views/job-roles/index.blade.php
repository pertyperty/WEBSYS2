<x-layout title="Jobs">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Jobs</h1></div>
        @can('manage-organization')
            <a href="{{ route('job-roles.create') }}" class="button"><x-icon name="plus" class="size-4" />Add job</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($jobs as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900">{{ $record->name }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $record->qualifications_count }} {{ \Illuminate\Support\Str::plural('requirement', $record->qualifications_count) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-status-badge :status="$record->is_active ? 'active' : 'inactive'" />
                    @can('manage-requirements')
                        <a href="{{ route('job-roles.requirements.edit', $record) }}" class="text-link">Requirements</a>
                    @endcan
                    @can('manage-organization')
                        <a href="{{ route('job-roles.edit', $record) }}" class="text-link">Edit</a>
                    @endcan
                </div>
            </section>
        @empty
            <x-empty-state title="No job roles yet" message="Add a job role to configure its required qualifications." />
        @endforelse
    </div>
    <div class="mt-6">{{ $jobs->links() }}</div>
</x-layout>
