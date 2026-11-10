<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\Facility;
use App\Models\Region;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CaptureAudit
{
    public function __construct(private AssessEmployeeCompliance $assessment) {}

    /** @param array{title: string, region_id: int|string, facility_id?: int|string|null} $data */
    public function handle(User $author, array $data): Audit
    {
        Gate::forUser($author)->authorize('conduct-audits');

        return DB::transaction(function () use ($author, $data): Audit {
            $region = Region::active()->findOrFail($data['region_id']);
            $facility = empty($data['facility_id']) ? null : Facility::active()->where('region_id', $region->id)->findOrFail($data['facility_id']);
            $date = CarbonImmutable::now('Asia/Manila')->startOfDay();
            $employees = User::where('role', UserRole::Staff)
                ->whereHas('facility', fn ($query) => $query->where('region_id', $region->id))
                ->when($facility, fn ($query) => $query->where('facility_id', $facility->id))
                ->orderBy('id')->with(AssessEmployeeCompliance::RELATIONS)->get();
            if ($employees->isEmpty()) {
                throw ValidationException::withMessages(['region_id' => 'There are no employees in this scope to assess.']);
            }
            $rows = $employees->map(fn (User $employee): array => $this->assessment->handle($employee, $date))->all();

            return Audit::create([
                'title' => $data['title'], 'author_id' => $author->id,
                'region_id' => $region->id, 'facility_id' => $facility?->id, 'audited_on' => $date->toDateString(),
                'snapshot' => ['region_name' => $region->name, 'facility_name' => $facility?->name, 'employees' => $rows],
            ]);
        });
    }
}
