<x-layout title="Sign in">
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold sm:text-3xl">Sign in to CheckPoint</h1>
        <form method="POST" action="{{ route('login.store') }}" class="panel mt-6 space-y-5">
            @csrf
            <x-input name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <x-input name="password" label="Password" type="password" autocomplete="current-password" required />
            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-teal-800"> Remember me</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-teal-800 hover:underline">Forgot password?</a>
            </div>
            <button class="button w-full" type="submit">Sign in</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-600">New here? <a class="font-semibold text-teal-800 hover:underline" href="{{ route('register') }}">Create an account</a></p>
    </div>
</x-layout>
