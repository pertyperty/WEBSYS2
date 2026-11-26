<x-layout title="Compliance history">
    <a href="{{ route('employees.show', $employee) }}" class="back-link"><x-icon name="arrow-left" class="size-4" />{{ $employee->name }}</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Assessment history</h1></div>
    </div>
    <div class="record-list">
        @forelse($history as $entry)
            <section class="record-row space-y-2.5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-status-badge :status="$entry->status->value" />
                        <h2 class="text-sm font-semibold text-slate-900">{{ $entry->assessed_on->format('M j, Y') }}</h2>
                    </div>
                    <span class="text-sm font-semibold text-slate-700">{{ $entry->satisfied_count }}/{{ $entry->required_count }} met @if($entry->percentage !== null)&middot; {{ $entry->percentage }}%@endif</span>
                </div>
                <p class="text-xs text-slate-500">
                    {{ $entry->snapshot['facility_name'] ?? 'No facility' }} &middot; {{ $entry->snapshot['job_role_name'] ?? 'No job' }} &middot;
                    {{ ucfirst(str_replace('_', ' ', $entry->source)) }} by {{ $entry->actor?->name ?? 'System' }} ({{ $entry->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }})
                </p>
                @if($entry->snapshot['reason'])<p class="text-xs font-medium text-amber-800">{{ $entry->snapshot['reason'] }}</p>@endif
                <details class="pt-1">
                    <summary class="cursor-pointer text-xs font-semibold text-teal-800">Qualification breakdown</summary>
                    <ul class="mt-2.5 divide-y divide-slate-100 rounded-lg bg-slate-50 px-3 text-xs">
                        @forelse($entry->snapshot['items'] as $item)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span class="font-medium text-slate-800">{{ $item['name'] }}</span>
                                <span class="text-slate-600">
                                    {{ ucfirst($item['state']) }}
                                    @if($item['requires_training'] && ! $item['training_completed']) &middot; Training needed @endif
                                    @if($item['expiring_soon']) &middot; Exp. {{ $item['expires_at'] }} @endif
                                </span>
                            </li>
                        @empty
                            <li class="py-2 text-slate-500">No requirements configured.</li>
                        @endforelse
                    </ul>
                </details>
            </section>
        @empty
            <x-empty-state title="No assessment history" message="Changes to readiness will be recorded here." />
        @endforelse
    </div>
    <div class="mt-6">{{ $history->links() }}</div>
</x-layout>
