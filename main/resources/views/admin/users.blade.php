<x-layout title="Accounts & access">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Accounts &amp; access</h1></div>
    </div>
    <x-filter-bar :action="route('admin.users')">
        <div class="w-full sm:max-w-xs"><label for="account-search">Name or email</label><input id="account-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-48"><label for="role-filter">Role</label><select class="field" id="role-filter" name="role"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
        <div class="w-full sm:w-40"><label for="active-filter">Status</label><select class="field" id="active-filter" name="active"><option value="">All statuses</option><option value="active" @selected(request('active') === 'active')>Active</option><option value="inactive" @selected(request('active') === 'inactive')>Inactive</option></select></div>
        <div class="w-full sm:w-44"><label for="assignment-filter">Assignment</label><select class="field" id="assignment-filter" name="assignment"><option value="">All assignments</option><option value="unassigned" @selected(request('assignment') === 'unassigned')>Unassigned staff</option></select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('role') || request()->filled('active') || request()->filled('assignment');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <x-result-summary :paginator="$users" noun="account" :filtered="$filtered" />
        <div class="record-list">
            @forelse($users as $account)
                @php
                    $retry = (string) old('_account') === (string) $account->id;
                    $initial = [
                        'expanded' => $retry,
                        'role' => $retry ? old('role') : $account->role->value,
                        'assignStaff' => (bool) ($retry ? old('facility_id') : $account->facility_id),
                        'facilityId' => (string) ($retry ? old('facility_id') : $account->facility_id),
                        'jobId' => (string) ($retry ? old('job_role_id') : $account->job_role_id),
                        'regionId' => (string) ($retry ? old('region_id') : $account->region_id),
                    ];
                    $unassignedStaff = $account->role === \App\Enums\UserRole::Staff && ! $account->isAssignedStaff();
                    $scopeSummary = match ($account->role) {
                        \App\Enums\UserRole::Admin, \App\Enums\UserRole::ComplianceOfficer => 'All regions',
                        \App\Enums\UserRole::RegionalDirector, \App\Enums\UserRole::Auditor => $account->region?->name ?? 'No region',
                        \App\Enums\UserRole::FacilityManager => $account->facility?->name ?? 'No facility',
                        \App\Enums\UserRole::Staff => $account->isAssignedStaff()
                            ? $account->facility?->name.' · '.$account->jobRole?->name
                            : null,
                    };
                @endphp
                <section class="record-row" x-data="assignmentForm(@js($initial))">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="avatar hidden sm:inline-flex">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <h2 id="account-title-{{ $account->id }}" class="font-semibold text-slate-900">{{ $account->name }}</h2>
                                <p class="break-all text-xs text-slate-500">{{ $account->email }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $account->role->label() }}@if($scopeSummary) &middot; {{ $scopeSummary }}@endif</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            @if($unassignedStaff)<x-status-badge status="unassigned" />@endif
                            <x-status-badge :status="$account->is_active ? 'active' : 'inactive'" />
                            <button type="button" class="button-secondary" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="account-{{ $account->id }}" :aria-label="(expanded ? 'Close assignment for ' : 'Edit assignment for ') + @js($account->name)" x-text="expanded ? 'Close' : 'Edit'">Edit</button>
                        </div>
                    </div>
                    <form id="account-{{ $account->id }}" aria-labelledby="account-title-{{ $account->id }}" method="POST" action="{{ route('admin.users.assignment', $account) }}" class="mt-4 grid max-w-3xl gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200/70 sm:grid-cols-2" x-show="expanded" x-cloak>
                        @csrf @method('PATCH')
                        <input type="hidden" name="_account" value="{{ $account->id }}">
                        <div><label for="role-{{ $account->id }}">Role</label><select id="role-{{ $account->id }}" name="role" class="field" x-model="role" @change="clearUnusedAssignments()">@foreach($roles as $role)<option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>@endforeach</select></div>
                        <div><label for="active-{{ $account->id }}">Account status</label><select id="active-{{ $account->id }}" name="is_active" class="field"><option value="1" @selected($retry ? old('is_active') : $account->is_active)>Active</option><option value="0" @selected(! ($retry ? old('is_active') : $account->is_active))>Inactive</option></select></div>
                        <div class="sm:col-span-2" x-show="role === 'staff'"><label class="flex min-h-10 items-center gap-2.5"><input type="checkbox" class="rounded border-slate-300 text-teal-800" x-model="assignStaff" @change="clearUnusedAssignments()">Assign facility and job</label></div>
                        <div x-show="needsFacility"><label for="facility-{{ $account->id }}">Facility</label><select id="facility-{{ $account->id }}" name="facility_id" class="field" x-model="facilityId" :disabled="!needsFacility" :required="needsFacility"><option value="" disabled>Select a facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected($account->facility_id === $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
                        <div x-show="needsJob"><label for="job-{{ $account->id }}">Job</label><select id="job-{{ $account->id }}" name="job_role_id" class="field" x-model="jobId" :disabled="!needsJob" :required="needsJob"><option value="" disabled>Select a job</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected($account->job_role_id === $job->id)>{{ $job->name }}</option>@endforeach</select></div>
                        <div x-show="needsRegion"><label for="region-{{ $account->id }}">Region</label><select id="region-{{ $account->id }}" name="region_id" class="field" x-model="regionId" :disabled="!needsRegion" :required="needsRegion"><option value="" disabled>Select a region</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected($account->region_id === $region->id)>{{ $region->name }}</option>@endforeach</select></div>
                        <p class="field-help sm:col-span-2" x-show="['admin', 'compliance_officer'].includes(role)">Has access across all regions and facilities.</p>
                        <div class="flex flex-wrap gap-3 sm:col-span-2"><button class="button" type="submit">Save</button><button class="button-secondary" type="button" @click="expanded = false">Cancel</button></div>
                    </form>
                </section>
            @empty
                <x-empty-state :title="$filtered ? 'No accounts match your filters' : 'No accounts found'" :message="$filtered ? 'Try another search or filter.' : 'Accounts will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('admin.users') }}" @click.prevent="reset()">Clear filters</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </div>
</div>
</x-layout>
