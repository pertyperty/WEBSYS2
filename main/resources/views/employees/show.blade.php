<x-layout title="Employee readiness">
    <a href="{{ route('employees.index') }}" class="text-link">Back to people</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-3xl font-bold">{{ $employee->name }}</h1><p class="page-intro">{{ $employee->facility?->name ?? 'No facility assigned' }} &middot; {{ $employee->jobRole?->name ?? 'No job assigned' }}</p></div>
        <a class="button-secondary" href="{{ route('employees.compliance-history', $employee) }}">Assessment history</a>
    </div>
    <section class="panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <div><x-status-badge :status="$assessment['status']" /><p class="mt-3 text-sm text-slate-600">{{ $assessment['satisfied_count'] }} of {{ $assessment['required_count'] }} required qualifications satisfied</p></div>
            <p class="text-3xl font-semibold tracking-tight">{{ $assessment['percentage'] === null ? '--' : $assessment['percentage'].'%' }}<span class="mt-1 block text-xs font-normal text-slate-500">{{ $assessment['percentage'] === null ? 'Not assessable' : 'Requirements satisfied' }}</span></p>
        </div>
        @if($assessment['reason'])<p class="notice notice-warning mt-5">{{ $assessment['reason'] }}</p>@endif
        <p class="mt-4 text-xs text-slate-500">Updated {{ $assessment['assessed_on'] }} &middot; Asia/Manila</p>
    </section>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-semibold">Required qualifications</h2><a class="text-link" href="{{ route('training-enrollments.index') }}">Training records</a></div>
    <div class="record-list">
        @forelse($assessment['items'] as $item)
            <section class="record-row">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-xs text-slate-500">{{ $item['domain'] }}</p><h3 class="mt-1 font-semibold">{{ $item['name'] }}</h3></div>
                    <x-status-badge :status="$item['state']" />
                </div>
                @if($item['requires_training'])<p class="mt-3 text-sm text-slate-600">Training {{ $item['training_completed'] ? 'completed' : 'incomplete' }}</p>@endif
                @if($item['expiring_soon'])<p class="mt-3 text-sm font-medium text-amber-800">Expiring soon: {{ $item['expires_at'] }}</p>@endif
                @if($item['pending_count'])<p class="mt-2 text-xs text-slate-500">{{ $item['pending_count'] }} pending submission(s). Earlier valid approvals still apply.</p>@endif
                @if(auth()->id() === $employee->id)
                    @can('submit-credentials')
                        @if($item['requires_training'] && ! $item['training_completed'])
                            <a class="text-link mt-2" href="{{ route('training-courses.index') }}">Find training<x-icon name="arrow" class="size-4" /></a>
                        @elseif($item['state'] !== 'satisfied' || $item['expiring_soon'])
                            <a class="text-link mt-2" href="{{ route('credentials.create', ['qualification' => $item['qualification_id']]) }}">{{ $item['expiring_soon'] ? 'Submit renewal' : 'Submit evidence' }}<x-icon name="arrow" class="size-4" /></a>
                        @endif
                    @endcan
                @endif
            </section>
        @empty
            <x-empty-state title="No requirements configured. This does not mean the employee is compliant." message="An administrator or compliance officer needs to configure this job's requirements." icon="matrix" />
        @endforelse
    </div>
    <details class="mt-6 text-sm text-slate-500"><summary class="text-link cursor-pointer">How readiness is assessed</summary><p class="mt-2 max-w-2xl">These are requirements, not awarded qualifications. Each requirement needs approved, valid evidence. Training completion required by a qualification is recorded separately. Evidence stays valid through its expiry date. Renewal warnings start 30 days before expiry.</p></details>
</x-layout>
