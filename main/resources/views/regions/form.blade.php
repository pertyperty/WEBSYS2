<x-layout title="Edit Regions">
    <a href="{{ route('regions.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to Regions</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">{{ $region->exists ? 'Edit record' : 'Add record' }}</h1>
    <form method="POST" action="{{ $region->exists ? route('regions.update', $region) : route('regions.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($region->exists)
            @method('PUT')
        @endif
        <div>
            <label for="name" class="text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" class="field" maxlength="255" value="{{ old('name', $region->name) }}" required>
        </div>

        <div>
            <label for="is_active" class="text-sm font-medium">Availability</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $region->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $region->is_active))>Inactive</option>
            </select>
        </div>
        <p class="text-sm text-slate-600">Deactivation keeps the record. Resolve dependent assignments or requirements first.</p>
        <button type="submit" class="button">Save record</button>
    </form>
</x-layout>
