<x-layout :title="$jobRole->exists ? 'Edit job' : 'Add job'">
    <a href="{{ route('job-roles.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Jobs</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ $jobRole->exists ? 'Edit job' : 'Add job' }}</h1></div>
    </div>
    <form method="POST" action="{{ $jobRole->exists ? route('job-roles.update', $jobRole) : route('job-roles.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($jobRole->exists)
            @method('PUT')
        @endif
        <x-input name="name" label="Name" :value="$jobRole->name" maxlength="255" required />
        <div>
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $jobRole->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $jobRole->is_active))>Inactive</option>
            </select>
            <p class="field-help">Inactive records remain in history.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save job</button>
            <a href="{{ route('job-roles.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
