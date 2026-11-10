<x-layout title="Audit details">
    <a class="text-link" href="{{ route('audits.index') }}">&larr; Audits</a>
    <div class="page-heading mt-5">
        <div>
            <h1 class="text-3xl font-bold">{{ $audit->title }}</h1>
            <p class="page-intro">{{ $audit->snapshot['facility_name'] ?? $audit->snapshot['region_name'] }} &middot; {{ $audit->audited_on->format('M j, Y') }}</p>
            <p class="mt-2 text-sm text-slate-500">Captured by {{ $audit->author->name }}</p>
        </div>
    </div>
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-8">
            <section>
                <h2 class="text-lg font-semibold">Readiness at capture</h2>
                <p class="mt-1 text-sm text-slate-500">A fixed record. Check People for current results.</p>
                <div class="record-list mt-4">
                    @foreach($audit->snapshot['employees'] as $employee)
                        <div class="record-row flex flex-wrap items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $employee['employee_name'] }}</p>
                                <p class="text-sm text-slate-500">{{ $employee['facility_name'] }} &middot; {{ $employee['job_role_name'] ?? 'No job assigned' }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <x-status-badge :status="$employee['status']" />
                                <p class="mt-1 text-xs text-slate-500">{{ $employee['satisfied_count'] }} / {{ $employee['required_count'] }} requirements</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            <section>
                <h2 class="text-lg font-semibold">Findings</h2>
                <div class="record-list mt-4">
                    @forelse($audit->findings as $finding)
                        <a class="record-row flex flex-wrap items-center justify-between gap-4" href="{{ route('findings.show', $finding) }}">
                            <div>
                                <p class="font-medium">{{ $finding->title }}</p>
                                <p class="text-sm text-slate-500">{{ $finding->assignee->name }} &middot; Due {{ $finding->due_on->format('M j, Y') }}</p>
                            </div>
                            <x-status-badge :status="$finding->status" />
                        </a>
                    @empty
                        <p class="py-5 text-sm text-slate-500">No findings recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>
        @can('conduct-audits')
            <section class="panel self-start">
                <h2 class="text-lg font-semibold">Assign a finding</h2>
                <form class="mt-5 space-y-4" method="POST" action="{{ route('audit-findings.store', $audit) }}" x-data="findingAssignmentForm(@js($managers), @js((string) old('facility_id', $audit->facility_id ?? '')), @js((string) old('assignee_id', '')))">
                    @csrf
                    <x-input name="title" label="Finding title" required maxlength="255" :value="old('title')" />
                    <div>
                        <label class="block text-sm font-medium" for="description">What needs correcting?</label>
                        <textarea class="field" name="description" id="description" rows="3" required maxlength="5000">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="facility_id">Facility</label>
                        <select class="field" id="facility_id" name="facility_id" x-model="facilityId" @change="assigneeId = ''" required>
                            <option value="" disabled>Select a facility</option>
                            @foreach($facilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="assignee_id">Assigned manager</label>
                        <select class="field" id="assignee_id" name="assignee_id" x-model="assigneeId" :disabled="!facilityId" required>
                            <option value="" disabled>Select a manager</option>
                            <template x-for="manager in availableManagers" :key="manager.id">
                                <option :value="String(manager.id)" x-text="manager.name">
                                </option>
                            </template>
                        </select>
                        <p class="field-help" x-show="facilityId && !availableManagers.length" x-cloak>Ask an administrator to assign a manager to this facility.</p>
                    </div>
                    <x-input name="due_on" type="date" label="Due date" required :min="now('Asia/Manila')->toDateString()" :value="old('due_on')" />
                    <button class="button w-full" type="submit">Assign finding</button>
                </form>
            </section>
        @endcan
    </div>
</x-layout>
