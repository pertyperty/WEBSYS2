<x-layout title="Training course">
    <a href="{{ route('training-courses.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Course catalog</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $course->title }}</h1>
            <p class="page-intro">{{ $course->qualification->name }} &middot; {{ $course->qualification->safetyDomain->name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-status-badge :status="$course->status->value" />
            @can('manage-courses')
                <a href="{{ route('training-courses.edit', $course) }}" class="button-secondary">Edit course</a>
            @endcan
        </div>
    </div>
    <section class="panel max-w-3xl space-y-5">
        @if($course->description)<p class="whitespace-pre-line text-sm text-slate-700">{{ $course->description }}</p>@endif
        @if($ownEnrollment)
            <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-inset ring-slate-200/70">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="text-sm font-semibold text-slate-900">Your status: {{ ucfirst($ownEnrollment->status->value) }}</span>
                    <x-status-badge :status="$ownEnrollment->status->value" />
                </div>
                @if($ownEnrollment->completed_at)
                    <p class="mt-2 text-sm text-slate-600">When submitting evidence for {{ $course->qualification->name }}, select this qualification and completed course.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        @can('submit-credentials')
                            <a class="button" href="{{ route('credentials.create', ['qualification' => $course->qualification_id]) }}">Submit evidence</a>
                            <a class="text-link" href="{{ route('credentials.index', ['search' => $course->qualification->name]) }}">View my evidence</a>
                        @endcan
                        <a href="{{ route('training-enrollments.index') }}" class="text-link">Training records</a>
                    </div>
                @else
                    <p class="mt-2 text-sm text-slate-600">Attend the session, then have a Compliance Officer record completion.</p>
                    <a href="{{ route('training-enrollments.index') }}" class="text-link mt-2">View training progress</a>
                @endif
            </div>
        @else
            @can('enroll-training')
                @if($course->acceptsEnrollment())
                    <form method="POST" action="{{ route('training-enrollments.store', $course) }}">
                        @csrf
                        <button type="submit" class="button">Enroll in course</button>
                    </form>
                @else
                    <p class="notice notice-warning">This offering is not open for enrollment.</p>
                @endif
            @endcan
        @endif
        <p class="text-xs text-slate-500">Compliance requires both recorded training completion and approved, unexpired evidence.</p>
    </section>
</x-layout>
