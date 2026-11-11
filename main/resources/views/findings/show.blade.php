<x-layout title="Finding details">
    <a class="text-link" href="{{ route('findings.index') }}">&larr; Corrective work</a>
    <div class="page-heading mt-5">
        <div>
            <h1 class="text-3xl font-bold">{{ $finding->title }}</h1>
            <p class="page-intro">{{ $finding->facility->name }}</p>
        </div>
        <x-status-badge :status="$finding->status" />
    </div>
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0">
            <p class="whitespace-pre-wrap text-slate-700">{{ $finding->description }}</p>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Assigned manager</dt>
                    <dd class="font-medium">{{ $finding->assignee->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Due date</dt>
                    <dd class="font-medium">{{ $finding->due_on->format('M j, Y') }}</dd>
                </div>
            </dl>
            <section class="mt-8">
                <h2 class="text-lg font-semibold">Activity</h2>
                <div class="record-list mt-3">
                    @forelse($finding->updates as $update)
                        <article class="py-5">
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="font-medium">{{ $update->author->name }}</p>
                                <x-status-badge :status="$update->status" />
                                <time class="text-xs text-slate-500">{{ $update->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $update->note }}</p>
                            @if($update->assignee_name)
                                <p class="mt-1 text-xs text-slate-500">Assigned to {{ $update->assignee_name }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="py-4 text-sm text-slate-500">No updates yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
        @if($finding->canReceiveUpdateFrom(auth()->user()))
            <section class="panel self-start">
                <h2 class="text-lg font-semibold">Update corrective work</h2>
                <form class="mt-5 space-y-4" method="POST" action="{{ route('finding-updates.store', $finding) }}">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium" for="status">Next status</label>
                        <select class="field" id="status" name="status" required>
                            <option value="" disabled @selected(! old('status'))>Choose next status</option>
                            @foreach(['open' => 'Reopen', 'in_progress' => 'In progress', 'pending_verification' => 'Submit for verification', 'resolved' => 'Verify and resolve'] as $value => $label)
                                @if(auth()->user()->can('conduct-audits') || in_array($value, ['in_progress', 'pending_verification']))
                                    <option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        @can('conduct-audits')
                            <div>
                                <label class="block text-sm font-medium" for="assignee_id">Assigned manager</label>
                                <select class="field" id="assignee_id" name="assignee_id">
                                    <option value="">Keep current assignment</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}" @selected((string) old('assignee_id') === (string) $manager->id)>{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endcan
                        <label class="block text-sm font-medium" for="note">Corrective notes</label>
                        <textarea class="field" name="note" id="note" rows="5" required maxlength="5000">{{ old('note') }}</textarea>
                        <p class="field-help">Describe the action taken or the result of your verification.</p>
                    </div>
                    <button class="button w-full" type="submit">Save update</button>
                </form>
            </section>
        @endif
    </div>
</x-layout>
