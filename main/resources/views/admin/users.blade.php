<x-layout title="Account assignments">
    <h1 class="text-3xl font-bold">Account assignments</h1>
    <p class="mb-8 mt-3 max-w-3xl text-sm text-slate-600">Assign staff a facility and job together. Managers need a facility; directors and API auditors need a region. Global roles need no assignment. Manage organization records from the navigation. Only active regions, facilities, and jobs can receive assignments.</p>
    <div class="space-y-5">
        @foreach($users as $account)
        <section class="panel" x-data="{ expanded: false }">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><h2 class="font-semibold">{{ $account->name }}</h2><p class="break-all text-sm text-slate-500">{{ $account->email }} · {{ $account->role->label() }} · {{ $account->is_active ? 'Active' : 'Inactive' }}</p></div>
                <button type="button" class="text-sm font-semibold text-teal-800" @click="expanded = !expanded" :aria-expanded="expanded" aria-controls="account-{{ $account->id }}">Edit assignment</button>
            </div>
            <form id="account-{{ $account->id }}" method="POST" action="{{ route('admin.users.assignment', $account) }}" class="mt-5 grid gap-4 sm:grid-cols-2" x-show="expanded" x-cloak>
                @csrf @method('PATCH')
                <div><label for="role-{{ $account->id }}" class="text-sm font-medium">Role</label><select id="role-{{ $account->id }}" name="role" class="field">@foreach($roles as $role)<option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>@endforeach</select></div>
                <div><label for="active-{{ $account->id }}" class="text-sm font-medium">Account access</label><select id="active-{{ $account->id }}" name="is_active" class="field"><option value="1" @selected($account->is_active)>Active</option><option value="0" @selected(! $account->is_active)>Inactive</option></select></div>
                <div><label for="facility-{{ $account->id }}" class="text-sm font-medium">Facility</label><select id="facility-{{ $account->id }}" name="facility_id" class="field"><option value="">Unassigned</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected($account->facility_id === $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
                <div><label for="job-{{ $account->id }}" class="text-sm font-medium">Job</label><select id="job-{{ $account->id }}" name="job_role_id" class="field"><option value="">Unassigned</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected($account->job_role_id === $job->id)>{{ $job->name }}</option>@endforeach</select></div>
                <div><label for="region-{{ $account->id }}" class="text-sm font-medium">Region</label><select id="region-{{ $account->id }}" name="region_id" class="field"><option value="">Unassigned</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected($account->region_id === $region->id)>{{ $region->name }}</option>@endforeach</select></div>
                <div class="flex items-end"><button class="button" type="submit">Save assignment</button></div>
            </form>
        </section>
        @endforeach
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layout>
