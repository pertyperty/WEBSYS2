<x-layout title="Qualification matrix">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">Qualification matrix</h1>
            <p class="page-intro">Showing qualifications required on this page &middot; N/A = not required.</p>
        </div>
    </div>
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
        <div class="overflow-x-auto rounded-2xl bg-white shadow-xs ring-1 ring-slate-200/80" role="region" aria-label="Scrollable qualification matrix" tabindex="0">
            <table class="w-full text-left text-sm">
                <caption class="sr-only">Scoped employee compliance by required qualification</caption>
                <thead class="bg-slate-50/80">
                    <tr class="border-b border-slate-200">
                        <th scope="col" class="sticky left-0 z-10 border-r border-slate-200/80 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-600">Employee</th>
                        @foreach($qualifications as $qualification)
                            <th scope="col" class="min-w-36 px-4 py-3 text-xs font-semibold text-slate-700">{{ $qualification['name'] }}<span class="mt-0.5 block font-normal text-slate-500">{{ $qualification['domain'] }}</span></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                        @php($result = $assessments[$employee->id])
                        <tr class="hover:bg-slate-50/60">
                            <th scope="row" class="sticky left-0 z-10 min-w-52 border-r border-slate-200/80 bg-white px-4 py-3.5 align-top font-normal">
                                <a href="{{ route('employees.show', $employee) }}" class="font-semibold text-teal-800 hover:underline">{{ $employee->name }}</a>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $result['job_role_name'] ?? 'No job' }} &middot; {{ $result['facility_name'] ?? 'No facility' }}</p>
                                <div class="mt-2"><x-status-badge :status="$result['status']" /></div>
                                @if($result['reason'])<details class="mt-1.5 text-xs text-slate-500"><summary class="cursor-pointer font-medium text-amber-800">Why?</summary><p class="mt-1">{{ $result['reason'] }}</p></details>@endif
                            </th>
                            @foreach($qualifications as $qualification)
                                @php($item = $result['items'][$qualification['qualification_id']] ?? null)
                                <td class="px-4 py-3.5 align-top">
                                    @if($item)
                                        <x-status-badge :status="$item['state']" />
                                        @if($item['requires_training'] && ! $item['training_completed'])<span class="mt-1 block text-xs text-amber-800">Training needed</span>@endif
                                        @if($item['expiring_soon'])<span class="mt-1 block text-xs font-medium text-amber-800">Exp. {{ $item['expires_at'] }}</span>@endif
                                    @else
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-sm text-slate-500" colspan="{{ $qualifications->count() + 1 }}">{{ $filtered ? 'No employees match your filters.' : 'No employees in scope.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $employees->links() }}</div>
    </div>
</div>
</x-layout>
