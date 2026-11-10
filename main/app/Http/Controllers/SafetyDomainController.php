<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveSafetyDomainRequest;
use App\Models\SafetyDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SafetyDomainController extends Controller
{
    public function index(): View
    {
        return view('safety-domains.index', [
            'domains' => SafetyDomain::query()->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('safety-domains.form', [
            'safetyDomain' => new SafetyDomain(['is_active' => true]),
        ]);
    }

    public function store(SaveSafetyDomainRequest $request): RedirectResponse
    {
        SafetyDomain::create($request->validated());

        return to_route('safety-domains.index')->with('status', 'Record created.');
    }

    public function edit(SafetyDomain $safetyDomain): View
    {
        return view('safety-domains.form', [
            'safetyDomain' => $safetyDomain,
        ]);
    }

    public function update(SaveSafetyDomainRequest $request, SafetyDomain $safetyDomain): RedirectResponse
    {
        $safetyDomain->update($request->validated());

        return to_route('safety-domains.index')->with('status', 'Record updated.');
    }
}
