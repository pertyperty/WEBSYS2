<x-layout title="Qualification matrix">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <h1 class="text-3xl font-bold">Qualification matrix</h1>
    <p class="mb-6 mt-3 text-sm text-slate-600">Compare requirements across your team. N/A means the qualification is not required for that job.</p>
    <x-filter-bar :action="route('qualification-matrix')">
        <div class="w-full sm:max-w-sm"><label for="matrix-search">Employee name</label><input id="matrix-search" name="search" type="search" class="field" value="{{ request('search') }}" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-64"><label for="matrix-facility">Facility</label><select id="matrix-facility" name="facility_id" class="field"><option value="">All assigned facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
        <div class="w-full sm:w-56"><label for="matrix-job">Job role</label><select id="matrix-job" name="job_role_id" class="field"><option value="">All assigned jobs</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected((string) request('job_role_id') === (string) $job->id)>{{ $job->name }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('facility_id') || request()->filled('job_role_id');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Matrix results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    <x-result-summary :paginator="$employees" noun="employee" :filtered="$filtered" />
    @if($employees->isNotEmpty())<p class="mb-3 text-xs text-slate-500">Columns show the qualifications required by employees on this page. Open an employee's name for details.</p>@endif
    <div class="panel overflow-x-auto" role="region" aria-label="Scrollable qualification matrix" tabindex="0">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Scoped employee compliance by required qualification</caption>
            <thead><tr class="border-b border-slate-200"><th scope="col" class="sticky left-0 bg-white p-3">Employee / overall status</th>@foreach($qualifications as $qualification)<th scope="col" class="p-3">{{ $qualification['name'] }}<span class="block font-normal text-slate-500">{{ $qualification['domain'] }}</span></th>@endforeach</tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    @php
                        $result = $assessments[$employee->id];
                    @endphp
                    <tr class="border-b border-slate-100">
                        <th scope="row" class="sticky left-0 min-w-48 bg-white p-3"><a href="{{ route('employees.show', $employee) }}" class="text-teal-800 underline">{{ $employee->name }}</a><p class="mt-1 text-xs font-normal text-slate-500">{{ $result['job_role_name'] ?? 'No job assigned' }} &middot; {{ $result['facility_name'] ?? 'No facility assigned' }}</p><div class="mt-2"><x-status-badge :status="$result['status']" /></div>@if($result['reason'])<details class="mt-2 text-xs font-normal text-slate-500"><summary class="cursor-pointer text-amber-800">Why not assessable?</summary><p class="mt-2">{{ $result['reason'] }}</p></details>@endif</th>
                        @foreach($qualifications as $qualification)
                            @php
                                $item = $result['items'][$qualification['qualification_id']] ?? null;
                            @endphp
                            <td class="p-3 align-top">
                                @if($item)
                                    <x-status-badge :status="$item['state']" />
                                    @if($item['requires_training'] && ! $item['training_completed'])<span class="mt-1 block text-amber-800">Training incomplete</span>@endif
                                    @if($item['expiring_soon'])<span class="mt-1 block text-amber-800">Expires {{ $item['expires_at'] }} (within 30 days)</span>@endif
                                @else<span class="text-slate-500">N/A</span>@endif
                            </td>
                        @endforeach
                    </tr>
                @empty<tr><td class="p-3" colspan="{{ $qualifications->count() + 1 }}">{{ $filtered ? 'No employees match your filters. Try another name, facility or job.' : 'No employees in your assigned scope.' }}</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $employees->links() }}</div>
    </div>
</div>
</x-layout>
