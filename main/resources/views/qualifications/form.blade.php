<x-layout title="Edit Qualifications">
    <a href="{{ route('qualifications.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to Qualifications</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">{{ $qualification->exists ? 'Edit record' : 'Add record' }}</h1>
    <form method="POST" action="{{ $qualification->exists ? route('qualifications.update', $qualification) : route('qualifications.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($qualification->exists)
            @method('PUT')
        @endif
        <div>
            <label for="name" class="text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" class="field" maxlength="255" value="{{ old('name', $qualification->name) }}" required>
        </div>
        <div>
            <label for="safety_domain_id" class="text-sm font-medium">Safety domain</label>
            <select id="safety_domain_id" name="safety_domain_id" class="field" required>
                <option value="">Select an active parent</option>
                @foreach($domains as $domain)
                    <option value="{{ $domain->id }}" @selected(old('safety_domain_id', $qualification->safety_domain_id) == $domain->id)>{{ $domain->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="requires_expiry" class="text-sm font-medium">Expiry date on evidence</label>
            <select id="requires_expiry" name="requires_expiry" class="field">
                <option value="1" @selected(old('requires_expiry', $qualification->requires_expiry))>Required</option>
                <option value="0" @selected(! old('requires_expiry', $qualification->requires_expiry))>Optional (can be non-expiring)</option>
            </select>
        </div>
        <div>
            <label for="requires_training" class="text-sm font-medium">Training completion</label>
            <select id="requires_training" name="requires_training" class="field">
                <option value="0" @selected(! old('requires_training', $qualification->requires_training))>Not required</option>
                <option value="1" @selected(old('requires_training', $qualification->requires_training))>Required in addition to approved evidence</option>
            </select>
        </div>
        <div>
            <label for="is_active" class="text-sm font-medium">Availability</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $qualification->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $qualification->is_active))>Inactive</option>
            </select>
        </div>
        <p class="text-sm text-slate-600">Deactivation keeps the record. Resolve dependent assignments or requirements first.</p>
        <button type="submit" class="button">Save record</button>
    </form>
</x-layout>
