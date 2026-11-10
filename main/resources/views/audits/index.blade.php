<x-layout title="Audits">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Audits</h1>
            <p class="page-intro">Readiness captured at a point in time.</p>
        </div>
        @can('conduct-audits')
            <a class="button" href="{{ route('audits.create') }}">Capture audit</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($audits as $audit)
            <a class="record-row group flex flex-wrap items-center justify-between gap-4" href="{{ route('audits.show', $audit) }}">
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $audit->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $audit->snapshot['facility_name'] ?? $audit->snapshot['region_name'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $audit->audited_on->format('M j, Y') }} &middot; {{ $audit->author->name }}</p>
                </div>
                <span class="shrink-0 text-sm text-slate-600">{{ $audit->findings_count }} findings <span aria-hidden="true">&rarr;</span>
                </span>
            </a>
        @empty
            <x-empty-state title="No audits yet" message="Captured audits will appear here for your assigned scope." />
        @endforelse
    </div>
    <div class="mt-6">{{ $audits->links() }}</div>
</x-layout>
