<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class IssueAuditorToken
{
    /** @param array{auditor_id: int|string, name: string, expires_on: string} $data */
    public function handle(User $admin, array $data): NewAccessToken
    {
        Gate::forUser($admin)->authorize('manage-users');

        return DB::transaction(function () use ($data): NewAccessToken {
            $auditor = User::with('region')->lockForUpdate()->findOrFail($data['auditor_id']);
            if (! $auditor->is_active || $auditor->role !== UserRole::Auditor || ! $auditor->region?->is_active) {
                throw ValidationException::withMessages(['auditor_id' => 'Choose an active auditor assigned to an active region.']);
            }
            $expiresAt = CarbonImmutable::parse($data['expires_on'], 'Asia/Manila')->addDay()->startOfDay()->utc();
            $token = $auditor->createToken($data['name'], ['verification:read'], $expiresAt);
            $token->accessToken->forceFill(['region_id' => $auditor->region_id])->save();

            return $token;
        });
    }
}
