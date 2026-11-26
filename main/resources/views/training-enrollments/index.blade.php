<x-layout title="Training records">
<div x-data="liveFilters({ protectCompletion: true })" @popstate.window="restore()" @click="follow($event)">
    <dialog data-unsaved-dialog aria-labelledby="unsaved-title" class="m-auto w-full max-w-md rounded-2xl bg-white p-6 shadow-xl backdrop:bg-slate-900/40" @cancel="cancelLeaving()">
        <h2 id="unsaved-title" class="text-lg font-semibold">Leave without saving?</h2>
        <p class="mt-2 text-sm text-slate-600">Your completion details have not been saved.</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <button type="button" class="button" @click="cancelLeaving()" autofocus>Stay</button>
            <button type="button" class="button-secondary" @click="confirmLeaving()" x-text="leaveIntent === 'reload' ? 'Reload without saving' : 'Leave without saving'">Leave without saving</button>
        </div>
    </dialog>
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Training records</h1></div>
        <a href="{{ route('training-courses.index') }}" class="button-secondary"><x-icon name="training" class="size-4" />Course catalog</a>
    </div>

    <x-filter-bar :action="route('training-enrollments.index')">
        <div class="w-full sm:max-w-sm"><label for="training-search">Employee, course or qualification</label><input id="training-search" name="search" type="search" class="field" value="{{ request('search') }}" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-52"><label for="training-status">Status</label><select id="training-status" name="status" class="field"><option value="">All statuses</option><option value="enrolled" @selected(request('status') === 'enrolled')>Awaiting completion</option><option value="completed" @selected(request('status') === 'completed')>Completed</option></select></div>
        <div class="w-full sm:w-64"><label for="training-facility">Facility</label><select id="training-facility" name="facility_id" class="field"><option value="">All assigned facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status') || request()->filled('facility_id');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Training results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <x-result-summary :paginator="$enrollments" noun="enrollment" :filtered="$filtered" />
            @if($total > 0)<p class="text-xs text-slate-500">{{ $completed }} completed &middot; {{ $total - $completed }} awaiting completion</p>@endif
        </div>
        <div class="record-list">
            @forelse($enrollments as $enrollment)
                <section class="record-row space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-slate-900">{{ $enrollment->trainingCourse->title }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $enrollment->employee->name }} &middot; {{ $enrollment->employee->facility?->name ?? 'No facility' }} &middot; {{ $enrollment->trainingCourse->qualification->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Enrolled {{ $enrollment->enrolled_at->timezone('Asia/Manila')->format('M j, Y') }}
                                @if($enrollment->completed_at) &middot; Completed {{ $enrollment->completed_at->format('M j, Y') }} ({{ $enrollment->completedBy?->name }})@endif
                            </p>
                        </div>
                        <x-status-badge :status="$enrollment->status->value" />
                    </div>
                    @if($enrollment->completed_at)
                        @if($enrollment->feedback)
                            <p class="whitespace-pre-line rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $enrollment->feedback }}</p>
                        @endif
                        @if($enrollment->employee_id === auth()->id())
                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-teal-50/70 px-3.5 py-2.5 text-xs text-slate-700">
                                <span>Completion alone does not meet {{ $enrollment->trainingCourse->qualification->name }}. Approved, linked evidence is also required.</span>
                                @can('submit-credentials')
                                    <span class="flex flex-wrap items-center gap-4">
                                        <a class="text-link min-h-0" href="{{ route('credentials.index', ['search' => $enrollment->trainingCourse->qualification->name]) }}">View my evidence</a>
                                        <a class="text-link min-h-0" href="{{ route('credentials.create', ['qualification' => $enrollment->trainingCourse->qualification_id]) }}">Submit evidence</a>
                                    </span>
                                @endcan
                            </div>
                        @endif
                    @else
                        @if($enrollment->employee_id === auth()->id())<p class="text-xs text-slate-500">Attend the training, then ask a Compliance Officer to record completion.</p>@endif
                        @can('complete-training')
                            @if($enrollment->employee_id !== auth()->id())
                                @php($retrying = (string) old('_completion_enrollment') === (string) $enrollment->id)
                                <details @if($retrying) open @endif>
                                    <summary class="text-link min-h-9 cursor-pointer">Record completion</summary>
                                    <form method="POST" action="{{ route('training-completions.update', $enrollment) }}" data-filter-edit-form class="mt-3 grid gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200/70 sm:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_completion_enrollment" value="{{ $enrollment->id }}">
                                        @foreach(request()->only('search', 'status', 'facility_id') as $name => $value)
                                            <input type="hidden" name="filters[{{ $name }}]" value="{{ $value }}">
                                        @endforeach
                                        <div>
                                            <label for="completed-{{ $enrollment->id }}">Completion date</label>
                                            <input id="completed-{{ $enrollment->id }}" name="completed_at" type="date" value="{{ $retrying ? old('completed_at') : '' }}" class="field" max="{{ now('Asia/Manila')->toDateString() }}" min="{{ $enrollment->enrolled_at->timezone('Asia/Manila')->toDateString() }}" required>
                                        </div>
                                        <div>
                                            <label for="feedback-{{ $enrollment->id }}">Notes <span class="font-normal text-slate-500">(optional)</span></label>
                                            <textarea id="feedback-{{ $enrollment->id }}" name="feedback" class="field" rows="2" maxlength="2000">{{ $retrying ? old('feedback') : '' }}</textarea>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                                            <button type="submit" class="button">Save completion</button>
                                            <button type="button" class="text-link text-slate-600 hover:text-teal-800" x-show="ready" x-cloak @click="$el.form.querySelectorAll('input[type=date], textarea').forEach(field => field.value = ''); error = ''">Clear</button>
                                        </div>
                                    </form>
                                </details>
                            @endif
                        @endcan
                    @endif
                </section>
            @empty
                <x-empty-state :title="$filtered ? 'No enrollments match your filters' : 'No training records yet'" :message="$filtered ? 'Try another search, status, or facility.' : 'Course enrollments will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('training-enrollments.index') }}" @click.prevent="reset()">Clear filters</a>@else<a class="text-link" href="{{ route('training-courses.index') }}">Browse courses</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $enrollments->links() }}</div>
    </div>
</div>
</x-layout>
