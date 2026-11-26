    <x-filter-bar :action="route('training-courses.index')">
        <div class="w-full sm:max-w-sm">
            <label for="course-search">Course, qualification or safety domain</label>
            <input
                id="course-search"
                name="search"
                type="search"
                value="{{ request('search') }}"
                class="field"
                maxlength="100"
                autocomplete="off"
            >
        </div>

        @can('manage-courses')
            <div class="w-full sm:w-52">
                <label for="status">Course status</label>
                <select class="field" id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(request('status') === $status->value)
                        >
                            {{ ucfirst($status->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endcan
    </x-filter-bar>

    @php
        $filtered = request()->filled('search') || request()->filled('status');
    @endphp

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
        <x-result-summary :paginator="$courses" noun="course" :filtered="$filtered" />

        <div class="record-list">
            @forelse($courses as $course)
                <section class="record-row flex flex-wrap items-center justify-between gap-4">

                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-slate-900">
                            {{ $course->title }}
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            {{ $course->qualification->name }}
                            /
                            {{ $course->qualification->safetyDomain->name }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ $course->enrollments_count }} enrollments
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <div class="w-28 shrink-0">
                            <x-status-badge :status="$course->status->value" />
                        </div>

                        <a
                            href="{{ route('training-courses.show', $course) }}"
                            class="button"
                        >
                            View course →
                        </a>
                    </div>

                </section>
            @empty
                <x-empty-state
                    :title="$filtered ? 'No courses match your filters' : 'No courses available'"
                    :message="$filtered ? 'Try another search or status.' : 'Published courses will appear here.'"
                >
                    @if($filtered)
                        <a
                            class="text-link"
                            href="{{ route('training-courses.index') }}"
                            @click.prevent="reset()"
                        >
                            Clear filters
                        </a>
                    @endif
                </x-empty-state>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $courses->links() }}
        </div>
    </div>
