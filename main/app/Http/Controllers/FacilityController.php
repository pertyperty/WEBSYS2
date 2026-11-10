<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFacilityRequest;
use App\Models\Facility;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function index(): View
    {
        return view('facilities.index', [
            'facilities' => Facility::query()->with('region')->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('facilities.form', [
            'facility' => new Facility(['is_active' => true]),
            'regions' => Region::active()->orderBy('name')->get(),
        ]);
    }

    public function store(SaveFacilityRequest $request): RedirectResponse
    {
        Facility::create($request->validated());

        return to_route('facilities.index')->with('status', 'Record created.');
    }

    public function edit(Facility $facility): View
    {
        return view('facilities.form', [
            'facility' => $facility,
            'regions' => Region::active()->orderBy('name')->get(),
        ]);
    }

    public function update(SaveFacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());

        return to_route('facilities.index')->with('status', 'Record updated.');
    }
}
