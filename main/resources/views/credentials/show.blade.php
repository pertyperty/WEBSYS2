<x-layout title="Document details">
    <a href="{{ route('credentials.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Documents</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $document->qualification->name }}</h1>
            <p class="page-intro">{{ $document->employee->name }} &middot; <span class="font-mono text-xs">{{ $document->reference }}</span></p>
        </div>
        <x-status-badge :status="$document->status->value" />
    </div>
    <section class="panel space-y-5">
        <dl class="meta-grid">
            <div><dt class="meta-label">Issued</dt><dd class="meta-value">{{ $document->issued_at->format('M j, Y') }}</dd></div>
            <div><dt class="meta-label">Expires</dt><dd class="meta-value">{{ $document->expires_at?->format('M j, Y') ?? 'No expiry' }}</dd></div>
            <div><dt class="meta-label">Facility &amp; job</dt><dd class="meta-value">{{ $document->facility->name }} &middot; {{ $document->jobRole->name }}</dd></div>
            @if($document->employeeTraining)
                <div><dt class="meta-label">Training</dt><dd class="meta-value">{{ $document->employeeTraining->trainingCourse->title }} ({{ ucfirst($document->employeeTraining->status->value) }})</dd></div>
            @endif
        </dl>
        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-4">
            @if($available)
                <a href="{{ route('credentials.download', $document) }}" class="button"><x-icon name="download" class="size-4" />Download private evidence</a>
            @else
                <p class="notice notice-warning">The stored file is unavailable. Contact an administrator.</p>
            @endif
            @if($effective)
                <p class="text-xs text-slate-500">Active qualifying document: <a href="{{ route('credentials.show', $effective) }}" class="font-mono font-semibold text-teal-800 hover:underline">{{ $effective->reference }}</a></p>
            @else
                <p class="text-xs text-slate-500">No approved evidence currently qualifies for this qualification.</p>
            @endif
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="panel">
            <h2 class="mb-4 text-base font-semibold">Review history</h2>
            <ol class="divide-y divide-slate-100">
                @forelse($document->events as $event)
                    <li class="py-3.5 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">{{ ucfirst($event->type->value) }} <span class="font-normal text-slate-500">by {{ $event->actor->name }}</span></p>
                            <time class="text-xs text-slate-500">{{ $event->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time>
                        </div>
                        @if($event->note)<p class="mt-1.5 whitespace-pre-line text-sm text-slate-600">{{ $event->note }}</p>@endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No recorded events.</li>
                @endforelse
            </ol>
        </section>

        @can('review-credentials')
            @if($document->status === App\Enums\CredentialStatus::Pending && $document->employee_id !== auth()->id())
                <form method="POST" action="{{ route('credentials.reviews.store', $document) }}" class="panel self-start space-y-4" x-data="{ decision: @js(old('decision', '')) }">
                    @csrf
                    <h2 class="text-base font-semibold">Officer review</h2>
                    <div>
                        <label for="decision">Decision</label>
                        <select id="decision" name="decision" class="field" x-model="decision" required>
                            <option value="" disabled @selected(! old('decision'))>Select a decision</option>
                            <option value="approved" @selected(old('decision') === 'approved')>Approve evidence</option>
                            <option value="rejected" @selected(old('decision') === 'rejected')>Reject evidence</option>
                        </select>
                    </div>
                    <div>
                        <label for="note">Feedback <span class="font-normal text-slate-500" x-text="decision === 'rejected' ? '(required)' : '(optional)'">(optional)</span></label>
                        <textarea id="note" name="note" class="field" :required="decision === 'rejected'" rows="4" maxlength="2000">{{ old('note') }}</textarea>
                        <p class="field-help">Reviews are permanent.</p>
                    </div>
                    <button type="submit" class="button w-full">Record review</button>
                </form>
            @endif
        @endcan
    </div>
</x-layout>
