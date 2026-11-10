<x-layout title="Employee requirements">
    <a href="{{ route('employees.index') }}" class="text-sm font-semibold text-teal-800 underline">Back to employees</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $employee->name }}</h1>
    <p class="mt-3 text-sm text-slate-600">
        {{ $employee->facility?->name ?? 'No facility assigned' }} /
        {{ $employee->jobRole?->name ?? 'No job assigned' }}
    </p>
    <section class="panel mt-8">
        <h2 class="text-lg font-semibold">Mandatory job qualifications</h2>
        <p class="mb-5 mt-2 text-sm text-slate-600">These are requirements, not awarded qualifications. Evidence, training completion, and eligibility will be assessed in later milestones.</p>
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
