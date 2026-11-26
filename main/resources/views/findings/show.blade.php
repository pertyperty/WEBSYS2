<x-layout title="Finding details">
    @php
        $overdue = $finding->status !== 'resolved' && $finding->due_on->toDateString() < now('Asia/Manila')->toDateString();
        $canOpenAudit = auth()->user()->role !== \App\Enums\UserRole::FacilityManager || $finding->audit->facility_id === auth()->user()->facility_id;
    @endphp
    <a class="back-link" href="{{ route('findings.index') }}"><x-icon name="arrow-left" class="size-4" />Corrective work</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $finding->title }}</h1>
            <p class="page-intro">
                {{ $finding->facility->name }} &middot;
                @if($canOpenAudit)
                    <a href="{{ route('audits.show', $finding->audit) }}" class="font-medium text-teal-800 hover:underline">{{ $finding->audit->title }}</a>
                @else
                    {{ $finding->audit->title }}
                @endif
            </p>
        </div>
        <x-status-badge :status="$finding->status" />
    </div>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            <section class="panel space-y-5">
                <p class="whitespace-pre-wrap text-sm text-slate-700">{{ $finding->description }}</p>
                <dl class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    <div>
                        <dt class="meta-label">Assigned manager</dt>
                        <dd class="meta-value">{{ $finding->assignee->name }}</dd>
                    </div>
                    <div>
                        <dt class="meta-label">Due date</dt>
                        <dd class="meta-value {{ $overdue ? 'text-rose-700' : '' }}">{{ $finding->due_on->format('M j, Y') }}@if($overdue) (Overdue)@endif</dd>
                    </div>
                </dl>
            </section>
            <section>
                <h2 class="mb-3 text-base font-semibold">Activity</h2>
                <div class="record-list">
                    @forelse($finding->updates as $update)
                        <article class="record-row">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <p class="text-sm font-semibold text-slate-900">{{ $update->author->name }}</p>
                                    <x-status-badge :status="$update->status" />
                                </div>
                                <time class="text-xs text-slate-500">{{ $update->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $update->note }}</p>
                            @if($update->assignee_name)
                                <p class="mt-1.5 text-xs text-slate-500">Reassigned to {{ $update->assignee_name }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500">No updates yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
        @if($finding->status === 'resolved' && ! auth()->user()->can('conduct-audits'))
            <section class="panel self-start text-sm text-slate-600" aria-label="Finding status">
                <h2 class="font-semibold text-slate-900">No further action needed</h2>
                <p class="mt-2">This finding is resolved. A compliance officer must reopen it before further updates.</p>
            </section>
        @elseif($finding->canReceiveUpdateFrom(auth()->user()))
            <section class="panel self-start">
                <h2 class="text-base font-semibold">Update work</h2>
                <form class="mt-4 space-y-4" method="POST" action="{{ route('finding-updates.store', $finding) }}">
                    @csrf
                    <div>
                        <label for="status">Next status</label>
                        <select class="field" id="status" name="status" required>
                            <option value="" disabled @selected(! old('status'))>Choose next status</option>
                            @foreach(['open' => 'Reopen', 'in_progress' => 'In progress', 'pending_verification' => 'Submit for verification', 'resolved' => 'Verify and resolve'] as $value => $label)
                                @if(auth()->user()->can('conduct-audits') || in_array($value, ['in_progress', 'pending_verification']))
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @can('conduct-audits')
                        <div>
                            <label for="assignee_id">Assigned manager</label>
                            <select class="field" id="assignee_id" name="assignee_id">
                                <option value="">Keep current assignment</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected((string) old('assignee_id') === (string) $manager->id)>{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endcan
                    <div>
                        <label for="note">Notes</label>
                        <textarea class="field" name="note" id="note" rows="4" required maxlength="5000">{{ old('note') }}</textarea>
                    </div>
                    <button class="button w-full" type="submit">Save update</button>
                </form>
            </section>
        @endif
    </div>
</x-layout>
