<x-layout :title="$qualification->exists ? 'Edit qualification' : 'Add qualification'">
    <a href="{{ route('qualifications.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Qualifications</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ $qualification->exists ? 'Edit qualification' : 'Add qualification' }}</h1></div>
    </div>
    <form method="POST" action="{{ $qualification->exists ? route('qualifications.update', $qualification) : route('qualifications.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($qualification->exists)
            @method('PUT')
        @endif
        <x-input name="name" label="Name" :value="$qualification->name" maxlength="255" required />
        <div>
            <label for="safety_domain_id">Safety domain</label>
            <select id="safety_domain_id" name="safety_domain_id" class="field" required>
                <option value="" disabled @selected(! old('safety_domain_id', $qualification->safety_domain_id))>Select a safety domain</option>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected(old('safety_domain_id', $qualification->safety_domain_id) == $domain->id)>{{ $domain->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="requires_expiry">Expiry date</label>
                <select id="requires_expiry" name="requires_expiry" class="field">
                    <option value="1" @selected(old('requires_expiry', $qualification->requires_expiry))>Required</option>
                    <option value="0" @selected(! old('requires_expiry', $qualification->requires_expiry))>Optional</option>
                </select>
            </div>
            <div>
                <label for="requires_training">Training completion</label>
                <select id="requires_training" name="requires_training" class="field">
                    <option value="0" @selected(! old('requires_training', $qualification->requires_training))>Not required</option>
                    <option value="1" @selected(old('requires_training', $qualification->requires_training))>Required</option>
                </select>
            </div>
        </div>
        <div>
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $qualification->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $qualification->is_active))>Inactive</option>
            </select>
            <p class="field-help">Inactive records remain in history.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save qualification</button>
            <a href="{{ route('qualifications.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
