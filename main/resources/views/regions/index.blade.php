<x-layout title="Regions">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Regions</h1>
            <p class="mt-3 text-sm text-slate-600">Organize facilities by region.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('regions.create') }}" class="button">Add region</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($regions as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>

                    <div class="mt-2"><x-status-badge :status="$record->is_active ? 'active' : 'inactive'" /></div>
                </div>
                <div class="flex gap-5 text-sm">
                    @can('manage-organization')
                        <a href="{{ route('regions.edit', $record) }}" class="font-semibold text-teal-800 underline">Edit</a>
                    @endcan

                </div>
            </section>
        @empty
            <x-empty-state title="No regions yet" message="Add a region to get started." />
        @endforelse
    </div>
    <div class="mt-6">{{ $regions->links() }}</div>
</x-layout>
