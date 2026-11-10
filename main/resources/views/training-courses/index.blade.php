<x-layout title="Training courses">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Training courses</h1>
            <p class="mt-3 text-sm text-slate-600">Find training for your required qualifications.</p>
        </div>
        @can('manage-courses')
            <a href="{{ route('training-courses.create') }}" class="button">Add course</a>
        @endcan
    </div>
    <div class="record-list">
        @forelse($courses as $course)
            <section class="record-row flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $course->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $course->qualification->name }} / {{ $course->qualification->safetyDomain->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $course->enrollments_count }} enrollments</p>
                </div>
                <x-status-badge :status="$course->status->value" />
                <a href="{{ route('training-courses.show', $course) }}" class="text-sm font-semibold text-teal-800 underline">View course</a>
            </section>
        @empty
            <p class="panel text-sm text-slate-600">No courses available.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $courses->links() }}</div>
</x-layout>
