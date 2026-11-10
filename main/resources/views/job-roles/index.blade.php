<x-layout title="Jobs">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Jobs</h1>
            <p class="mt-3 text-sm text-slate-600">Inactive records remain available for history. Records in use cannot be deactivated.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('job-roles.create') }}" class="button">Add record</a>
        @endcan
    </div>
    <div class="space-y-4">
        @forelse($jobs as $record)
            <section class="panel flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $record->qualifications_count }} mandatory qualifications</p>
                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $record->is_active ? 'Active' : 'Inactive' }}</p>
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
            <p class="panel text-sm text-slate-600">No records yet.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $jobs->links() }}</div>
</x-layout>
