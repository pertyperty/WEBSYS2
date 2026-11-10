<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Enums\UserRole;
use App\Http\Requests\SaveQualificationRequest;
use App\Models\Qualification;
use App\Models\SafetyDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QualificationController extends Controller
{
    public function index(): View
    {
        return view('qualifications.index', [
            'qualifications' => Qualification::query()->with('safetyDomain')->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('qualifications.form', [
            'qualification' => new Qualification(['is_active' => true, 'requires_expiry' => true, 'requires_training' => false]),
            'domains' => SafetyDomain::active()->orderBy('name')->get(),
        ]);
    }

    public function store(SaveQualificationRequest $request): RedirectResponse
    {
        Qualification::create($request->validated());

        return to_route('qualifications.index')->with('status', 'Record created.');
    }

    public function edit(Qualification $qualification): View
    {
        return view('qualifications.form', [
            'qualification' => $qualification,
            'domains' => SafetyDomain::active()->orderBy('name')->get(),
        ]);
    }

    public function update(SaveQualificationRequest $request, Qualification $qualification, RecordComplianceAssessment $history): RedirectResponse
    {
        $qualification->update($request->validated());
        foreach (User::where('role', UserRole::Staff)->whereHas('jobRole.qualifications', fn (Builder $query): Builder => $query->whereKey($qualification->id))->lazyById(100) as $employee) {
            $history->handle($employee, 'qualification', $request->user());
        }

        return to_route('qualifications.index')->with('status', 'Record updated.');
    }
}
