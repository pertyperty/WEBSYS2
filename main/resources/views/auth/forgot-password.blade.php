<x-layout title="Reset password">
    <div class="mx-auto max-w-md">
        <h1 class="mb-4 text-3xl font-bold">Forgot your password?</h1>
        <p class="mb-6 text-sm text-slate-600">Enter your email address to request a password-reset link.</p>
        <form method="POST" action="{{ route('password.email') }}" class="panel space-y-5">
            @csrf
            <x-input name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <button class="button w-full" type="submit">Request reset link</button>
        </form>
    </div>
</x-layout>
