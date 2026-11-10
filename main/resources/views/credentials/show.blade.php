<x-layout title="Credential submission">
    <a href="{{ route('credentials.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to credentials</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $document->qualification->name }}</h1>
    <p class="mt-3 text-sm text-slate-600">{{ $document->employee->name }}</p>
    <div class="mt-3"><x-status-badge :status="$document->status->value" /></div>
    <details class="mt-3 text-xs text-slate-500"><summary class="cursor-pointer">Submission reference</summary><p class="mt-2 break-all font-mono">{{ $document->reference }}</p></details>
    <section class="panel mt-8 space-y-4">
        <h2 class="text-lg font-semibold">Submission details</h2>
        <p class="text-sm">Issued {{ $document->issued_at->format('M j, Y') }} / Expires {{ $document->expires_at?->format('M j, Y') ?? 'Not specified' }}</p>
        <p class="text-sm text-slate-600">At submission: {{ $document->facility->name }} / {{ $document->jobRole->name }}</p>
        @if($document->employeeTraining)
            <p class="text-sm">Training: {{ $document->employeeTraining->trainingCourse->title }} / {{ ucfirst($document->employeeTraining->status->value) }}</p>
        @endif
        @if($available)
            <a href="{{ route('credentials.download', $document) }}" class="button">Download private evidence</a>
        @else
            <p class="text-sm font-semibold text-amber-800">The stored file is unavailable. Contact an administrator.</p>
        @endif
        @if($effective)
            <p class="text-sm text-slate-600">Currently qualifying approved evidence:
                <a href="{{ route('credentials.show', $effective) }}" class="break-all font-semibold text-teal-800 underline">{{ $effective->reference }}</a>
            </p>
        @else
            <p class="text-sm text-slate-600">No approved evidence currently qualifies for this qualification.</p>
        @endif
        <details class="text-xs text-slate-500"><summary class="cursor-pointer text-teal-800">About approval and validity</summary><p class="mt-2">Approval is the review decision. Current validity also requires valid dates and completed training. Employee readiness includes all job requirements.</p></details>
    </section>
    <section class="panel mt-6">
        <h2 class="mb-4 text-lg font-semibold">Submission and review history</h2>
        <ol class="space-y-4">
            @forelse($document->events as $event)
                <li class="border-t border-slate-100 pt-4">
                    <p class="text-sm font-semibold">{{ ucfirst($event->type->value) }} / {{ $event->actor->name }} / {{ $event->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</p>
                    @if($event->note)<p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $event->note }}</p>@endif
                </li>
            @empty
                <li class="text-sm text-slate-600">No recorded events.</li>
            @endforelse
        </ol>
    </section>
    @can('review-credentials')
        @if($document->status === App\Enums\CredentialStatus::Pending && $document->employee_id !== auth()->id())
            <form method="POST" action="{{ route('credentials.reviews.store', $document) }}" class="panel mt-6 max-w-2xl space-y-4" x-data="{ decision: @js(old('decision', '')) }">
                @csrf
                <h2 class="text-lg font-semibold">Officer review</h2>
                <div>
                    <label for="decision" class="text-sm font-medium">Decision</label>
                    <select id="decision" name="decision" class="field" x-model="decision" required>
                        <option value="" disabled>Select a decision</option>
                        <option value="approved" @selected(old('decision') === 'approved')>Approve evidence</option>
                        <option value="rejected" @selected(old('decision') === 'rejected')>Reject evidence</option>
                    </select>
                </div>
                <div>
                    <label for="note" class="text-sm font-medium">Feedback (required for rejection)</label>
                    <textarea id="note" name="note" class="field" :required="decision === 'rejected'" rows="4" maxlength="2000">{{ old('note') }}</textarea>
                </div>
                <p class="text-sm text-slate-600">Check the file and training details. A recorded review cannot be changed; corrections require a new submission.</p>
                <button type="submit" class="button">Record review</button>
            </form>
        @endif
    @endcan
</x-layout>
