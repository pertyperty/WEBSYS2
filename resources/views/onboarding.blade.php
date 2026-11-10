<x-layout title="Awaiting assignment">
    <div class="panel max-w-2xl border-t-4 border-t-teal-700">
        <p class="text-xs font-semibold uppercase tracking-widest text-teal-800">Account created</p>
        <h1 class="mt-3 text-3xl font-bold">Awaiting your assignment</h1>
        <p class="mt-4 text-slate-600">Welcome, {{ auth()->user()->name }}. An administrator must assign your facility and job before you can access employee records, enroll in training, or submit credentials.</p>
        <p class="mt-4 text-sm text-slate-600">You can update your personal profile while you wait. Contact your administrator for your assignment.</p>
        <a href="{{ route('profile') }}" class="button mt-6">View my profile</a>
    </div>
</x-layout>
