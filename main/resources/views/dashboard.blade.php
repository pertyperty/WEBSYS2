<x-layout title="Overview">
    @php($staff = auth()->user()->role === \App\Enums\UserRole::Staff)
    <div class="page-heading">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $staff ? 'Overview' : 'Workforce readiness' }}</h1>
            <p class="page-intro">As of {{ \Carbon\CarbonImmutable::parse($assessedOn)->format('M j, Y') }} (Asia/Manila)</p>
        </div>
        @can('submit-credentials')<a class="button" href="{{ route('credentials.create') }}"><x-icon name="plus" class="size-4" />Submit evidence</a>@endcan
        @can('review-credentials')<a class="button" href="{{ route('credentials.index', ['status' => 'pending']) }}">Review pending ({{ $totals['pending_documents'] }})<x-icon name="arrow" class="size-4" /></a>@endcan
    </div>

    @if($staff && $ownAssessment)
        @php($statusEnum = \App\Enums\ComplianceStatus::from($ownAssessment['status']))
        <section class="mb-6 flex flex-wrap items-center justify-between gap-5 rounded-2xl bg-teal-900 p-6 text-white shadow-xs sm:p-7">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-teal-50">{{ $statusEnum->dutyLabel() }}</span>
                </div>
                <h2 class="mt-2.5 text-2xl font-bold">Compliance assessment</h2>
                <p class="mt-1 text-sm text-teal-100">{{ $ownAssessment['satisfied_count'] }} of {{ $ownAssessment['required_count'] }} required qualifications met</p>
                <p class="mt-2 text-xs text-teal-100">This system-calculated assessment does not independently authorize deployment.</p>
            </div>
            <a class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-teal-900 transition-colors hover:bg-teal-50" href="{{ route('employees.show', auth()->user()) }}">View readiness<x-icon name="arrow" class="size-4" /></a>
        </section>
    @else
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach(['eligible' => ['Requirements met', 'text-emerald-700'], 'not_eligible' => ['Requirements not met', 'text-rose-700'], 'not_assessable' => ['Assessment incomplete', 'text-amber-700'], 'employees' => ['People in scope', 'text-slate-900']] as $key => [$label, $color])
                <section class="metric">
                    <h2 class="metric-label">{{ $label }}</h2>
                    <p class="metric-value {{ $color }}">{{ number_format($totals[$key]) }}</p>
                </section>
            @endforeach
        </div>
        <p class="mb-6 text-sm text-slate-500">These counts are system-calculated from assigned requirements; they do not independently authorize deployment.</p>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[1.45fr_1fr]">
        <section class="panel">
            <h2 class="mb-3 text-base font-semibold">{{ $staff ? 'Your next steps' : 'Action queue' }}</h2>
            <div class="divide-y divide-slate-100">
                @can('view-credentials')
                    <a class="action-link" href="{{ route('credentials.index', ['status' => 'pending']) }}">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $staff ? 'Pending submissions' : 'Pending document reviews' }}</span>
                            <span class="block text-xs text-slate-500">{{ $totals['pending_documents'] }} awaiting review</span>
                        </span>
                        <x-icon name="arrow" class="size-4 text-teal-800" />
                    </a>
                @endcan
                @can('view-training')
                    <a class="action-link" href="{{ route('training-enrollments.index') }}">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Training records</span>
                            <span class="block text-xs text-slate-500">{{ $totals['enrolled'] }} enrolled &middot; {{ $totals['completed'] }} completed</span>
                        </span>
                        <x-icon name="arrow" class="size-4 text-teal-800" />
                    </a>
                @endcan
                @can('view-employees')
                    <a class="action-link" href="{{ $staff ? route('employees.show', auth()->user()) : route('qualification-matrix') }}">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $staff ? 'Required qualifications' : 'Qualification gaps' }}</span>
                            <span class="block text-xs text-slate-500">{{ $totals['missing'] }} missing &middot; {{ $totals['expired'] }} expired &middot; {{ $totals['expiring_soon'] }} expiring soon</span>
                        </span>
                        <x-icon name="arrow" class="size-4 text-teal-800" />
                    </a>
                @endcan
                @can('view-audits')
                    <a class="action-link" href="{{ route('findings.index') }}">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Corrective work</span>
                            <span class="block text-xs text-slate-500">{{ $openFindingsCount }} open {{ \Illuminate\Support\Str::plural('finding', $openFindingsCount) }}</span>
                        </span>
                        <x-icon name="arrow" class="size-4 text-teal-800" />
                    </a>
                @endcan
                @can('manage-users')
                    <a class="action-link" href="{{ $unassignedStaffCount ? route('admin.users', ['assignment' => 'unassigned']) : route('admin.users') }}">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">Accounts &amp; access</span>
                            <span class="block text-xs text-slate-500">{{ $unassignedStaffCount ? $unassignedStaffCount.' unassigned staff waiting' : 'Manage roles and facility scope' }}</span>
                        </span>
                        <x-icon name="arrow" class="size-4 text-teal-800" />
                    </a>
                @endcan
            </div>
        </section>

        <div class="space-y-5">
            @if(! $staff)
                <section class="panel">
                    <h2 class="text-base font-semibold">Requirements met rate</h2>
                    <p class="mt-2 text-4xl font-semibold tracking-tight text-teal-800">{{ $totals['eligible_rate'] === null ? '--' : $totals['eligible_rate'].'%' }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ $totals['eligible'] }} of {{ $totals['assessable'] }} assessable staff meet requirements</p>
                    @if($totals['eligible_rate'] !== null)<progress class="mt-3.5 h-2 w-full accent-teal-700" value="{{ $totals['eligible'] }}" max="{{ $totals['assessable'] }}" aria-label="Staff meeting requirements out of assessable staff"></progress>@endif
                    <details class="mt-3.5 text-xs text-slate-500"><summary class="cursor-pointer font-medium text-teal-800">Calculation notes</summary><p class="mt-1.5">Counts active staff with configured job requirements ({{ $totals['not_assessable'] }} excluded).</p></details>
                </section>
            @endif
            <section class="panel">
                <h2 class="text-sm font-semibold text-slate-900">{{ $staff ? 'Your assignment' : 'Your access' }}</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Role</dt><dd class="font-medium text-slate-900">{{ auth()->user()->role->label() }}</dd></div>
                    @if(auth()->user()->facility)<div class="flex justify-between gap-3"><dt class="text-slate-500">Facility</dt><dd class="text-right font-medium text-slate-900">{{ auth()->user()->facility->name }}</dd></div>@endif
                    @if(auth()->user()->jobRole)<div class="flex justify-between gap-3"><dt class="text-slate-500">Job</dt><dd class="text-right font-medium text-slate-900">{{ auth()->user()->jobRole->name }}</dd></div>@endif
                    @if(auth()->user()->region)<div class="flex justify-between gap-3"><dt class="text-slate-500">Region</dt><dd class="text-right font-medium text-slate-900">{{ auth()->user()->region->name }}</dd></div>@endif
                </dl>
            </section>
        </div>
    </div>
</x-layout>
