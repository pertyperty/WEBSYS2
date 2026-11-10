<x-layout title="Dashboard">
    <p class="text-sm font-semibold uppercase tracking-wide text-teal-800">{{ auth()->user()->role->label() }}</p>
    <h1 class="mt-2 text-3xl font-bold">Compliance dashboard</h1>
    <p class="mt-3 text-sm text-slate-600">Live assessment as of {{ $assessedOn }} (Asia/Manila). Counts cover staff in your current scope, including inactive accounts.</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach(['eligible' => 'Eligible', 'not_eligible' => 'Not eligible', 'not_assessable' => 'Not assessable', 'employees' => 'Staff in scope'] as $key => $label)
            <section class="panel"><h2 class="text-sm text-slate-600">{{ $label }}</h2><p class="mt-2 text-3xl font-bold">{{ $totals[$key] }}</p></section>
        @endforeach
    </div>
    <section class="panel mt-6">
        <h2 class="text-lg font-semibold">Eligibility rate: {{ $totals['eligible_rate'] === null ? 'Not assessable' : $totals['eligible_rate'].'%' }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $totals['eligible'] }} eligible / {{ $totals['assessable'] }} assessable staff. Not-assessable employees are excluded from this rate, never treated as compliant.</p>
        <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach(['missing' => 'Missing requirements', 'expired' => 'Expired requirements', 'expiring_soon' => 'Valid evidence expiring within 30 days', 'training_incomplete' => 'Required training incomplete'] as $key => $label)
                <div><dt class="text-sm text-slate-600">{{ $label }}</dt><dd class="mt-1 text-xl font-bold">{{ $totals[$key] }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-4 text-xs text-slate-500">Requirement counts above include assessable employees only. Each count represents an employee/qualification pair.</p>
    </section>
    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <section class="panel">
            <h2 class="text-lg font-semibold">Work in progress</h2>
            <p class="mt-3 text-sm">Pending submissions: <strong>{{ $totals['pending_documents'] }}</strong> (all scoped staff, including optional evidence).</p>
            <p class="mt-3 text-sm">Training enrollments: {{ $totals['enrolled'] }} enrolled / {{ $totals['completed'] }} completed.</p>
            <p class="mt-3 text-sm text-slate-600">Training completion alone does not grant compliance. Approved, valid evidence must support each required qualification.</p>
            @can('view-employees')
                <div class="mt-5 flex flex-wrap gap-4"><a class="text-sm font-semibold text-teal-800 underline" href="{{ route('qualification-matrix') }}">Qualification matrix</a><a class="text-sm font-semibold text-teal-800 underline" href="{{ route('employees.index') }}">Employee assessments</a></div>
            @endcan
        </section>
        <section class="panel">
            <h2 class="text-lg font-semibold">Account details</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Role</dt><dd>{{ auth()->user()->role->label() }}</dd></div>
                @if(auth()->user()->facility)<div><dt class="text-slate-500">Facility</dt><dd>{{ auth()->user()->facility->name }}</dd></div>@endif
                @if(auth()->user()->jobRole)<div><dt class="text-slate-500">Job</dt><dd>{{ auth()->user()->jobRole->name }}</dd></div>@endif
            </dl>
            @can('manage-users')<a href="{{ route('admin.users') }}" class="button mt-5">Manage accounts</a>@endcan
        </section>
    </div>
</x-layout>
