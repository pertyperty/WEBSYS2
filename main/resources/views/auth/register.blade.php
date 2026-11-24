<x-layout title="Create account">
    <div class="mx-auto max-w-md">
        <div class="text-center">
            <div class="mb-4 flex justify-center">
                <span class="brand-mark size-12"><x-icon name="check" class="size-6" /></span>
            </div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-teal-800">Staff registration</p>
            <h1 class="text-3xl font-bold">Create your staff account</h1>
            <p class="notice notice-warning mb-7 mt-3 justify-center text-center">An administrator will assign your facility and job before employee features become available.</p>
        </div>
        <form method="POST" action="{{ route('register.store') }}" class="panel space-y-5 ring-1 ring-slate-200" x-data="{ togglePassword(id, isVisible) { document.getElementById(id).type = isVisible ? 'text' : 'password' } }">
            @csrf
            <x-input name="name" label="Full name" autocomplete="name" required autofocus maxlength="255" />
            <x-input name="email" label="Email address" type="email" autocomplete="username" required maxlength="255" />
            <x-input name="password" label="Password" type="password" autocomplete="new-password" required minlength="12" aria-describedby="password-help" />
            <p id="password-help" class="text-xs text-slate-600">Use at least 12 characters, including letters and numbers.</p>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" class="size-4 rounded border-slate-300 text-teal-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-800" x-on:change="togglePassword('password', $event.target.checked)"> Show password</label>
            <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" class="size-4 rounded border-slate-300 text-teal-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-800" x-on:change="togglePassword('password_confirmation', $event.target.checked)"> Show password</label>
            <button class="button w-full" type="submit">Create account</button>
        </form>
    </div>
</x-layout>
