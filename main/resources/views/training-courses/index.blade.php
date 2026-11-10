<x-layout title="Training courses">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Training courses</h1>
            <p class="mt-3 text-sm text-slate-600">Browse published offerings and courses from your enrollment history.</p>
        </div>
        @can('manage-courses')
            <a href="{{ route('training-courses.create') }}" class="button">Add course</a>
        @endcan
    </div>
    <div class="space-y-4">
        @forelse($courses as $course)
            <section class="panel flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $course->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $course->qualification->name }} / {{ $course->qualification->safetyDomain->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($course->status->value) }} / {{ $course->enrollments_count }} enrollments in your scope</p>
                </div>
                <a href="{{ route('training-courses.show', $course) }}" class="text-sm font-semibold text-teal-800 underline">View course</a>
            </section>
        @empty
            <p class="panel text-sm text-slate-600">No courses available.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $courses->links() }}</div>
</x-layout>
