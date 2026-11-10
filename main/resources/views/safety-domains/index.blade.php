<x-layout title="Safety domains">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Safety domains</h1>
            <p class="mt-3 text-sm text-slate-600">Group qualifications by safety area.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('safety-domains.create') }}" class="button">Add safety domain</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($domains as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>

                    <div class="mt-2"><x-status-badge :status="$record->is_active ? 'active' : 'inactive'" /></div>
                </div>
                <div class="flex gap-5 text-sm">
                    @can('manage-organization')
                        <a href="{{ route('safety-domains.edit', $record) }}" class="font-semibold text-teal-800 underline">Edit</a>
                    @endcan

                </div>
            </section>
        @empty
            <x-empty-state title="No safety domains yet" message="Add a safety domain to get started." />
        @endforelse
    </div>
    <div class="mt-6">{{ $domains->links() }}</div>
</x-layout>
