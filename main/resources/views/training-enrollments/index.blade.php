<x-layout title="Training progress">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Training progress</h1>
            <p class="mt-3 text-sm text-slate-600">{{ $completed }} completed / {{ $total - $completed }} awaiting completion / {{ $total }} enrollments in your scope.</p>
        </div>
        <a href="{{ route('training-courses.index') }}" class="button">Browse courses</a>
    </div>
    <p class="mb-6 text-sm text-slate-600">Completion records training only. Approved, valid evidence is also required for compliance.</p>
    <div class="space-y-5">
        @forelse($enrollments as $enrollment)
            <section class="panel space-y-4">
                <div>
                    <h2 class="font-semibold">{{ $enrollment->trainingCourse->title }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $enrollment->employee->name }} / {{ $enrollment->employee->facility?->name ?? 'No current facility' }} / {{ $enrollment->trainingCourse->qualification->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ ucfirst($enrollment->status->value) }} / Enrolled {{ $enrollment->enrolled_at->timezone('Asia/Manila')->format('M j, Y') }}</p>
                </div>
                @if($enrollment->completed_at)
                    <p class="text-sm">Completed {{ $enrollment->completed_at->format('M j, Y') }} / Recorded by {{ $enrollment->completedBy?->name }}</p>
                    @if($enrollment->feedback)
                        <p class="whitespace-pre-line text-sm text-slate-600">{{ $enrollment->feedback }}</p>
                    @endif
                @else
                    @can('complete-training')
                        @if($enrollment->employee_id !== auth()->id())
                            <form method="POST" action="{{ route('training-completions.update', $enrollment) }}" class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label for="completed-{{ $enrollment->id }}" class="text-sm font-medium">Completion date (Asia/Manila)</label>
                                    <input id="completed-{{ $enrollment->id }}" name="completed_at" type="date" class="field" max="{{ now('Asia/Manila')->toDateString() }}" min="{{ $enrollment->enrolled_at->timezone('Asia/Manila')->toDateString() }}" required>
                                </div>
                                <div>
                                    <label for="feedback-{{ $enrollment->id }}" class="text-sm font-medium">Feedback (optional)</label>
                                    <textarea id="feedback-{{ $enrollment->id }}" name="feedback" class="field" rows="2" maxlength="2000"></textarea>
                                </div>
                                <div><button type="submit" class="button">Record completion</button></div>
                            </form>
                        @endif
                    @endcan
                @endif
            </section>
        @empty
            <p class="panel text-sm text-slate-600">No enrollments in your scope.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $enrollments->links() }}</div>
</x-layout>
