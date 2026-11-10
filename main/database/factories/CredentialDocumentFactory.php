<?php

namespace Database\Factories;

use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CredentialDocument> */
class CredentialDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => User::factory()->for(Facility::factory())->for(JobRole::factory()),
            'qualification_id' => Qualification::factory(),
            'facility_id' => fn (array $attributes): int => User::findOrFail($attributes['employee_id'])->facility_id,
            'job_role_id' => fn (array $attributes): int => User::findOrFail($attributes['employee_id'])->job_role_id,
            'reference' => (string) Str::uuid(),
            'private_path' => 'credentials/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 128,
            'issued_at' => now('Asia/Manila')->toDateString(),
            'expires_at' => now('Asia/Manila')->addYear()->toDateString(),
        ];
    }
}
