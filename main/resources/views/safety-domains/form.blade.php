<x-layout :title="$safetyDomain->exists ? 'Edit safety domain' : 'Add safety domain'">
    <a href="{{ route('safety-domains.index') }}" class="back-link"><x-icon name="arrow-left" class="size-4" />Safety domains</a>
    <div class="page-heading mt-3">
        <div><h1 class="text-2xl font-bold sm:text-3xl">{{ $safetyDomain->exists ? 'Edit safety domain' : 'Add safety domain' }}</h1></div>
    </div>
    <form method="POST" action="{{ $safetyDomain->exists ? route('safety-domains.update', $safetyDomain) : route('safety-domains.store') }}" class="panel max-w-2xl space-y-5">
        @csrf
        @if($safetyDomain->exists)
            @method('PUT')
        @endif
        <x-input name="name" label="Name" :value="$safetyDomain->name" maxlength="255" required />
        <div>
            <label for="is_active">Status</label>
            <select id="is_active" name="is_active" class="field">
                <option value="1" @selected(old('is_active', $safetyDomain->is_active))>Active</option>
                <option value="0" @selected(! old('is_active', $safetyDomain->is_active))>Inactive</option>
            </select>
            <p class="field-help">Inactive records remain in history.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="button">Save domain</button>
            <a href="{{ route('safety-domains.index') }}" class="button-secondary">Cancel</a>
        </div>
    </form>
</x-layout>
