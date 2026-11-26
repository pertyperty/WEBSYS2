<x-layout title="Reports">

    <div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">

        {{-- Page Heading --}}
        <div class="page-heading">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Reports
                </h1>
                <p class="page-intro">
                    Review workforce readiness, qualification expiry, training progress, and corrective findings.
                </p>
            </div>

            <a
                class="button"
                data-filter-download
                :href="busy || error ? null : downloadUrl"
                :tabindex="busy || error ? -1 : 0"
                :aria-disabled="busy || !!error"
                :class="{ 'opacity-50 cursor-not-allowed': busy || error }"
                @click="if (busy || error) $event.preventDefault()"
                href="{{ route('reports.download', request()->only('type', 'facility_id', 'q')) }}"
            >
                <x-icon name="download" class="size-4" />
                Download CSV
            </a>
        </div>

        {{-- Report Filters --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">

            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900">
                      Report filters
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Select the report and narrow the results.
                </p>
            </div>

            <x-filter-bar :action="route('reports.index')">

                <div class="w-full sm:w-56">
                    <label for="type">Report type</label>
                    <select class="field" id="type" name="type">
                        @foreach([
                            'readiness' => 'Workforce readiness',
                            'expiry' => 'Qualification expiry',
                            'training' => 'Training progress',
                            'findings' => 'Corrective findings'
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($type === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-64">
                    <label for="facility_id">Facility</label>
                    <select class="field" id="facility_id" name="facility_id">
                        <option value="">All permitted facilities</option>
                        @foreach($facilities as $facility)
                            <option
                                value="{{ $facility->id }}"
                                @selected((string) request('facility_id') === (string) $facility->id)
                            >
                                {{ $facility->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full sm:w-64">
                    <label
                        for="q"
                        x-text="reportType === 'findings' ? 'Finding title' : 'Employee name'"
                    >{{ $type === 'findings' ? 'Finding title' : 'Employee name' }}</label>
                    <input
                        class="field"
                        id="q"
                        name="q"
                        autocomplete="off"
                        type="search"
                        maxlength="100"
                        value="{{ request('q') }}"
                        placeholder="Search..."
                    >
                </div>

            </x-filter-bar>
        </div>

        {{-- Report Results --}}
        <div
            data-filter-results
            class="scroll-mt-24"
            role="region"
            aria-label="Results"
            tabindex="-1"
            aria-busy="false"
            :aria-busy="busy"
            :class="{ 'opacity-60': busy }"
        >

            @if(count($rows))

                {{-- Result Summary --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Report Preview
                        </h2>
                        <p class="mt-1 text-sm text-slate-500" data-filter-summary>
                            <span class="font-semibold text-slate-900">
                                {{ count($rows) }}
                                {{ \Illuminate\Support\Str::plural('preview row', count($rows)) }}
                            </span>
                            &middot; Up to 25 rows shown. CSV exports all matches.
                        </p>
                    </div>

                    <span class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-800">
                        Preview
                    </span>
                </div>

                {{-- Report Table --}}
                <div
                    class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm"
                    role="region"
                    aria-label="Report preview"
                    tabindex="0"
                >
                    <table class="w-full min-w-[46rem] text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr class="border-b border-slate-200">
                                @foreach($headers as $header)
                                    @continue(in_array($header, ['Region', 'Assessed on']))

                                    <th scope="col" class="whitespace-nowrap px-5 py-4 font-semibold">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($rows as $row)
                                <tr class="transition-colors hover:bg-teal-50/30">

                                    @foreach($row as $column => $cell)
                                        @continue(in_array($headers[$column], ['Region', 'Assessed on']))

                                        <td class="max-w-64 px-5 py-4 align-middle text-slate-700">

                                            @if($headers[$column] === 'Status')
                                                <x-status-badge :status="$cell" />
                                            @else
                                                <span class="break-words">
                                                    {{ $cell ?? '--' }}
                                                </span>
                                            @endif

                                        </td>
                                    @endforeach

                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-slate-500">
                    Scroll horizontally to view additional columns on smaller screens.
                </p>

            @else

                <p class="sr-only" data-filter-summary>
                    No matching report rows.
                </p>

                <x-empty-state
                    title="No matching results"
                    message="Try another report, facility, or search."
                    icon="report"
                >
                    <a
                        class="text-link"
                        href="{{ route('reports.index') }}"
                        @click.prevent="reset()"
                    >
                        Clear filters
                    </a>
                </x-empty-state>

            @endif

        </div>

    </div>

</x-layout>
