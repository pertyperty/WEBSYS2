<x-layout title="Compliance history">
    <a href="{{ route('employees.show', $employee) }}" class="text-sm font-semibold text-teal-800 underline">Back to employee</a>
    <h1 class="mt-4 text-3xl font-bold">Compliance history: {{ $employee->name }}</h1>
    <p class="mt-3 text-sm text-slate-600">See how readiness has changed over time. Most recent changes appear first.</p>
    <div class="mt-8 space-y-5">
        @foreach($history as $entry)
            <section class="panel">
                <div class="flex flex-wrap items-center gap-3"><x-status-badge :status="$entry->status->value" /><h2 class="text-sm font-medium text-slate-500">{{ $entry->assessed_on->format('M j, Y') }}</h2></div>
                <p class="mt-2 text-sm">{{ $entry->satisfied_count }} / {{ $entry->required_count }} required qualifications satisfied; {{ $entry->percentage === null ? 'No percentage: not assessable' : $entry->percentage.'%' }}.</p>
                <p class="mt-2 text-sm text-slate-600">{{ $entry->snapshot['facility_name'] ?? 'No facility' }} / {{ $entry->snapshot['job_role_name'] ?? 'No job' }}. Source: {{ str_replace('_', ' ', $entry->source) }}. Recorded by {{ $entry->actor?->name ?? 'System' }} at {{ $entry->created_at->timezone('Asia/Manila')->format('Y-m-d H:i:s') }}.</p>
                @if($entry->snapshot['reason'])<p class="mt-2 text-sm text-amber-800">{{ $entry->snapshot['reason'] }}</p>@endif
                <details class="mt-4"><summary class="cursor-pointer text-sm font-semibold text-teal-800">Qualification states at this assessment</summary>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse($entry->snapshot['items'] as $item)
                            <li>
                                {{ $item['name'] }} / {{ ucfirst($item['state']) }}
                                @if($item['requires_training'] && ! $item['training_completed'])
                                    / Training incomplete
                                @endif
                                @if($item['expiring_soon'])
                                    / Expiring soon: {{ $item['expires_at'] }}
                                @endif
                            </li>
                        @empty
                            <li>No requirements configured.</li>
                        @endforelse
                    </ul>
                </details>
            </section>
        @endforeach
    </div>
    <div class="mt-6">{{ $history->links() }}</div>
</x-layout>
