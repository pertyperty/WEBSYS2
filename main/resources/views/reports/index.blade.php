<x-layout title="Reports">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Reports</h1>
            <p class="page-intro">Preview results, then download the full selection.</p>
        </div>
        <a class="button" data-filter-download :href="busy || error ? null : downloadUrl" :tabindex="busy || error ? -1 : 0" :aria-disabled="busy || !!error" :class="{ 'opacity-50': busy || error }" @click="if (busy || error) $event.preventDefault()" href="{{ route('reports.download', request()->only('type', 'facility_id', 'q')) }}">Download CSV</a>
    </div>
    <x-filter-bar :action="route('reports.index')">
        <div class="w-full sm:w-64">
            <label class="block text-sm font-medium" for="type">Report</label>
            <select class="field" id="type" name="type">
                @foreach(['readiness' => 'Workforce readiness', 'expiry' => 'Qualification expiry', 'training' => 'Training progress', 'findings' => 'Corrective findings'] as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-64">
            <label class="block text-sm font-medium" for="facility_id">Facility</label>
            <select class="field" id="facility_id" name="facility_id">
                <option value="">All permitted facilities</option>
                @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-64">
            <label class="block text-sm font-medium" for="q" x-text="reportType === 'findings' ? 'Finding title' : 'Employee name'">{{ $type === 'findings' ? 'Finding title' : 'Employee name' }}</label>
            <input class="field" id="q" name="q" autocomplete="off" type="search" maxlength="100" value="{{ request('q') }}">
        </div>
    </x-filter-bar>
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    @if(count($rows))
        <p class="mb-3 text-sm text-slate-600" data-filter-summary>{{ count($rows) }} {{ \Illuminate\Support\Str::plural('preview row', count($rows)) }}. Showing up to 25; CSV includes all matching results.</p>
        <div class="overflow-x-auto rounded-xl bg-white focus-visible:outline-2 focus-visible:outline-teal-700" role="region" aria-label="Report preview" tabindex="0">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-slate-100 text-xs text-slate-600">
                    <tr>
                        @foreach($headers as $header)
                            @continue(in_array($header, ['Region', 'Assessed on']))
                            <th scope="col" class="px-4 py-3 font-semibold">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $column => $cell)
                            @continue(in_array($headers[$column], ['Region', 'Assessed on']))
                                <td class="max-w-56 px-4 py-4 align-top">
                                    @if($headers[$column] === 'Status')
                                        <x-status-badge :status="$cell" />
                                    @else
                                        {{ $cell ?? 'Not available' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="sr-only" data-filter-summary>No matching report rows.</p>
        <x-empty-state title="No matching results" message="Try another report, facility or search."><a class="text-link" href="{{ route('reports.index') }}" @click.prevent="reset()">Clear filters</a></x-empty-state>
    @endif
    </div>
</div>
</x-layout>
