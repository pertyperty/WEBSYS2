<x-layout title="Jobs">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Jobs</h1>
            <p class="mt-3 text-sm text-slate-600">Define job roles and their required qualifications.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('job-roles.create') }}" class="button">Add job</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($jobs as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $record->qualifications_count }} mandatory qualifications</p>
                    <div class="mt-2"><x-status-badge :status="$record->is_active ? 'active' : 'inactive'" /></div>
                </div>
                <div class="flex gap-5 text-sm">
                    @can('manage-organization')
                        <a href="{{ route('job-roles.edit', $record) }}" class="font-semibold text-teal-800 underline">Edit</a>
                    @endcan
                    @can('manage-requirements')
                        <a href="{{ route('job-roles.requirements.edit', $record) }}" class="font-semibold text-teal-800 underline">Requirements</a>
                    @endcan
                </div>
            </section>
        @empty
            <x-empty-state title="No job roles yet" message="Add a job to get started." />
        @endforelse
    </div>
    <div class="mt-6">{{ $jobs->links() }}</div>
</x-layout>
