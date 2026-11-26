<x-layout title="Facilities">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Facilities</h1></div>
        @can('manage-organization')
            <a href="{{ route('facilities.create') }}" class="button"><x-icon name="plus" class="size-4" />Add facility</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($facilities as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900">{{ $record->name }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $record->region->name }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <x-status-badge :status="$record->is_active ? 'active' : 'inactive'" />
                    @can('manage-organization')
                        <a href="{{ route('facilities.edit', $record) }}" class="text-link">Edit</a>
                    @endcan
                </div>
            </section>
        @empty
            <x-empty-state title="No facilities yet" message="Add a facility to assign staff and managers." />
        @endforelse
    </div>
    <div class="mt-6">{{ $facilities->links() }}</div>
</x-layout>
