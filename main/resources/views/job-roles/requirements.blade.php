<x-layout title="Job requirements">
    <a href="{{ route('job-roles.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to jobs</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $jobRole->name }} requirements</h1>
    <p class="mb-8 mt-3 max-w-3xl text-sm text-slate-600">Select every mandatory qualification for this job. Staff inherit these requirements through their assigned job. An empty selection means not assessable when compliance assessment is implemented.</p>
    @if(! $jobRole->is_active)
        <p class="mb-5 text-sm font-semibold text-amber-800">This job is inactive. You may clear its requirements; reactivate the job before adding requirements.</p>
    @endif
    <form method="POST" action="{{ route('job-roles.requirements.update', $jobRole) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @forelse($domains as $name => $qualifications)
            <fieldset class="panel">
                <legend class="px-2 font-semibold">{{ $name }}</legend>
                <div class="space-y-4">
                    @foreach($qualifications as $qualification)
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="qualification_ids[]"
                                value="{{ $qualification->id }}"
                                class="mt-1 rounded border-slate-300 text-teal-800 focus:ring-teal-600"
                                @checked(in_array($qualification->id, (array) old('qualification_ids', $selected)))
                            >
                            <span>
                                <span class="block text-sm font-semibold">{{ $qualification->name }}</span>
                                <span class="text-xs text-slate-500">
                                    {{ $qualification->requires_training ? 'Training completion and evidence' : 'Evidence' }} /
                                    {{ $qualification->requires_expiry ? 'Expiry date required' : 'Expiry date optional' }}
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @empty
            <p class="panel text-sm text-slate-600">No active qualifications. Ask an administrator to add a safety domain and qualifications first.</p>
        @endforelse
        <button type="submit" class="button">Save mandatory requirements</button>
    </form>
</x-layout>
