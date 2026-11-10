<x-layout title="Qualification matrix">
    <h1 class="text-3xl font-bold">Qualification matrix</h1>
    <p class="mt-3 text-sm text-slate-600">Compare requirements across your team. N/A means the qualification is not required for that job.</p>
    <div class="panel mt-8 overflow-x-auto" role="region" aria-label="Scrollable qualification matrix" tabindex="0">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Scoped employee compliance by required qualification</caption>
            <thead><tr class="border-b border-slate-200"><th scope="col" class="sticky left-0 bg-white p-3">Employee / overall status</th>@foreach($qualifications as $qualification)<th scope="col" class="p-3">{{ $qualification['name'] }}<span class="block font-normal text-slate-500">{{ $qualification['domain'] }}</span></th>@endforeach</tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    @php($result = $assessments[$employee->id])
                    <tr class="border-b border-slate-100">
                        <th scope="row" class="sticky left-0 min-w-48 bg-white p-3"><a href="{{ route('employees.show', $employee) }}" class="text-teal-800 underline">{{ $employee->name }}</a><div class="mt-2"><x-status-badge :status="$result['status']" /></div>@if($result['reason'])<details class="mt-2 text-xs font-normal text-slate-500"><summary class="cursor-pointer text-amber-800">Why not assessable?</summary><p class="mt-2">{{ $result['reason'] }}</p></details>@endif</th>
                        @foreach($qualifications as $qualification)
                            @php($item = $result['items'][$qualification['qualification_id']] ?? null)
                            <td class="p-3 align-top">
                                @if($item)
                                    <x-status-badge :status="$item['state']" />
                                    @if($item['requires_training'] && ! $item['training_completed'])<span class="mt-1 block text-amber-800">Training incomplete</span>@endif
                                    @if($item['expiring_soon'])<span class="mt-1 block text-amber-800">Expires {{ $item['expires_at'] }} (within 30 days)</span>@endif
                                @else<span class="text-slate-500">N/A</span>@endif
                            </td>
                        @endforeach
                    </tr>
                @empty<tr><td class="p-3" colspan="{{ $qualifications->count() + 1 }}">No employees in your scope.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $employees->links() }}</div>
</x-layout>
