<x-layout title="Auditor access">
    <div class="page-heading">
        <div><h1 class="text-3xl font-bold">Auditor access</h1><p class="page-intro">Issue and revoke regional verification access.</p></div>
        <a class="button-secondary" href="{{ route('admin.users') }}">Manage auditor accounts</a>
    </div>
    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0">
            <h2 class="mb-4 text-lg font-semibold">Issued tokens</h2>
            <div class="record-list">
                @forelse($tokens as $token)
                    <article class="record-row flex flex-wrap items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-semibold">{{ $token->name }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $token->tokenable?->name ?? 'Unavailable account' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Expires {{ $token->expires_at?->copy()->subSecond()->timezone('Asia/Manila')->format('M j, Y') ?? 'Not configured' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Last used {{ $token->last_used_at?->timezone('Asia/Manila')->format('M j, Y g:i A') ?? 'Never' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-status-badge :status="$token->expires_at?->isPast() ? 'expired' : 'active'" />
                            <form method="POST" action="{{ route('auditor-tokens.destroy', $token->id) }}">
                                @csrf @method('DELETE')
                                <button class="button-secondary" type="submit" aria-label="Revoke {{ $token->name }}">Revoke</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No access tokens" message="Issue a token when a regional auditor needs verification access." />
                @endforelse
            </div>
            <div class="mt-6">{{ $tokens->links() }}</div>
        </section>
        <section class="panel self-start">
            <h2 class="text-lg font-semibold">Issue a token</h2>
            @if($auditors->isEmpty())
                <p class="mt-3 text-sm text-slate-600">Assign an active auditor to a region in Accounts & access first.</p>
            @else
                <form class="mt-5 space-y-5" method="POST" action="{{ route('auditor-tokens.store') }}" x-data="{ submitting: false, selectedRegion: '' }" @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
                    @csrf
                    <div>
                        <label for="auditor_id" class="block text-sm font-medium">Auditor and region</label>
                        <select class="field" id="auditor_id" name="auditor_id" required x-init="selectedRegion = $el.selectedOptions[0]?.dataset.region ?? ''" @change="selectedRegion = $event.target.selectedOptions[0]?.dataset.region ?? ''">
                            <option value="" disabled @selected(!old('auditor_id'))>Choose an auditor</option>
                            @foreach($auditors as $auditor)
                                <option value="{{ $auditor->id }}" data-region="{{ $auditor->region->name }}" @selected((string) old('auditor_id') === (string) $auditor->id)>{{ $auditor->name }} - {{ $auditor->region->name }}</option>
                            @endforeach
                        </select>
                        <p class="field-help" x-show="selectedRegion" x-cloak aria-live="polite">Verification region: <span x-text="selectedRegion"></span></p>
                    </div>
                    <x-input name="name" label="Token name" required maxlength="100" :value="old('name')" />
                    <x-input name="expires_on" label="Expiry date" type="date" required :min="now('Asia/Manila')->toDateString()" :max="now('Asia/Manila')->addDays(90)->toDateString()" :value="old('expires_on', now('Asia/Manila')->addDays(30)->toDateString())" />
                    <p class="text-sm text-slate-600">Read-only credential verification for the selected region. Valid through the expiry date, up to 90 days.</p>
                    <button class="button w-full" type="submit" :disabled="submitting">Issue token</button>
                </form>
            @endif
        </section>
    </div>
</x-layout>
