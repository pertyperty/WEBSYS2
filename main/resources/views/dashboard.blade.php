<x-layout title="Dashboard">
    <p class="text-xs font-semibold uppercase tracking-widest text-teal-800">{{ auth()->user()->role->label() }}</p>
    <h1 class="mt-2 text-3xl font-bold">Welcome, {{ auth()->user()->name }}</h1>
    <p class="mt-3 text-slate-600">Your account foundation is ready. Compliance and training modules will be added in the next milestones.</p>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <section class="panel">
            <h2 class="text-lg font-semibold">Account details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Role</dt><dd>{{ auth()->user()->role->label() }}</dd></div>
                @if(auth()->user()->facility)<div><dt class="text-slate-500">Facility</dt><dd>{{ auth()->user()->facility->name }}</dd></div>@endif
                @if(auth()->user()->jobRole)<div><dt class="text-slate-500">Job</dt><dd>{{ auth()->user()->jobRole->name }}</dd></div>@endif
            </dl>
        </section>
        <section class="panel">
            <h2 class="text-lg font-semibold">Next steps</h2>
            @can('manage-users')
                <p class="my-4 text-sm text-slate-600">Review registered accounts and assign roles, facilities, and jobs.</p>
                <a href="{{ route('admin.users') }}" class="button">Manage accounts</a>
            @else
                <p class="mt-4 text-sm text-slate-600">No compliance assessment has been implemented yet. This page does not indicate eligibility for active duty.</p>
            @endcan
        </section>
    </div>
</x-layout>
