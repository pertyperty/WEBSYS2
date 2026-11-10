<x-layout title="Capture audit">
    <a class="text-link" href="{{ route('audits.index') }}">&larr; Audits</a>
    <div class="page-heading mt-5">
        <div>
            <h1 class="text-3xl font-bold">Capture audit</h1>
            <p class="page-intro">Save today's readiness before assigning follow-up work.</p>
        </div>
    </div>
    <form class="panel max-w-2xl space-y-5" method="POST" action="{{ route('audits.store') }}" x-data="auditScopeForm(@js($facilities->map->only(['id', 'name', 'region_id'])), @js((string) old('region_id', '')), @js((string) old('facility_id', '')))">
        @csrf
        <x-input name="title" label="Audit title" required maxlength="255" :value="old('title')" />
        <div>
            <label class="block text-sm font-medium" for="region_id">Region</label>
            <select class="field" id="region_id" name="region_id" x-model="regionId" @change="facilityId = ''" required>
                <option value="" disabled>Select a region</option>
                @foreach($regions as $region)
                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="facility_id">Facility</label>
            <select class="field" id="facility_id" name="facility_id" x-model="facilityId" :disabled="!regionId">
                <option value="">All facilities in this region</option>
                <template x-for="facility in availableFacilities" :key="facility.id">
                    <option :value="String(facility.id)" x-text="facility.name">
                    </option>
                </template>
            </select>
            <p class="field-help">Leave the region-wide selection to include every facility.</p>
        </div>
        <p class="text-sm text-slate-500">This captures a fixed record of today's results. Future renewals will appear in live readiness without changing this audit.</p>
        <div class="flex flex-wrap gap-3">
            <button class="button" type="submit">Capture readiness</button>
            <a class="button-secondary" href="{{ route('audits.index') }}">Cancel</a>
        </div>
    </form>
</x-layout>
