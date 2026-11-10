<x-layout title="My profile">
    <h1 class="text-3xl font-bold">My profile</h1>
    <p class="mb-8 mt-3 text-sm text-slate-600">Keep your contact details and password up to date.</p>
    <div class="grid gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="panel space-y-5">
            @csrf @method('PUT')
            <h2 class="text-lg font-semibold">Personal information</h2>
            <x-input name="name" label="Full name" bag="updateProfileInformation" :value="auth()->user()->name" autocomplete="name" required />
            <x-input name="email" label="Email address" bag="updateProfileInformation" type="email" :value="auth()->user()->email" autocomplete="username" required />
            <button class="button" type="submit">Save profile</button>
        </form>
        <form method="POST" action="{{ route('user-password.update') }}" class="panel space-y-5">
            @csrf @method('PUT')
            <h2 class="text-lg font-semibold">Change password</h2>
            <x-input name="current_password" label="Current password" bag="updatePassword" type="password" autocomplete="current-password" required />
            <x-input name="password" label="New password" bag="updatePassword" type="password" autocomplete="new-password" required minlength="12" />
            <p class="text-xs text-slate-600">At least 12 characters, including letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm new password" bag="updatePassword" type="password" autocomplete="new-password" required />
            <button class="button" type="submit">Update password</button>
        </form>
    </div>
</x-layout>
