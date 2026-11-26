<x-layout title="Create account">
    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold sm:text-3xl">Create staff account</h1>
        <p class="page-intro">An administrator will assign your facility and job after signup.</p>
        <form method="POST" action="{{ route('register.store') }}" class="panel mt-6 space-y-5">
            @csrf
            <x-input name="name" label="Full name" autocomplete="name" required autofocus maxlength="255" />
            <x-input name="email" label="Email address" type="email" autocomplete="username" required maxlength="255" />
            <x-input name="password" label="Password" type="password" autocomplete="new-password" required minlength="12" aria-describedby="password-help" />
            <p id="password-help" class="field-help -mt-3">12+ characters with letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <button class="button w-full" type="submit">Create account</button>
        </form>
    </div>
</x-layout>
