<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    #[TestWith(['admin', 'Administrator'])]
    #[TestWith(['compliance_officer', 'Compliance Officer'])]
    #[TestWith(['regional_director', 'Regional Director'])]
    #[TestWith(['facility_manager', 'Facility Manager'])]
    #[TestWith(['staff', 'Staff'])]
    #[TestWith(['auditor', 'API Auditor'])]
    public function test_stored_values_have_clear_user_facing_labels(string $value, string $label): void
    {
        $this->assertSame($label, UserRole::from($value)->label());
    }
}
