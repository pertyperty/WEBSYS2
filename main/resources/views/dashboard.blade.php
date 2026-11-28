<x-layout title="Overview">
    @php($staff = auth()->user()->role === \App\Enums\UserRole::Staff)
    @php($unmetRate = $totals['eligible_rate'] === null ? 0 : 100 - $totals['eligible_rate'])

    <div class="dashboard-heading">
        <div class="min-w-0">
            <h1 class="dashboard-title">{{ $staff ? 'Overview' : 'Workforce readiness' }}</h1>
            <p class="dashboard-subtitle">{{ $staff ? 'Your requirements, training, and outstanding work.' : 'Readiness and follow-up work for staff in your scope.' }}</p>
            <p class="dashboard-as-of">
                <x-icon name="calendar" class="size-4" />
                <span>As of <time datetime="{{ $assessedOn }}">{{ \Carbon\CarbonImmutable::parse($assessedOn)->format('M j, Y') }}</time> · Asia/Manila</span>
            </p>
        </div>
        <div class="dashboard-actions">
            @can('submit-credentials')
                <a class="button" href="{{ route('credentials.create') }}"><x-icon name="plus" class="size-4" />Submit evidence</a>
            @endcan
            @can('review-credentials')
                <a class="button-secondary" href="{{ route('credentials.index', ['status' => 'pending']) }}">
                    <x-icon name="document" class="size-4" />
                    Review pending ({{ $totals['pending_documents'] }})
                    <x-icon name="arrow" class="size-4" />
                </a>
            @endcan
        </div>
    </div>

    @if($staff && $ownAssessment)
        @php($statusEnum = \App\Enums\ComplianceStatus::from($ownAssessment['status']))
        <section class="dashboard-own-assessment">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-teal-50">{{ $statusEnum->dutyLabel() }}</span>
                </div>
                <h2 class="mt-2 text-xl font-bold sm:text-2xl">Compliance assessment</h2>
                <p class="mt-1 text-sm text-teal-100">{{ $ownAssessment['satisfied_count'] }} of {{ $ownAssessment['required_count'] }} required qualifications met</p>
                <p class="mt-2 text-xs text-teal-100">This system-calculated assessment does not independently authorize deployment.</p>
            </div>
            <a class="dashboard-own-assessment-link" href="{{ route('employees.show', auth()->user()) }}">View readiness<x-icon name="arrow" class="size-4" /></a>
        </section>
    @else
        <div class="dashboard-metrics">
            @foreach([
                'eligible' => ['Requirements met', 'text-emerald-800', 'check-circle', 'metric-icon-positive'],
                'not_eligible' => ['Requirements not met', 'text-rose-800', 'x-circle', 'metric-icon-negative'],
                'not_assessable' => ['Assessment incomplete', 'text-amber-800', 'clock', 'metric-icon-warning'],
                'employees' => ['People in scope', 'text-slate-950', 'people', 'metric-icon-neutral'],
            ] as $key => [$label, $color, $icon, $iconColor])
                <section class="metric dashboard-metric">
                    <div class="metric-heading">
                        <h2 class="metric-label">{{ $label }}</h2>
                        <span class="metric-icon {{ $iconColor }}"><x-icon :name="$icon" class="size-5" /></span>
                    </div>
                    <p class="metric-value {{ $color }}">{{ number_format($totals[$key]) }}</p>
                </section>
            @endforeach
        </div>
        <p class="dashboard-disclaimer">These counts are system-calculated from assigned requirements; they do not independently authorize deployment.</p>
    @endif

    <div class="dashboard-grid">
        <section class="panel dashboard-panel">
            <div class="dashboard-panel-heading">
                <div>
                    <h2 class="dashboard-panel-title">{{ $staff ? 'Your next steps' : 'Action queue' }}</h2>
                    <p class="dashboard-panel-caption">{{ $staff ? 'Your current training, evidence, and readiness follow-ups.' : 'Current work across the staff in your scope.' }}</p>
                </div>
            </div>
            <div class="dashboard-queue">
                @can('view-credentials')
                    <a class="action-link" href="{{ route('credentials.index', ['status' => 'pending']) }}">
                        <span class="queue-icon queue-icon-warning"><x-icon name="document" class="size-5" /></span>
                        <span class="queue-copy">
                            <span class="queue-title">
                                @if($staff)
                                    Pending submissions
                                @elseif(auth()->user()->can('review-credentials'))
                                    Pending document reviews
                                @else
                                    Pending submissions awaiting Officer review
                                @endif
                            </span>
                            <span class="queue-detail">{{ number_format($totals['pending_documents']) }} {{ ! $staff && ! auth()->user()->can('review-credentials') ? 'awaiting Officer review' : 'awaiting review' }}</span>
                        </span>
                        <x-icon name="arrow" class="queue-arrow" />
                    </a>
                @endcan
                @can('view-training')
                    <a class="action-link" href="{{ route('training-enrollments.index') }}">
                        <span class="queue-icon queue-icon-neutral"><x-icon name="training" class="size-5" /></span>
                        <span class="queue-copy">
                            <span class="queue-title">Training records</span>
                            <span class="queue-detail">{{ number_format($totals['completed']) }} completed</span>
                        </span>
                        <span class="queue-trailing">
                            <span class="queue-count queue-count-neutral">{{ number_format($totals['enrolled']) }} enrolled</span>
                            <x-icon name="arrow" class="queue-arrow" />
                        </span>
                    </a>
                @endcan
                @can('view-employees')
                    <a class="action-link" href="{{ $staff ? route('employees.show', auth()->user()) : route('qualification-matrix') }}">
                        <span class="queue-icon queue-icon-danger"><x-icon name="matrix" class="size-5" /></span>
                        <span class="queue-copy">
                            <span class="queue-title">{{ $staff ? 'Required qualifications' : 'Qualification gaps' }}</span>
                            <span class="queue-chips">
                                <span class="queue-chip queue-chip-danger">{{ number_format($totals['missing']) }} missing</span>
                                <span class="queue-chip queue-chip-danger">{{ number_format($totals['expired']) }} expired</span>
                                <span class="queue-chip queue-chip-warning">{{ number_format($totals['expiring_soon']) }} expiring soon</span>
                            </span>
                        </span>
                        <x-icon name="arrow" class="queue-arrow" />
                    </a>
                @endcan
                @can('view-audits')
                    <a class="action-link" href="{{ route('findings.index') }}">
                        <span class="queue-icon queue-icon-warning"><x-icon name="findings" class="size-5" /></span>
                        <span class="queue-copy">
                            <span class="queue-title">Corrective work</span>
                            <span class="queue-detail">Open findings</span>
                        </span>
                        <span class="queue-trailing">
                            <span class="queue-count {{ $openFindingsCount ? 'queue-count-warning' : 'queue-count-positive' }}">{{ number_format($openFindingsCount) }}</span>
                            <x-icon name="arrow" class="queue-arrow" />
                        </span>
                    </a>
                @endcan
                @can('manage-users')
                    <a class="action-link" href="{{ $unassignedStaffCount ? route('admin.users', ['assignment' => 'unassigned']) : route('admin.users') }}">
                        <span class="queue-icon queue-icon-neutral"><x-icon name="people" class="size-5" /></span>
                        <span class="queue-copy">
                            <span class="queue-title">Accounts &amp; access</span>
                            <span class="queue-detail">{{ $unassignedStaffCount ? 'Unassigned staff waiting' : 'Manage roles and facility scope' }}</span>
                        </span>
                        <span class="queue-trailing">
                            @if($unassignedStaffCount)
                                <span class="queue-count queue-count-warning">{{ number_format($unassignedStaffCount) }}</span>
                            @endif
                            <x-icon name="arrow" class="queue-arrow" />
                        </span>
                    </a>
                @endcan
            </div>
        </section>

        <div class="dashboard-aside">
            @if(! $staff)
                <section class="panel readiness-panel" aria-labelledby="readiness-title">
                    <div class="readiness-heading">
                        <div>
                            <h2 id="readiness-title" class="dashboard-panel-title">Requirements met rate</h2>
                            <p class="readiness-summary">{{ $totals['eligible'] }} of {{ $totals['assessable'] }} assessable staff meet requirements</p>
                        </div>
                        <p class="readiness-value">{{ $totals['eligible_rate'] === null ? '--' : $totals['eligible_rate'].'%' }}</p>
                    </div>

                    @if($totals['eligible_rate'] !== null)
                        <div class="readiness-bar" role="img" aria-label="Requirements met: {{ $totals['eligible'] }}. Requirements not met: {{ $totals['not_eligible'] }}." >
                            <span class="readiness-bar-met" style="width: {{ number_format($totals['eligible_rate'], 2, '.', '') }}%"></span>
                            <span class="readiness-bar-unmet" style="width: {{ number_format($unmetRate, 2, '.', '') }}%"></span>
                        </div>
                    @else
                        <p class="readiness-empty">No assessable staff are in scope yet. Active assignments and configured requirements are needed to calculate a rate.</p>
                    @endif

                    <div class="readiness-legend">
                        <span class="readiness-legend-item"><span class="readiness-legend-dot readiness-legend-dot-met"></span>Requirements met <strong>{{ number_format($totals['eligible']) }}</strong></span>
                        <span class="readiness-legend-item"><span class="readiness-legend-dot readiness-legend-dot-unmet"></span>Requirements not met <strong>{{ number_format($totals['not_eligible']) }}</strong></span>
                    </div>
                    <p class="readiness-excluded">{{ number_format($totals['not_assessable']) }} excluded: account, assignment, or requirement setup is not assessable.</p>
                    <details class="readiness-details">
                        <summary>Calculation notes</summary>
                        <p>Rate = staff meeting requirements divided by assessable staff. Inactive accounts, incomplete or inactive assignments, and missing or inactive requirement configuration are excluded—not counted as meeting requirements.</p>
                    </details>
                </section>
            @endif

            <section class="panel access-panel">
                <div class="access-heading">
                    <span class="access-icon"><x-icon name="user" class="size-4" /></span>
                    <h2 class="dashboard-panel-title">{{ $staff ? 'Your assignment' : 'Your access' }}</h2>
                </div>
                <dl class="access-list">
                    <div><dt>Role</dt><dd>{{ auth()->user()->role->label() }}</dd></div>
                    @if(auth()->user()->facility)<div><dt>Facility</dt><dd>{{ auth()->user()->facility->name }}</dd></div>@endif
                    @if(auth()->user()->jobRole)<div><dt>Job</dt><dd>{{ auth()->user()->jobRole->name }}</dd></div>@endif
                    @if(auth()->user()->region)<div><dt>Region</dt><dd>{{ auth()->user()->region->name }}</dd></div>@endif
                </dl>
            </section>
        </div>
    </div>
</x-layout>
