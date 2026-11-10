<x-layout title="Qualification matrix">
    <h1 class="text-3xl font-bold">Qualification matrix</h1>
    <p class="mt-3 text-sm text-slate-600">Live required qualifications for this page of staff in your scope. N/A means the job does not require that qualification. Training and evidence are evaluated separately.</p>
    <div class="panel mt-8 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Scoped employee compliance by required qualification</caption>
            <thead><tr class="border-b border-slate-200"><th scope="col" class="p-3">Employee / overall status</th>@foreach($qualifications as $qualification)<th scope="col" class="p-3">{{ $qualification['name'] }}<span class="block font-normal text-slate-500">{{ $qualification['domain'] }}</span></th>@endforeach</tr></thead>
            <tbody>
                @forelse($employees as $employee)
                    @php($result = $assessments[$employee->id])
                    <tr class="border-b border-slate-100">
                        <th scope="row" class="p-3"><a href="{{ route('employees.show', $employee) }}" class="text-teal-800 underline">{{ $employee->name }}</a><span class="mt-1 block font-normal">{{ \App\Enums\ComplianceStatus::from($result['status'])->label() }}</span>@if($result['reason'])<span class="mt-1 block font-normal text-amber-800">{{ $result['reason'] }}</span>@endif</th>
                        @foreach($qualifications as $qualification)
                            @php($item = $result['items'][$qualification['qualification_id']] ?? null)
                            <td class="p-3 align-top">
                                @if($item)
                                    <span class="font-semibold">{{ ucfirst($item['state']) }}</span>
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
