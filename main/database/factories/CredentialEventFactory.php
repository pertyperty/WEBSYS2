<?php

namespace Database\Factories;

use App\Enums\CredentialEventType;
use App\Models\CredentialDocument;
use App\Models\CredentialEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CredentialEvent> */
class CredentialEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'credential_document_id' => CredentialDocument::factory(),
            'actor_id' => fn (array $attributes): int => CredentialDocument::findOrFail($attributes['credential_document_id'])->employee_id,
            'type' => CredentialEventType::Submitted,
            'note' => null,
        ];
    }

    public function submittedBy(User $employee): static
    {
        return $this->for($employee, 'actor');
    }

    public function approvedBy(User $officer): static
    {
        return $this->for($officer, 'actor')->state(fn (): array => ['type' => CredentialEventType::Approved]);
    }

    public function rejectedBy(User $officer): static
    {
        return $this->for($officer, 'actor')->state(fn (): array => [
            'type' => CredentialEventType::Rejected,
            'note' => 'Replace the unreadable evidence with a clear copy.',
        ]);
    }
}
