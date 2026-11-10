<x-layout title="Qualifications">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Qualifications</h1>
            <p class="mt-3 text-sm text-slate-600">Define the evidence and training your team needs.</p>
        </div>
        @can('manage-organization')
            <a href="{{ route('qualifications.create') }}" class="button">Add qualification</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($qualifications as $record)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $record->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Domain: {{ $record->safetyDomain->name }} /
                        Expiry {{ $record->requires_expiry ? 'required' : 'optional' }} /
                        Training {{ $record->requires_training ? 'required' : 'not required' }}
                    </p>
                    <div class="mt-2"><x-status-badge :status="$record->is_active ? 'active' : 'inactive'" /></div>
                </div>
                <div class="flex gap-5 text-sm">
                    @can('manage-organization')
                        <a href="{{ route('qualifications.edit', $record) }}" class="font-semibold text-teal-800 underline">Edit</a>
                    @endcan

                </div>
            </section>
        @empty
            <x-empty-state title="No qualifications yet" message="Add a qualification to get started." />
        @endforelse
    </div>
    <div class="mt-6">{{ $qualifications->links() }}</div>
</x-layout>
