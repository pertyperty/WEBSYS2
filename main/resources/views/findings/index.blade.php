<x-layout title="Corrective work">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Corrective work</h1>
            <p class="page-intro">Track findings through correction and verification.</p>
        </div>
    </div>
    <form class="mb-5 flex flex-wrap items-end gap-3" method="GET">
        <div>
            <label class="block text-sm font-medium" for="status">Show</label>
            <select class="field" id="status" name="status">
                <option value="">All findings</option>
                @foreach(['open' => 'Open', 'in_progress' => 'In progress', 'pending_verification' => 'Awaiting verification', 'resolved' => 'Resolved', 'overdue' => 'Overdue'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="button-secondary" type="submit">Apply</button>
    </form>
    <div class="record-list">
        @forelse($findings as $finding)
            <a class="record-row flex flex-wrap items-center justify-between gap-4" href="{{ route('findings.show', $finding) }}">
                <div class="min-w-0">
                    <h2 class="font-semibold">{{ $finding->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $finding->facility->name }} &middot; {{ $finding->assignee->name }}</p>
                    <p class="mt-1 text-sm {{ $finding->status !== 'resolved' && $finding->due_on->toDateString() < now('Asia/Manila')->toDateString() ? 'font-medium text-rose-700' : 'text-slate-500' }}">{{ $finding->status !== 'resolved' && $finding->due_on->toDateString() < now('Asia/Manila')->toDateString() ? 'Overdue - ' : '' }}Due {{ $finding->due_on->format('M j, Y') }}</p>
                </div>
                <x-status-badge :status="$finding->status" />
            </a>
        @empty
            <x-empty-state title="No findings to show" message="Assigned corrective work will appear here." />
        @endforelse
    </div>
    <div class="mt-6">{{ $findings->links() }}</div>
</x-layout>
