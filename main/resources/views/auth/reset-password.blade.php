<x-layout title="Choose a password">
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-3xl font-bold">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="panel space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-input name="email" label="Email address" type="email" :value="$request->email" autocomplete="username" required />
            <x-input name="password" label="New password" type="password" autocomplete="new-password" required minlength="12" />
            <p class="text-xs text-slate-600">At least 12 characters, including letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <button class="button w-full" type="submit">Reset password</button>
        </form>
    </div>
</x-layout>
