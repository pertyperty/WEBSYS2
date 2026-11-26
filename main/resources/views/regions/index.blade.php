<x-layout title="Regions">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Regions</h1></div>
        @can('manage-organization')
            <a href="{{ route('regions.create') }}" class="button"><x-icon name="plus" class="size-4" />Add region</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($regions as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <h2 class="font-semibold text-slate-900">{{ $record->name }}</h2>
                <div class="flex items-center gap-4">
                    <x-status-badge :status="$record->is_active ? 'active' : 'inactive'" />
                    @can('manage-organization')
                        <a href="{{ route('regions.edit', $record) }}" class="text-link">Edit</a>
                    @endcan
                </div>
            </section>
        @empty
            <x-empty-state title="No regions yet" message="Add a region to organize facilities." />
        @endforelse
    </div>
    <div class="mt-6">{{ $regions->links() }}</div>
</x-layout>
