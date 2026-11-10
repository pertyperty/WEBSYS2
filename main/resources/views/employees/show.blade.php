<x-layout title="Employee compliance">
    <a href="{{ route('employees.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to employees</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $employee->name }}</h1>
    <p class="mt-3 text-sm text-slate-600">
        {{ $employee->facility?->name ?? 'No facility assigned' }} /
        {{ $employee->jobRole?->name ?? 'No job assigned' }}
    </p>
    <section class="panel mt-8">
        <h2 class="text-xl font-semibold">{{ \App\Enums\ComplianceStatus::from($assessment['status'])->label() }}</h2>
        <p class="mt-2 text-sm">{{ $assessment['satisfied_count'] }} / {{ $assessment['required_count'] }} required qualifications satisfied. {{ $assessment['percentage'] === null ? 'No percentage: not assessable.' : $assessment['percentage'].'%' }}</p>
        @if($assessment['reason'])<p class="mt-3 text-sm text-amber-800">{{ $assessment['reason'] }}</p>@endif
        <p class="mt-3 text-xs text-slate-500">Assessed {{ $assessment['assessed_on'] }} in Asia/Manila. Evidence remains valid through its expiry date; warnings start 30 days before expiry.</p>
        <a class="mt-4 inline-block text-sm font-semibold text-teal-800 underline" href="{{ route('employees.compliance-history', $employee) }}">View assessment history</a>
        <ul class="mt-5 space-y-3">
            @foreach($assessment['items'] as $item)
                <li class="border-t border-slate-100 pt-3"><strong>{{ $item['name'] }}: {{ ucfirst($item['state']) }}</strong>
                    @if($item['requires_training'])<span class="block text-sm">Training {{ $item['training_completed'] ? 'completed' : 'incomplete' }}</span>@endif
                    @if($item['expiring_soon'])<span class="block text-sm text-amber-800">Expiring soon: {{ $item['expires_at'] }}</span>@endif
                    @if($item['pending_count'])<span class="block text-sm text-slate-600">{{ $item['pending_count'] }} pending submission(s); valid approvals remain effective.</span>@endif
                </li>
            @endforeach
        </ul>
    </section>
    <section class="panel mt-8">
        <h2 class="text-lg font-semibold">Mandatory job qualifications</h2>
        <p class="mb-5 mt-2 text-sm text-slate-600">These are requirements, not awarded qualifications. Training is recorded in Training progress. The assessment below checks current approved evidence and required training.</p>
        @if(! $employee->facility_id || ! $employee->job_role_id)
            <p class="mb-4 text-sm font-semibold text-amber-800">Assignment incomplete: this employee cannot be assessed.</p>
        @endif
        <ul class="space-y-4">
            @forelse($employee->jobRole?->qualifications ?? [] as $qualification)
                <li class="border-t border-slate-100 pt-4">
                    <p class="font-semibold">{{ $qualification->name }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $qualification->safetyDomain->name }} /
                        {{ $qualification->requires_training ? 'Training completion required' : 'Evidence without a course' }} /
                        {{ $qualification->requires_expiry ? 'Expiry date required' : 'Expiry date optional' }}
                    </p>
                </li>
            @empty
                <li class="text-sm text-slate-600">No requirements configured. This does not mean the employee is compliant.</li>
            @endforelse
        </ul>
    </section>
</x-layout>
