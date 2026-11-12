<x-layout title="Training course">
    <a href="{{ route('training-courses.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to courses</a>
    <div class="mb-8 mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">{{ $course->title }}</h1>
            <p class="mt-3 text-sm text-slate-600">{{ $course->qualification->name }} / {{ ucfirst($course->status->value) }}</p>
        </div>
        @can('manage-courses')
            <a href="{{ route('training-courses.edit', $course) }}" class="button">Edit course</a>
        @endcan
    </div>
    <section class="panel space-y-5">
        @if($course->description)<p class="whitespace-pre-line text-sm text-slate-700">{{ $course->description }}</p>@endif
        <p class="text-sm text-slate-600">Meeting this qualification requires recorded training completion and approved, unexpired evidence.</p>
        @if($ownEnrollment)
            <p class="font-semibold">Your status: {{ ucfirst($ownEnrollment->status->value) }}</p>
            @if($ownEnrollment->completed_at)
                <p class="text-sm text-slate-600">Check your evidence for {{ $course->qualification->name }}. When submitting it, select this qualification and completed course.</p>
                @can('submit-credentials')<a class="text-link" href="{{ route('credentials.index', ['search' => $course->qualification->name]) }}">View my evidence</a><a class="text-link" href="{{ route('credentials.create') }}">Submit evidence</a>@endcan
            @else
                <p class="text-sm text-slate-600">Attend the training, then ask a Compliance Officer to record your completion.</p>
            @endif
            <a href="{{ route('training-enrollments.index') }}" class="text-sm font-semibold text-teal-800 underline">View training progress</a>
        @else
            @can('enroll-training')
                @if($course->acceptsEnrollment())
                    <form method="POST" action="{{ route('training-enrollments.store', $course) }}">
                        @csrf
                        <button type="submit" class="button">Enroll in this offering</button>
                    </form>
                @else
                    <p class="text-sm font-semibold text-amber-800">This offering is not open for enrollment.</p>
                @endif
            @endcan
        @endif
    </section>
</x-layout>
