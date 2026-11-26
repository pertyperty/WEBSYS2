<x-layout :title="$facility->exists ? 'Edit facility' : 'Add facility'">
    <a href="{{ route('facilities.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Facilities</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ $facility->exists ? 'Edit facility' : 'Add facility' }}</h1></div>
    </div>
    <form method="POST" action="{{ $facility->exists ? route('facilities.update', $facility) : route('facilities.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($facility->exists)
            @method('PUT')
        @endif
        <x-input name="name" label="Name" :value="$facility->name" maxlength="255" required />
        <div>
            <label for="region_id">Region</label>
            <select id="region_id" name="region_id" class="field" required>
                <option value="" disabled @selected(! old('region_id', $facility->region_id))>Select a region</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}" @selected(old('region_id', $facility->region_id) == $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $facility->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $facility->is_active))>Inactive</option>
            </select>
            <p class="field-help">Inactive records remain in history.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save facility</button>
            <a href="{{ route('facilities.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
