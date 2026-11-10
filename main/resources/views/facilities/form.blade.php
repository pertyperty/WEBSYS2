<x-layout title="Edit Facilities">
    <a href="{{ route('facilities.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to Facilities</a>
    <h1 class="mb-6 mt-4 text-3xl font-bold">{{ $facility->exists ? 'Edit record' : 'Add record' }}</h1>
    <form method="POST" action="{{ $facility->exists ? route('facilities.update', $facility) : route('facilities.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($facility->exists)
            @method('PUT')
        @endif
        <div>
            <label for="name" class="text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" class="field" maxlength="255" value="{{ old('name', $facility->name) }}" required>
        </div>
        <div>
            <label for="region_id" class="text-sm font-medium">Region</label>
            <select id="region_id" name="region_id" class="field" required>
                <option value="">Select an active parent</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id', $facility->region_id) == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="is_active" class="text-sm font-medium">Availability</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $facility->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $facility->is_active))>Inactive</option>
            </select>
        </div>
        <p class="text-sm text-slate-600">Deactivation keeps the record. Resolve dependent assignments or requirements first.</p>
        <button type="submit" class="button">Save record</button>
    </form>
</x-layout>
