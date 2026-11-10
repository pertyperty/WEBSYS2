<x-layout title="Overview">
    @php($staff = auth()->user()->role === \App\Enums\UserRole::Staff)
    <div class="page-heading">
        <div><p class="mb-1 text-xs font-semibold uppercase tracking-widest text-teal-800">{{ $staff ? 'Your workspace' : 'Workforce overview' }}</p><h1 class="text-3xl font-bold">{{ $staff ? 'Ready for your next shift?' : 'Readiness at a glance' }}</h1><p class="page-intro">{{ $assessedOn }} <span aria-hidden="true">&middot;</span> Asia/Manila</p></div>
        @can('submit-credentials')<a class="button" href="{{ route('credentials.create') }}"><x-icon name="document" class="size-4" />Submit evidence</a>@endcan
        @can('review-credentials')<a class="button" href="{{ route('credentials.index', ['status' => 'pending']) }}">Review pending documents<x-icon name="arrow" class="size-4" /></a>@endcan
    </div>
    @if($staff && $ownAssessment)
        <section class="mb-7 flex flex-wrap items-center justify-between gap-5 rounded-2xl bg-teal-900 p-6 text-white sm:p-8">
            <div><p class="text-sm text-teal-100">Your current readiness</p><h2 class="mt-2 text-2xl font-semibold">{{ \App\Enums\ComplianceStatus::from($ownAssessment['status'])->label() }}</h2><p class="mt-2 text-sm text-teal-100">{{ $ownAssessment['satisfied_count'] }} of {{ $ownAssessment['required_count'] }} required qualifications satisfied.</p></div>
            <a class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-teal-900" href="{{ route('employees.show', auth()->user()) }}">See what to do next<x-icon name="arrow" class="size-4" /></a>
        </section>
    @else
        <div class="mb-7 grid grid-cols-2 gap-5 sm:grid-cols-4">
            @foreach(['eligible' => ['Eligible', 'text-emerald-700'], 'not_eligible' => ['Not eligible', 'text-rose-700'], 'not_assessable' => ['Not assessable', 'text-amber-700'], 'employees' => ['People in scope', 'text-slate-900']] as $key => [$label, $color])
                <section class="metric"><h2 class="metric-label">{{ $label }}</h2><p class="metric-value {{ $color }}">{{ $totals[$key] }}</p></section>
            @endforeach
        </div>
    @endif
    <div class="grid items-start gap-7 xl:grid-cols-[1.4fr_1fr]">
        <section class="panel">
            <div class="mb-5 flex items-center justify-between gap-4"><h2 class="text-lg font-semibold">{{ $staff ? 'Your next steps' : 'Needs attention' }}</h2><span class="text-xs text-slate-500">Current records</span></div>
            @can('view-credentials')
                <a class="action-link" href="{{ route('credentials.index', ['status' => 'pending']) }}"><span><span class="block text-sm font-semibold">{{ $staff ? 'Track your submissions' : 'Pending document reviews' }}</span><span class="block text-xs text-slate-500">{{ $totals['pending_documents'] }} awaiting a decision</span></span><x-icon name="arrow" class="size-4 text-teal-800" /></a>
            @endcan
            @can('view-training')
                <a class="action-link" href="{{ route('training-enrollments.index') }}"><span><span class="block text-sm font-semibold">Training in progress</span><span class="block text-xs text-slate-500">{{ $totals['enrolled'] }} enrolled &middot; {{ $totals['completed'] }} completed</span></span><x-icon name="arrow" class="size-4 text-teal-800" /></a>
            @endcan
            @can('view-employees')
                <a class="action-link" href="{{ $staff ? route('employees.show', auth()->user()) : route('qualification-matrix') }}"><span><span class="block text-sm font-semibold">{{ $staff ? 'Check required qualifications' : 'Check qualification gaps' }}</span><span class="block text-xs text-slate-500">{{ $totals['missing'] }} missing &middot; {{ $totals['expired'] }} expired &middot; {{ $totals['expiring_soon'] }} expiring soon</span></span><x-icon name="arrow" class="size-4 text-teal-800" /></a>
            @endcan
            @can('manage-users')<a class="action-link" href="{{ route('admin.users') }}"><span><span class="block text-sm font-semibold">Manage accounts & assignments</span><span class="block text-xs text-slate-500">Keep team access up to date</span></span><x-icon name="arrow" class="size-4 text-teal-800" /></a>@endcan
        </section>
        <div class="space-y-6">
            @if(! $staff)
                <section class="panel">
                    <h2 class="text-base font-semibold">Eligibility rate</h2>
                    <p class="mt-3 text-4xl font-semibold tracking-tight text-teal-800">{{ $totals['eligible_rate'] === null ? 'Not assessable' : $totals['eligible_rate'].'%' }}</p>
                    <p class="mt-3 text-xs text-slate-500">{{ $totals['eligible'] }} eligible / {{ $totals['assessable'] }} assessable staff</p>
                    @if($totals['eligible_rate'] !== null)<progress class="mt-4 h-2 w-full accent-teal-700" value="{{ $totals['eligible'] }}" max="{{ $totals['assessable'] }}" aria-label="Eligible staff out of assessable staff"></progress>@endif
                    <details class="mt-4 text-xs text-slate-500"><summary class="cursor-pointer font-medium text-teal-800">How is this calculated?</summary><p class="mt-2">Includes active staff with complete assignments and configured requirements. {{ $totals['not_assessable'] }} not-assessable accounts are excluded. Gaps count required qualifications for assessable staff only; document and training totals include all staff in scope, including optional submissions.</p></details>
                </section>
            @endif
            <section class="px-1">
                <h2 class="text-sm font-semibold">{{ $staff ? 'Your assignment' : 'Your access' }}</h2>
                <dl class="mt-3 space-y-2 text-sm text-slate-600"><div><dt class="sr-only">Role</dt><dd>{{ auth()->user()->role->label() }}</dd></div>@if(auth()->user()->facility)<div><dt class="sr-only">Facility</dt><dd>{{ auth()->user()->facility->name }}</dd></div>@endif @if(auth()->user()->jobRole)<div><dt class="sr-only">Job</dt><dd>{{ auth()->user()->jobRole->name }}</dd></div>@endif</dl>
            </section>
        </div>
    </div>
</x-layout>
