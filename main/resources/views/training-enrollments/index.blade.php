<x-layout title="Training progress">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Training progress</h1>
            <p class="mt-3 text-sm text-slate-600">Track course completion and what to do next.</p>
        </div>
        <a href="{{ route('training-courses.index') }}" class="button">Browse courses</a>
    </div>

    <x-filter-bar :action="route('training-enrollments.index')">
        <div class="w-full sm:max-w-sm"><label for="training-search">Employee, course or qualification</label><input id="training-search" name="search" type="search" class="field" value="{{ request('search') }}" maxlength="100" autocomplete="off"></div>
        <div class="w-full sm:w-56"><label for="training-status">Training status</label><select id="training-status" name="status" class="field"><option value="">All training statuses</option><option value="enrolled" @selected(request('status') === 'enrolled')>Awaiting completion</option><option value="completed" @selected(request('status') === 'completed')>Completed</option></select></div>
        <div class="w-full sm:w-64"><label for="training-facility">Facility</label><select id="training-facility" name="facility_id" class="field"><option value="">All assigned facilities</option>@foreach($facilities as $facility)<option value="{{ $facility->id }}" @selected((string) request('facility_id') === (string) $facility->id)>{{ $facility->name }}</option>@endforeach</select></div>
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status') || request()->filled('facility_id');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Training results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
    <x-result-summary :paginator="$enrollments" noun="enrollment" :filtered="$filtered" />
    @if($total > 0)<p class="mb-4 text-xs text-slate-500">{{ $completed }} completed &middot; {{ $total - $completed }} awaiting completion{{ $filtered ? ' in these results.' : '.' }}</p>@endif
    <div class="record-list">
        @forelse($enrollments as $enrollment)
            <section class="record-row space-y-4">
                <div>
                    <h2 class="font-semibold">{{ $enrollment->trainingCourse->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $enrollment->employee->name }} / {{ $enrollment->employee->facility?->name ?? 'No current facility' }} / {{ $enrollment->trainingCourse->qualification->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">Enrolled {{ $enrollment->enrolled_at->timezone('Asia/Manila')->format('M j, Y') }}</p>
                </div>
                <x-status-badge :status="$enrollment->status->value" />
                @if($enrollment->completed_at)
                    <p class="text-sm">Completed {{ $enrollment->completed_at->format('M j, Y') }} / Recorded by {{ $enrollment->completedBy?->name }}</p>
                    @if($enrollment->employee_id === auth()->id())
                        <p class="text-sm text-slate-600">Completion alone does not meet {{ $enrollment->trainingCourse->qualification->name }}. Check that your evidence is approved, unexpired and linked to this completed course.</p>
                        @can('submit-credentials')<a class="text-link" href="{{ route('credentials.index', ['search' => $enrollment->trainingCourse->qualification->name]) }}">View my evidence</a><a class="text-link" href="{{ route('credentials.create') }}">Submit evidence</a>@endcan
                    @endif
                    @if($enrollment->feedback)
                        <p class="whitespace-pre-line text-sm text-slate-600">{{ $enrollment->feedback }}</p>
                    @endif
                @else
                    @if($enrollment->employee_id === auth()->id())<p class="text-sm text-slate-600">Attend the training, then ask a Compliance Officer to record your completion.</p>@endif
                    @can('complete-training')
                        @if($enrollment->employee_id !== auth()->id())
                            @php
                                $retrying = (string) old('_completion_enrollment') === (string) $enrollment->id;
                            @endphp
                            <details class="pt-1" @if($retrying) open @endif>
                                <summary class="text-link cursor-pointer">Record completion</summary>
                            <form method="POST" action="{{ route('training-completions.update', $enrollment) }}" data-filter-edit-form class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_completion_enrollment" value="{{ $enrollment->id }}">
                                <div>
                                    <label for="completed-{{ $enrollment->id }}" class="text-sm font-medium">Completion date (Asia/Manila)</label>
                                    <input id="completed-{{ $enrollment->id }}" name="completed_at" type="date" value="{{ $retrying ? old('completed_at') : '' }}" class="field" max="{{ now('Asia/Manila')->toDateString() }}" min="{{ $enrollment->enrolled_at->timezone('Asia/Manila')->toDateString() }}" required>
                                </div>
                                <div>
                                    <label for="feedback-{{ $enrollment->id }}" class="text-sm font-medium">Feedback (optional)</label>
                                    <textarea id="feedback-{{ $enrollment->id }}" name="feedback" class="field" rows="2" maxlength="2000">{{ $retrying ? old('feedback') : '' }}</textarea>
                                </div>
                                <div class="flex flex-wrap items-center gap-4"><button type="submit" class="button">Record completion</button><button type="button" class="text-link" x-show="ready" x-cloak @click="$el.form.querySelectorAll('input[type=date], textarea').forEach(field => field.value = ''); error = ''">Clear entered details</button></div>
                            </form>
                            </details>
                        @endif
                    @endcan
                @endif
            </section>
        @empty
            <x-empty-state :title="$filtered ? 'No enrollments match your filters' : 'No training enrollments yet'" :message="$filtered ? 'Try another employee, course or training status.' : 'Enrollments will appear here after staff join a course.'">
                @if($filtered)<a class="text-link" href="{{ route('training-enrollments.index') }}" @click.prevent="reset()">Clear filters</a>@else<a class="text-link" href="{{ route('training-courses.index') }}">Browse courses</a>@endif
            </x-empty-state>
        @endforelse
    </div>
    <div class="mt-6">{{ $enrollments->links() }}</div>
    </div>
</div>
</x-layout>
