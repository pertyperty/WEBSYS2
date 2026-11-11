<x-layout title="Token issued">
    <div class="page-heading"><div><h1 class="text-3xl font-bold">Token issued</h1><p class="page-intro">{{ $tokenName }} expires {{ $expiresAt->copy()->subSecond()->timezone('Asia/Manila')->format('M j, Y') }}.</p></div></div>
    <section class="panel max-w-2xl space-y-5">
        <p class="text-sm text-slate-700">Copy this token now and deliver it privately to the authorized auditor. It cannot be shown again. If it is lost or exposed, revoke it and issue a replacement.</p>
        <div>
            <label for="issued-token" class="block text-sm font-medium">Access token</label>
            <textarea id="issued-token" class="field font-mono text-sm" rows="3" readonly spellcheck="false" autocomplete="off" aria-describedby="token-copy-help">{{ $plainTextToken }}</textarea>
            <p id="token-copy-help" class="field-help">Select the token and copy it. Keep it out of screenshots, messages, and source control.</p>
        </div>
        <a class="button" href="{{ route('auditor-tokens.index') }}">Return to auditor access</a>
    </section>
</x-layout>
