<x-layout title="Employee readiness">
    @php($staff = auth()->user()->role === \App\Enums\UserRole::Staff)
    <a href="{{ $staff ? route('dashboard') : route('employees.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />{{ $staff ? 'Overview' : 'People' }}</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $employee->name }}</h1>
            <p class="page-intro">{{ $employee->jobRole?->name ?? 'No job' }} &middot; {{ $employee->facility?->name ?? 'No facility' }}</p>
        </div>
        <a class="button-secondary" href="{{ route('employees.compliance-history', $employee) }}">Assessment history</a>
    </div>
    <section class="panel mb-6">
        <div class="flex flex-wrap items-center justify-between gap-5">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$assessment['status']" :label="\App\Enums\ComplianceStatus::from($assessment['status'])->label()" />
                </div>
                <p class="mt-2.5 text-sm text-slate-600">{{ $assessment['satisfied_count'] }} of {{ $assessment['required_count'] }} required qualifications met &middot; {{ $assessment['assessed_on'] }}</p>
            </div>
            <p class="text-right text-3xl font-semibold tracking-tight">{{ $assessment['percentage'] === null ? '--' : $assessment['percentage'].'%' }}<span class="mt-0.5 block text-xs font-normal text-slate-500">{{ $assessment['percentage'] === null ? 'Assessment incomplete' : 'Complete' }}</span></p>
        </div>
        <p class="mt-3 text-sm text-slate-500">This is a system-calculated requirements assessment, not an independent authorization for deployment.</p>
        @if($assessment['reason'])<p class="notice notice-warning mt-4">{{ $assessment['reason'] }}</p>@endif
    </section>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">Required qualifications</h2>
        <a class="text-link" href="{{ route('training-enrollments.index') }}">Training records</a>
    </div>
    <div class="record-list">
        @forelse($assessment['items'] as $item)
            <section class="record-row">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900">{{ $item['name'] }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $item['domain'] }}
                            @if($item['requires_training']) &middot; {{ $item['training_completed'] ? 'Training completed' : 'Training completion required' }}@endif
                            @if($item['expires_at'] && ! $item['expiring_soon']) &middot; Valid until {{ $item['expires_at'] }}@endif
                        </p>
                    </div>
                    <x-status-badge :status="$item['state']" />
                </div>
                @if($item['expiring_soon'])<p class="mt-2 text-xs font-semibold text-amber-800">Expiring soon: {{ $item['expires_at'] }}</p>@endif
                @if($item['pending_count'])
                    <p class="mt-1.5 text-xs text-slate-500">
                        {{ $item['pending_count'] }} pending {{ \Illuminate\Support\Str::plural('review', $item['pending_count']) }}.
                        @if($item['document_id'] !== null)
                            An earlier valid approval remains in effect.
                        @elseif($item['has_expired_approval'])
                            The previous approval has expired; this renewal is awaiting review.
                        @elseif($item['has_approved_credential'])
                            An approved credential is not currently valid; this submission is awaiting review.
                        @else
                            No credential has been approved yet; this submission is awaiting review.
                        @endif
                    </p>
                @endif
                @if(auth()->id() === $employee->id)
                    @can('submit-credentials')
                        @if($item['requires_training'] && ! $item['training_completed'])
                            <p class="mt-2 text-sm text-slate-600">You can submit evidence while training is pending, but it cannot be approved until the required training is completed.</p>
                            <a class="text-link mt-1.5" href="{{ route('training-courses.index') }}">Find training<x-icon name="arrow" class="size-4" /></a>
                        @endif
                        @if($item['state'] !== 'satisfied' || $item['expiring_soon'])
                            <a class="text-link mt-1.5" href="{{ route('credentials.create', ['qualification' => $item['qualification_id']]) }}">{{ $item['expiring_soon'] ? 'Submit renewal' : 'Submit evidence' }}<x-icon name="arrow" class="size-4" /></a>
                        @endif
                    @endcan
                @endif
            </section>
        @empty
            <x-empty-state title="No requirements configured" message="An administrator or compliance officer must assign requirements to this job." icon="matrix" />
        @endforelse
    </div>
    <details class="mt-5 text-xs text-slate-500"><summary class="cursor-pointer font-medium text-teal-800">How readiness is assessed</summary><p class="mt-1.5 max-w-2xl">These are requirements, not awarded qualifications. Each requires approved, unexpired evidence (plus completed training when required); renewal warnings start 30 days before expiry.</p></details>
</x-layout>
