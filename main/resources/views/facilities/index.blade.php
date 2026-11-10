<x-layout title="Facilities">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Facilities</h1>
            <p class="mt-3 text-sm text-slate-600">Manage workplaces and their regions.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('facilities.create') }}" class="button">Add facility</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($facilities as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Region: {{ $record->region->name }}</p>
                    <div class="mt-2"><x-status-badge :status="$record->is_active ? 'active' : 'inactive'" /></div>
                </div>
                <div class="flex gap-5 text-sm">
                    @can('manage-organization')
                        <a href="{{ route('facilities.edit', $record) }}" class="font-semibold text-teal-800 underline">Edit</a>
                    @endcan

                </div>
            </section>
        @empty
            <x-empty-state title="No facilities yet" message="Add a facility to get started." />
        @endforelse
    </div>
    <div class="mt-6">{{ $facilities->links() }}</div>
</x-layout>
