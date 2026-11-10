<x-layout title="Edit Safety domains">
    <a href="{{ route('safety-domains.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to Safety domains</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">{{ $safetyDomain->exists ? 'Edit safety domain' : 'Add safety domain' }}</h1>
    <form method="POST" action="{{ $safetyDomain->exists ? route('safety-domains.update', $safetyDomain) : route('safety-domains.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($safetyDomain->exists)
            @method('PUT')
        @endif
        <div>
            <label for="name" class="text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" class="field" maxlength="255" value="{{ old('name', $safetyDomain->name) }}" required>
        </div>

        <div>
            <label for="is_active" class="text-sm font-medium">Availability</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $safetyDomain->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $safetyDomain->is_active))>Inactive</option>
            </select>
        </div>
        <p class="field-help">Inactive records stay in history. Remove active dependencies before deactivating.</p>
        <button type="submit" class="button">Save safety domain</button>
        <a href="{{ route('safety-domains.index') }}" class="button-secondary">Cancel</a>
    </form>
</x-layout>
