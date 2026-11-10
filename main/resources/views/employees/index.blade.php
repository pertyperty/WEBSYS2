<x-layout title="Employees">
    <h1 class="text-3xl font-bold">Employees</h1>
    <p class="mb-8 mt-3 text-sm text-slate-600">Only staff within your account's assigned scope appear here.</p>
    <div class="space-y-4">
        @forelse($employees as $employee)
            <section class="panel flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold">{{ $employee->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $employee->facility?->name ?? 'No facility' }} /
                        {{ $employee->facility?->region?->name ?? 'No region' }} /
                        {{ $employee->jobRole?->name ?? 'No job' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $employee->is_active ? 'Active account' : 'Inactive account' }}</p>
                </div>
                <a href="{{ route('employees.show', $employee) }}" class="text-sm font-semibold text-teal-800 underline">View requirements</a>
            </section>
        @empty
            <p class="panel text-sm text-slate-600">No employees in your assigned scope.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $employees->links() }}</div>
</x-layout>
