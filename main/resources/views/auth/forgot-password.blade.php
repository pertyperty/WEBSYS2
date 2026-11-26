<x-layout title="Reset password">
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold sm:text-3xl">Reset password</h1>
        <p class="page-intro">Enter your email to receive a reset link.</p>
        <form method="POST" action="{{ route('password.email') }}" class="panel mt-6 space-y-5">
            @csrf
            <x-input name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <button class="button w-full" type="submit">Send reset link</button>
        </form>
    </div>
</x-layout>
