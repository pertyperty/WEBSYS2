<?php

namespace Tests\Unit;

use App\Enums\ComplianceStatus;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ComplianceStatusTest extends TestCase
{
    #[TestWith(['eligible', 'Eligible'])]
    #[TestWith(['not_eligible', 'Not eligible'])]
    #[TestWith(['not_assessable', 'Not assessable'])]
    public function test_stored_values_have_clear_user_facing_labels(string $value, string $label): void
    {
        $this->assertSame($label, ComplianceStatus::from($value)->label());
    }

    #[TestWith(['eligible', 'Cleared for duty'])]
    #[TestWith(['not_eligible', 'Restricted from duty'])]
    #[TestWith(['not_assessable', 'Duty status pending'])]
    public function test_duty_labels_match_compliance_status(string $value, string $label): void
    {
        $this->assertSame($label, ComplianceStatus::from($value)->dutyLabel());
    }
}
