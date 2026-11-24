<x-layout title="Sign in">
    <div class="mx-auto max-w-md">
        <div class="text-center">
            <div class="mb-4 flex justify-center">
                <span class="brand-mark size-12"><x-icon name="check" class="size-6" /></span>
            </div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-teal-800">Workforce readiness</p>
            <h1 class="text-3xl font-bold">Welcome to CheckPoint</h1>
            <p class="mb-7 mt-3 text-sm text-slate-600">Sign in to manage your safety compliance records.</p>
        </div>
        <form method="POST" action="{{ route('login.store') }}" class="panel space-y-5 ring-1 ring-slate-200" x-data="{ togglePassword(id, isVisible) { document.getElementById(id).type = isVisible ? 'text' : 'password' } }">
            @csrf
            <x-input name="email" label="Email address" type="email" autocomplete="username" required autofocus />
            <x-input name="password" label="Password" type="password" autocomplete="current-password" required />
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" class="size-4 rounded border-slate-300 text-teal-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-800" x-on:change="togglePassword('password', $event.target.checked)"> Show password</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-teal-800"> Remember me</label>
            <button class="button w-full" type="submit">Sign in</button>
            <a href="{{ route('password.request') }}" class="block text-center text-sm text-teal-800 underline">Forgot your password?</a>
        </form>
        <p class="mt-5 text-center text-sm">New here? <a class="text-teal-800 underline" href="{{ route('register') }}">Create a staff account</a></p>
    </div>
</x-layout>
