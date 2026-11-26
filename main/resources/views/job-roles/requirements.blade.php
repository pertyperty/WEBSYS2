<x-layout title="Job requirements">
    @php($checkedIds = array_map('intval', (array) old('qualification_ids', $selected)))
    <a href="{{ route('job-roles.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Jobs</a>
    <div class="page-heading mt-3">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">{{ $jobRole->name }} requirements</h1>
            <p class="page-intro">Jobs without requirements are marked not assessable.</p>
        </div>
    </div>
    @if(! $jobRole->is_active)
        <p class="notice notice-warning mb-5">This job is inactive. Reactivate it before adding requirements.</p>
    @endif
    <form method="POST" action="{{ route('job-roles.requirements.update', $jobRole) }}" class="space-y-5" x-data="{ count: {{ count($checkedIds) }} }">
        @csrf
        @method('PUT')
        @forelse($domains as $name => $qualifications)
            <fieldset class="panel">
                <legend class="px-1 text-base font-semibold text-slate-900">{{ $name }}</legend>
                <div class="mt-3 divide-y divide-slate-100">
                    @foreach($qualifications as $qualification)
                        <label class="flex cursor-pointer items-start gap-3 py-3 first:pt-0 last:pb-0">
                            <input
                                type="checkbox"
                                name="qualification_ids[]"
                                value="{{ $qualification->id }}"
                                class="mt-0.5 rounded border-slate-300 text-teal-800 focus:ring-teal-600"
                                @change="count += $event.target.checked ? 1 : -1"
                                @checked(in_array($qualification->id, $checkedIds, true))
                            >
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $qualification->name }}</span>
                                <span class="text-xs text-slate-500">
                                    {{ $qualification->requires_training ? 'Training + evidence' : 'Evidence only' }} &middot;
                                    {{ $qualification->requires_expiry ? 'Expiry required' : 'No expiry required' }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @empty
            <x-empty-state title="No active qualifications" message="Add an active safety domain and qualification first." />
        @endforelse
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save requirements (<span x-text="count">{{ count($checkedIds) }}</span>)</button>
            <a href="{{ route('job-roles.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
