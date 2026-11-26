<?php

namespace App\Http\Controllers;

use App\Actions\CaptureAudit;
use App\Enums\UserRole;
use App\Http\Requests\StoreAuditRequest;
use App\Models\Audit;
use App\Models\Facility;
use App\Models\Region;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';

        return view('audits.index', ['audits' => Audit::visibleTo($request->user())->with('author')->withCount('findings')
            ->when($search !== '%%', fn (Builder $query): Builder => $query->where(fn (Builder $names): Builder => $names
                ->where('title', 'like', $search)->orWhere('snapshot->facility_name', 'like', $search)->orWhere('snapshot->region_name', 'like', $search)))
            ->latest('id')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        Gate::authorize('conduct-audits');

        return view('audits.create', ['regions' => Region::active()->orderBy('name')->get(), 'facilities' => Facility::active()->orderBy('name')->get()]);
    }

    public function store(StoreAuditRequest $request, CaptureAudit $capture): RedirectResponse
    {
        $audit = $capture->handle($request->user(), $request->validated());

        return to_route('audits.show', $audit)->with('status', 'Audit captured. Add findings to assign follow-up work.');
    }

    public function show(Request $request, int $audit): View
    {
        $audit = Audit::visibleTo($request->user())->with(['author', 'findings.assignee', 'findings.facility'])->findOrFail($audit);
        $facilities = Facility::where('region_id', $audit->region_id)->when($audit->facility_id, fn ($query) => $query->whereKey($audit->facility_id))->orderBy('name')->get();
        $managers = $request->user()->can('conduct-audits')
            ? User::where('role', UserRole::FacilityManager)->where('is_active', true)->whereIn('facility_id', $facilities->modelKeys())->orderBy('name')->get(['id', 'name', 'facility_id']) : collect();

        return view('audits.show', compact('audit', 'facilities', 'managers'));
    }
}
