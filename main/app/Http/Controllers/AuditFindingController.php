<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreAuditFindingRequest;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuditFindingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'pending_verification', 'resolved', 'overdue'])],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';
        $query = AuditFinding::visibleTo($request->user())->with(['assignee', 'facility'])
            ->when($search !== '%%', fn (Builder $query): Builder => $query->where(fn (Builder $names): Builder => $names
                ->where('title', 'like', $search)
                ->orWhereHas('facility', fn (Builder $facilities): Builder => $facilities->where('name', 'like', $search))
                ->orWhereHas('assignee', fn (Builder $managers): Builder => $managers->where('name', 'like', $search))));
        if (($filters['status'] ?? '') === 'overdue') {
            $query->where('status', '!=', 'resolved')->where('due_on', '<', now('Asia/Manila')->toDateString());
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return view('findings.index', ['findings' => $query->orderBy('due_on')->orderBy('id')->paginate(15)->withQueryString()]);
    }

    public function store(StoreAuditFindingRequest $request, int $audit): RedirectResponse
    {
        $finding = DB::transaction(function () use ($request, $audit): AuditFinding {
            $data = $request->validated();
            $audit = Audit::findOrFail($audit);
            $manager = User::lockForUpdate()->findOrFail($data['assignee_id']);
            $facility = Facility::findOrFail($data['facility_id']);
            if (! $manager->is_active || $manager->role !== UserRole::FacilityManager || $manager->facility_id !== $facility->id
                || $facility->region_id !== $audit->region_id || ($audit->facility_id && $audit->facility_id !== $facility->id)) {
                throw ValidationException::withMessages(['assignee_id' => 'Choose a current manager in the audit facility.']);
            }

            return $audit->findings()->create([...$data, 'region_id' => $audit->region_id]);
        });

        return to_route('findings.show', $finding)->with('status', 'Finding assigned.');
    }

    public function show(Request $request, int $finding): View
    {
        $finding = AuditFinding::visibleTo($request->user())->with(['audit', 'assignee', 'facility', 'updates.author'])->findOrFail($finding);

        $managers = $request->user()->can('conduct-audits') ? User::where('role', UserRole::FacilityManager)->where('is_active', true)
            ->where('facility_id', $finding->facility_id)->orderBy('name')->get(['id', 'name']) : collect();

        return view('findings.show', compact('finding', 'managers'));
    }
}
