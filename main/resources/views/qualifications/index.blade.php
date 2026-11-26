<x-layout title="Qualifications">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Qualifications</h1></div>
        @can('manage-organization')
            <a href="{{ route('qualifications.create') }}" class="button"><x-icon name="plus" class="size-4" />Add qualification</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($qualifications as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900">{{ $record->name }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $record->safetyDomain->name }} &middot;
                        Expiry {{ $record->requires_expiry ? 'required' : 'optional' }} &middot;
                        Training {{ $record->requires_training ? 'required' : 'not required' }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <x-status-badge :status="$record->is_active ? 'active' : 'inactive'" />
                    @can('manage-organization')
                        <a href="{{ route('qualifications.edit', $record) }}" class="text-link">Edit</a>
                    @endcan
                </div>
            </section>
        @empty
            <x-empty-state title="No qualifications yet" message="Add a qualification to define required credentials." />
        @endforelse
    </div>
    <div class="mt-6">{{ $qualifications->links() }}</div>
</x-layout>
