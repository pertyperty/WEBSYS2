<x-layout title="Create account">
    <div class="mx-auto max-w-md">
        <h1 class="text-3xl font-bold">Create your staff account</h1>
        <p class="mb-7 mt-3 text-sm text-slate-600">An administrator will assign your facility and job before employee features become available.</p>
        <form method="POST" action="{{ route('register.store') }}" class="panel space-y-5">
            @csrf
            <x-input name="name" label="Full name" autocomplete="name" required autofocus maxlength="255" />
            <x-input name="email" label="Email address" type="email" autocomplete="username" required maxlength="255" />
            <x-input name="password" label="Password" type="password" autocomplete="new-password" required minlength="12" aria-describedby="password-help" />
            <p id="password-help" class="text-xs text-slate-600">Use at least 12 characters, including letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <button class="button w-full" type="submit">Create account</button>
        </form>
    </div>
</x-layout>
