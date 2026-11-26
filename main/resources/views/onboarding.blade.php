<x-layout title="Awaiting assignment">
    <div class="panel max-w-xl border-t-4 border-t-teal-700">
        <p class="text-xs font-semibold uppercase tracking-widest text-teal-800">Pending setup</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Awaiting your assignment</h1>
        <p class="mt-3 text-sm text-slate-600">Hi {{ auth()->user()->name }}. An administrator needs to assign your facility and job before you can access compliance records, training, or evidence uploads.</p>
        <a href="{{ route('profile') }}" class="button mt-6">My profile</a>
    </div>
</x-layout>
