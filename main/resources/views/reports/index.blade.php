<x-layout title="Reports">
    <div class="page-heading">
        <div>
            <h1 class="text-3xl font-bold">Reports</h1>
            <p class="page-intro">Preview results, then download the full selection.</p>
        </div>
        <a class="button" href="{{ route('reports.download', request()->only('type', 'facility_id', 'q')) }}">Download CSV</a>
    </div>
    <form class="mb-8 grid items-end gap-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto]" method="GET">
        <div>
            <label class="block text-sm font-medium" for="type">Report</label>
            <select class="field" id="type" name="type">
                @foreach(['readiness' => 'Workforce readiness', 'expiry' => 'Qualification expiry', 'training' => 'Training progress', 'findings' => 'Corrective findings'] as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="facility_id">Facility</label>
            <select class="field" id="facility_id" name="facility_id">
                <option value="">All permitted facilities</option>
                @foreach($facilities as $facility)
                    <option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="q">Employee or finding title</label>
            <input class="field" id="q" name="q" type="search" maxlength="100" value="{{ request('q') }}">
        </div>
        <button class="button-secondary" type="submit">Apply filters</button>
    </form>
    @if(count($rows))
        <p class="mb-3 text-sm text-slate-500">Preview of up to 25 rows. CSV includes all matching results.</p>
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
        <x-empty-state title="No matching results" message="Try another report or change the filters." />
    @endif
</x-layout>
