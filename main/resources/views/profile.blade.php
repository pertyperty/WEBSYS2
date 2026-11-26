<x-layout title="My profile">
    <div class="page-heading">
        <div><h1 class="text-2xl font-bold sm:text-3xl">My profile</h1></div>
    </div>
    <div class="grid gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="panel space-y-5">
            @csrf @method('PUT')
            <h2 class="text-lg font-semibold">Personal details</h2>
            <x-input name="name" label="Full name" bag="updateProfileInformation" :value="auth()->user()->name" autocomplete="name" required />
            <x-input name="email" label="Email address" bag="updateProfileInformation" type="email" :value="auth()->user()->email" autocomplete="username" required />
            <button class="button" type="submit">Save changes</button>
        </form>
        <form method="POST" action="{{ route('user-password.update') }}" class="panel space-y-5">
            @csrf @method('PUT')
            <h2 class="text-lg font-semibold">Password</h2>
            <x-input name="current_password" label="Current password" bag="updatePassword" type="password" autocomplete="current-password" required />
            <x-input name="password" label="New password" bag="updatePassword" type="password" autocomplete="new-password" required minlength="12" aria-describedby="profile-password-help" />
            <p id="profile-password-help" class="field-help -mt-3">12+ characters with letters and numbers.</p>
            <x-input name="password_confirmation" label="Confirm new password" bag="updatePassword" type="password" autocomplete="new-password" required />
            <button class="button" type="submit">Update password</button>
        </form>
    </div>
</x-layout>
