<x-layout title="Sign in">
    <div class="mx-auto max-w-md">
        <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-teal-800">Workforce readiness</p>
        <h1 class="text-3xl font-bold">Welcome to CheckPoint</h1>
        <p class="mb-7 mt-3 text-sm text-slate-600">Sign in to manage your safety compliance records.</p>
        <form method="POST" action="{{ route('login.store') }}" class="panel space-y-5">
            @csrf
            <x-input name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <x-input name="password" label="Password" type="password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-teal-800"> Remember me</label>
            <button class="button w-full" type="submit">Sign in</button>
            <a href="{{ route('password.request') }}" class="block text-center text-sm text-teal-800 underline">Forgot your password?</a>
        </form>
        <p class="mt-5 text-center text-sm">New here? <a class="text-teal-800 underline" href="{{ route('register') }}">Create a staff account</a></p>
    </div>
</x-layout>
