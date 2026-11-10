<x-layout title="Accounts & access">
    <div class="page-heading">
        <div><h1 class="text-3xl font-bold">Accounts & access</h1><p class="page-intro">Manage who can sign in and where they work.</p></div>
    </div>
    <form method="GET" action="{{ route('admin.users') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-full sm:max-w-sm"><label for="account-search">Search accounts</label><input id="account-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" placeholder="Name or email"></div>
        <button class="button-secondary" type="submit"><x-icon name="search" />Search</button>
        @if(request('search'))<a class="text-link" href="{{ route('admin.users') }}">Clear search</a>@endif
    </form>
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
            @endphp
            <section class="record-row" x-data="assignmentForm(@js($initial))">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="avatar hidden sm:inline-flex">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                        <div class="min-w-0"><h2 id="account-title-{{ $account->id }}" class="font-semibold">{{ $account->name }}</h2><p class="break-all text-xs text-slate-500">{{ $account->email }}</p><p class="mt-1 text-xs text-slate-500">{{ $account->role->label() }}</p></div>
                    </div>
                    <div class="flex items-center gap-4">
                        <x-status-badge :status="$account->is_active ? 'active' : 'inactive'" />
                        <button type="button" class="button-secondary" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="account-{{ $account->id }}" :aria-label="(expanded ? 'Close assignment for ' : 'Edit assignment for ') + @js($account->name)">Edit assignment</button>
                    </div>
                </div>
                <form id="account-{{ $account->id }}" aria-labelledby="account-title-{{ $account->id }}" method="POST" action="{{ route('admin.users.assignment', $account) }}" class="mt-6 grid max-w-3xl gap-5 sm:grid-cols-2" x-show="expanded" x-cloak>
                    @csrf @method('PATCH')
                    <input type="hidden" name="_account" value="{{ $account->id }}">
                    <div><label for="role-{{ $account->id }}">Role</label><select id="role-{{ $account->id }}" name="role" class="field" x-model="role" @change="clearUnusedAssignments()">@foreach($roles as $role)<option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>@endforeach</select></div>
                    <div><label for="active-{{ $account->id }}">Account access</label><select id="active-{{ $account->id }}" name="is_active" class="field"><option value="1" @selected($retry ? old('is_active') : $account->is_active)>Active</option><option value="0" @selected(! ($retry ? old('is_active') : $account->is_active))>Inactive</option></select></div>
                    <div class="sm:col-span-2" x-show="role === 'staff'"><label class="flex min-h-11 items-center gap-3"><input type="checkbox" class="rounded text-teal-800" x-model="assignStaff" @change="clearUnusedAssignments()">Assign a facility and job now</label><p class="field-help" x-show="!assignStaff">This staff member will wait for an assignment before accessing employee records.</p></div>
                    <div x-show="needsFacility"><label for="facility-{{ $account->id }}">Facility</label><select id="facility-{{ $account->id }}" name="facility_id" class="field" x-model="facilityId" :disabled="!needsFacility" :required="needsFacility"><option value="" disabled>Select a facility</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected($account->facility_id === $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
                    <div x-show="needsJob"><label for="job-{{ $account->id }}">Job</label><select id="job-{{ $account->id }}" name="job_role_id" class="field" x-model="jobId" :disabled="!needsJob" :required="needsJob"><option value="" disabled>Select a job</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected($account->job_role_id === $job->id)>{{ $job->name }}</option>@endforeach</select></div>
                    <div x-show="needsRegion"><label for="region-{{ $account->id }}">Region</label><select id="region-{{ $account->id }}" name="region_id" class="field" x-model="regionId" :disabled="!needsRegion" :required="needsRegion"><option value="" disabled>Select a region</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected($account->region_id === $region->id)>{{ $region->name }}</option>@endforeach</select></div>
                    <p class="field-help sm:col-span-2" x-show="['admin', 'compliance_officer'].includes(role)">This role has access across all regions and facilities.</p>
                    <div class="flex gap-3 sm:col-span-2"><button class="button" type="submit">Save assignment</button><button class="button-secondary" type="button" @click="expanded = false">Close</button></div>
                </form>
            </section>
        @empty
            <x-empty-state title="No accounts found" message="Try a different name or email address." icon="people" />
        @endforelse
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layout>
