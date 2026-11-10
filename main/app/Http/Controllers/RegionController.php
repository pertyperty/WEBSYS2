<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRegionRequest;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function index(): View
    {
        return view('regions.index', [
            'regions' => Region::query()->orderBy('name')->orderBy('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('regions.form', [
            'region' => new Region(['is_active' => true]),
        ]);
    }

    public function store(SaveRegionRequest $request): RedirectResponse
    {
        Region::create($request->validated());

        return to_route('regions.index')->with('status', 'Record created.');
    }

    public function edit(Region $region): View
    {
        return view('regions.form', [
            'region' => $region,
        ]);
    }

    public function update(SaveRegionRequest $request, Region $region): RedirectResponse
    {
        $region->update($request->validated());

        return to_route('regions.index')->with('status', 'Record updated.');
    }
}
