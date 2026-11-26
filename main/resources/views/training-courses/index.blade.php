<x-layout title="Training courses">
<div x-data="liveFilters" @popstate.window="restore()" @click="follow($event)">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Course catalog</h1></div>
        @can('manage-courses')
            <a href="{{ route('training-courses.create') }}" class="button"><x-icon name="plus" class="size-4" />Add course</a>
        @endcan
    </div>
    <x-filter-bar :action="route('training-courses.index')">
        <div class="w-full sm:max-w-sm"><label for="course-search">Course, qualification or safety domain</label><input id="course-search" name="search" type="search" value="{{ request('search') }}" class="field" maxlength="100" autocomplete="off"></div>
        @can('manage-courses')<div class="w-full sm:w-52"><label for="status">Course status</label><select class="field" id="status" name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>@endforeach</select></div>@endcan
    </x-filter-bar>
    @php
        $filtered = request()->filled('search') || request()->filled('status');
    @endphp
    <div data-filter-results class="scroll-mt-24" role="region" aria-label="Results" tabindex="-1" aria-busy="false" :aria-busy="busy" :class="{ 'opacity-60': busy }">
        <x-result-summary :paginator="$courses" noun="course" :filtered="$filtered" />
        <div class="record-list">
            @forelse($courses as $course)
                <a href="{{ route('training-courses.show', $course) }}" class="record-row group flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900 group-hover:text-teal-800">{{ $course->title }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $course->qualification->name }} &middot; {{ $course->qualification->safetyDomain->name }} &middot; {{ $course->enrollments_count }} enrolled</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-status-badge :status="$course->status->value" />
                        <x-icon name="arrow" class="size-4 text-slate-400 transition-colors group-hover:text-teal-800" />
                    </div>
                </a>
            @empty
                <x-empty-state :title="$filtered ? 'No courses match your filters' : 'No courses available'" :message="$filtered ? 'Try another search or status.' : 'Published courses will appear here.'">
                    @if($filtered)<a class="text-link" href="{{ route('training-courses.index') }}" @click.prevent="reset()">Clear filters</a>@endif
                </x-empty-state>
            @endforelse
        </div>
        <div class="mt-6">{{ $courses->links() }}</div>
    </div>
</div>
</x-layout>
