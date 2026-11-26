<x-layout title="Token issued">
    <div class="page-heading">
        <div>
            <h1 class="text-2xl font-bold sm:text-3xl">Token issued</h1>
            <p class="page-intro">{{ $tokenName }} &middot; expires {{ $expiresAt->copy()->subSecond()->timezone('Asia/Manila')->format('M j, Y') }}</p>
        </div>
    </div>
    <section class="panel max-w-2xl space-y-5" x-data="{ copied: false, copy() { const el = this.$refs.token; el.select(); navigator.clipboard?.writeText(el.value).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } }">
        <p class="notice notice-warning">Copy this token now and share it privately. It will not be shown again.</p>
        <div>
            <label for="issued-token">Access token</label>
            <textarea x-ref="token" id="issued-token" class="field font-mono text-sm" rows="3" readonly spellcheck="false" autocomplete="off">{{ $plainTextToken }}</textarea>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" class="button" @click="copy()"><x-icon name="copy" class="size-4" /><span x-text="copied ? 'Copied' : 'Copy token'">Copy token</span></button>
            <a class="button-secondary" href="{{ route('auditor-tokens.index') }}">Done</a>
        </div>
    </section>
</x-layout>
