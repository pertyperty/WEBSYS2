<x-layout title="Choose a password">
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold sm:text-3xl">Set a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="panel mt-6 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-input name="email" label="Email address" type="email" :value="$request->email" autocomplete="username" required />
            <x-input name="password" label="New password" type="password" autocomplete="new-password" required minlength="12" aria-describedby="reset-password-help" />
            <p id="reset-password-help" class="field-help -mt-3">12+ characters with letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <button class="button w-full" type="submit">Reset password</button>
        </form>
    </div>
</x-layout>
