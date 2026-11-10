<x-layout title="Compliance history">
    <a href="{{ route('employees.show', $employee) }}" class="text-sm font-semibold text-teal-800 underline">Back to employee</a>
    <h1 class="mt-4 text-3xl font-bold">Compliance history: {{ $employee->name }}</h1>
    <p class="mt-3 text-sm text-slate-600">Material assessment snapshots, newest first. Assignment names and requirements are preserved as assessed. Unchanged reads do not append history. Current employee scope controls access.</p>
    <div class="mt-8 space-y-5">
        @foreach($history as $entry)
            <section class="panel">
                <h2 class="font-semibold">{{ $entry->status->label() }} / {{ $entry->assessed_on->toDateString() }} (Asia/Manila)</h2>
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
