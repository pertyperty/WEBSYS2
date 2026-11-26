<x-layout title="Capture audit">
    <a class="back-link" href="{{ route('audits.index') }}"><x-icon name="arrow-left" class="size-4" />Audits</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Capture audit</h1></div>
    </div>
    <form class="panel max-w-2xl space-y-5" method="POST" action="{{ route('audits.store') }}" x-data="auditScopeForm(@js($facilities->map->only(['id', 'name', 'region_id'])), @js((string) old('region_id', '')), @js((string) old('facility_id', '')))">
        @csrf
        <x-input name="title" label="Audit title" required maxlength="255" :value="old('title')" />
        <div>
            <label for="region_id">Region</label>
            <select class="field" id="region_id" name="region_id" x-model="regionId" @change="facilityId = ''" required>
                <option value="" disabled @selected(! old('region_id'))>Select a region</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="facility_id">Facility</label>
            <select class="field" id="facility_id" name="facility_id" x-model="facilityId" :disabled="!regionId">
                <option value="">All facilities in this region</option>
                <template x-for="facility in availableFacilities" :key="facility.id">
                    <option :value="String(facility.id)" :selected="String(facility.id) === String(facilityId)" x-text="facility.name"></option>
                </template>
            </select>
        </div>
        <p class="field-help">Saves a fixed snapshot of today's readiness before assigning corrective work.</p>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button class="button" type="submit">Capture readiness</button>
            <a class="button-secondary" href="{{ route('audits.index') }}">Cancel</a>
        </div>
    </form>
</x-layout>
