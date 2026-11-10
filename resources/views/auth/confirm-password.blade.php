<x-layout title="Confirm password">
    <form method="POST" action="{{ route('password.confirm.store') }}" class="panel mx-auto max-w-md space-y-5">
        @csrf
        <h1 class="text-2xl font-bold">Confirm your password</h1>
        <x-input name="password" label="Current password" type="password" autocomplete="current-password" required autofocus />
        <button class="button" type="submit">Confirm</button>
    </form>
</x-layout>
