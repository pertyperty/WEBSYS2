<x-layout title="Audit details">
    @php
        $snapshotEmployees = collect($audit->snapshot['employees'] ?? []);
        $eligibleCount = $snapshotEmployees->where('status', 'eligible')->count();
        $notEligibleCount = $snapshotEmployees->where('status', 'not_eligible')->count();
        $notAssessableCount = $snapshotEmployees->where('status', 'not_assessable')->count();
    @endphp
    <a class="back-link" href="{{ route('audits.index') }}"><x-icon name="arrow-left" class="size-4" />Audits</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $audit->title }}</h1>
            <p class="page-intro">{{ $audit->snapshot['facility_name'] ?? $audit->snapshot['region_name'] }} &middot; {{ $audit->audited_on->format('M j, Y') }} &middot; {{ $audit->author->name }}</p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="metric"><p class="metric-label">Eligible</p><p class="metric-value text-emerald-700">{{ $eligibleCount }}</p></div>
        <div class="metric"><p class="metric-label">Not eligible</p><p class="metric-value text-rose-700">{{ $notEligibleCount }}</p></div>
        <div class="metric"><p class="metric-label">Not assessable</p><p class="metric-value text-amber-700">{{ $notAssessableCount }}</p></div>
        <div class="metric"><p class="metric-label">Findings</p><p class="metric-value text-slate-900">{{ $audit->findings->count() }}</p></div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            <section>
                <h2 class="mb-3 text-base font-semibold">Findings ({{ $audit->findings->count() }})</h2>
                <div class="record-list">
                    @forelse($audit->findings as $finding)
                        <a class="record-row group flex flex-wrap items-center justify-between gap-4" href="{{ route('findings.show', $finding) }}">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $finding->title }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $finding->assignee->name }} &middot; Due {{ $finding->due_on->format('M j, Y') }}</p>
                            </div>
                            <x-status-badge :status="$finding->status" />
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500">No findings assigned yet.</p>
                    @endforelse
                </div>
            </section>
            <section>
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-base font-semibold">Captured readiness ({{ $snapshotEmployees->count() }})</h2>
                    <span class="text-xs text-slate-500">Fixed snapshot at capture</span>
                </div>
                <div class="record-list">
                    @foreach($snapshotEmployees as $employee)
                        <div class="record-row flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">{{ $employee['employee_name'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $employee['facility_name'] }} &middot; {{ $employee['job_role_name'] ?? 'No job' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-xs text-slate-500">{{ $employee['satisfied_count'] }}/{{ $employee['required_count'] }}</span>
                                <x-status-badge :status="$employee['status']" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        @can('conduct-audits')
            <section class="panel self-start">
                <h2 class="text-base font-semibold">Assign a finding</h2>
                <form class="mt-4 space-y-4" method="POST" action="{{ route('audit-findings.store', $audit) }}" x-data="findingAssignmentForm(@js($managers), @js((string) old('facility_id', $audit->facility_id ?? '')), @js((string) old('assignee_id', '')))">
                    @csrf
                    <x-input name="title" label="Finding title" required maxlength="255" :value="old('title')" />
                    <div>
                        <label for="description">Description</label>
                        <textarea class="field" name="description" id="description" rows="3" required maxlength="5000">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label for="facility_id">Facility</label>
                        <select class="field" id="facility_id" name="facility_id" x-model="facilityId" @change="assigneeId = ''" required>
                            <option value="" disabled @selected(! old('facility_id', $audit->facility_id))>Select a facility</option>
                            @foreach($facilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assignee_id">Assigned manager</label>
                        <select class="field" id="assignee_id" name="assignee_id" x-model="assigneeId" :disabled="!facilityId" required>
                            <option value="" disabled>Select a manager</option>
                            <template x-for="manager in availableManagers" :key="manager.id">
                                <option :value="String(manager.id)" :selected="String(manager.id) === String(assigneeId)" x-text="manager.name"></option>
                            </template>
                        </select>
                        <p class="field-help" x-show="facilityId && !availableManagers.length" x-cloak>No active manager assigned to this facility.</p>
                    </div>
                    <x-input name="due_on" type="date" label="Due date" required :min="now('Asia/Manila')->toDateString()" :value="old('due_on')" />
                    <button class="button w-full" type="submit">Assign finding</button>
                </form>
            </section>
        @endcan
    </div>
</x-layout>
