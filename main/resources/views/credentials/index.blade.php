<x-layout title="Credentials">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Credentials</h1>
            <p class="mt-3 text-sm text-slate-600">Track submissions, review decisions, and renewals.</p>
        </div>
        @can('submit-credentials')<a href="{{ route('credentials.create') }}" class="button">Submit evidence</a>@endcan
    </div>
    <form method="GET" action="{{ route('credentials.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label for="status" class="text-sm font-medium">Review status</label>
            <select id="status" name="status" class="field">
                <option value="">All submissions</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="button">Filter</button>
    </form>
    <div class="record-list">
        @forelse($documents as $document)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $document->qualification->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $document->employee->name }}</p>
                    <div class="mt-2"><x-status-badge :status="$document->status->value" /></div>
                    <details class="mt-2 text-xs text-slate-500"><summary class="cursor-pointer">Reference</summary><p class="mt-2 break-all font-mono">{{ $document->reference }}</p></details>
                </div>
                <a href="{{ route('credentials.show', $document) }}" class="text-sm font-semibold text-teal-800 underline">View submission</a>
            </section>
        @empty
            <p class="panel text-sm text-slate-600">No matching submissions.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $documents->links() }}</div>
</x-layout>
