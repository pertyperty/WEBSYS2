<x-layout title="Auditor access">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">Auditor access</h1></div>
        <a class="button-secondary" href="{{ route('admin.users') }}">Manage accounts</a>
    </div>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0">
            <h2 class="mb-3 text-base font-semibold">Issued tokens</h2>
            <div class="record-list">
                @forelse($tokens as $token)
                    @php($regionName = $token->region_id ? ($tokenRegions[$token->region_id] ?? null) : null)
                    <article class="record-row flex flex-wrap items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-slate-900">{{ $token->name }}</h3>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $token->tokenable?->name ?? 'Unavailable account' }}@if($regionName) &middot; {{ $regionName }}@endif</p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Expires {{ $token->expires_at?->copy()->subSecond()->timezone('Asia/Manila')->format('M j, Y') ?? 'Not set' }}
                                &middot; Last used {{ $token->last_used_at?->timezone('Asia/Manila')->format('M j, Y g:i A') ?? 'Never' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2.5" x-data="{ confirming: false, ready: false }" x-init="ready = true">
                            <x-status-badge :status="$token->expires_at?->isPast() ? 'expired' : 'active'" />
                            <form method="POST" action="{{ route('auditor-tokens.destroy', $token->id) }}" class="flex items-center gap-2">
                                @csrf @method('DELETE')
                                <button class="button-secondary" type="submit" x-show="!confirming" @click="if (ready) { $event.preventDefault(); confirming = true; }" aria-label="Revoke {{ $token->name }}">Revoke</button>
                                <button class="button-danger" type="submit" x-show="confirming" x-cloak aria-label="Confirm revoke {{ $token->name }}">Confirm</button>
                                <button class="button-secondary" type="button" x-show="confirming" x-cloak @click="confirming = false">Cancel</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No access tokens" message="Issue a token to grant read-only regional verification access." />
                @endforelse
            </div>
            <div class="mt-6">{{ $tokens->links() }}</div>
        </section>
        <section class="panel self-start">
            <h2 class="text-base font-semibold">Issue a token</h2>
            @if($auditors->isEmpty())
                <p class="mt-3 text-sm text-slate-600">Assign an active auditor to a region in <a href="{{ route('admin.users') }}" class="font-semibold text-teal-800 hover:underline">Accounts &amp; access</a> first.</p>
            @else
                <form class="mt-4 space-y-4" method="POST" action="{{ route('auditor-tokens.store') }}" x-data="{ submitting: false, selectedRegion: '' }" @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
                    @csrf
                    <div>
                        <label for="auditor_id">Auditor and region</label>
                        <select class="field" id="auditor_id" name="auditor_id" required x-init="selectedRegion = $el.selectedOptions[0]?.dataset.region ?? ''" @change="selectedRegion = $event.target.selectedOptions[0]?.dataset.region ?? ''">
                            <option value="" disabled @selected(! old('auditor_id'))>Choose an auditor</option>
                            @foreach($auditors as $auditor)
                                <option value="{{ $auditor->id }}" data-region="{{ $auditor->region->name }}" @selected((string) old('auditor_id') === (string) $auditor->id)>{{ $auditor->name }} - {{ $auditor->region->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help" x-show="selectedRegion" x-cloak aria-live="polite">Region: <span x-text="selectedRegion"></span></p>
                    </div>
                    <x-input name="name" label="Token name" required maxlength="100" :value="old('name')" />
                    <x-input name="expires_on" label="Expiry date" type="date" required :min="now('Asia/Manila')->toDateString()" :max="now('Asia/Manila')->addDays(90)->toDateString()" :value="old('expires_on', now('Asia/Manila')->addDays(30)->toDateString())" />
                    <p class="field-help">Read-only regional API access, valid through expiry (max 90 days).</p>
                    <button class="button w-full" type="submit" :disabled="submitting">Issue token</button>
                </form>
            @endif
        </section>
    </div>
</x-layout>
