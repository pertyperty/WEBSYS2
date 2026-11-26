<?php

namespace Tests\Unit;

use App\Enums\ComplianceStatus;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ComplianceStatusTest extends TestCase
{
    #[TestWith(['eligible', 'Requirements met'])]
    #[TestWith(['not_eligible', 'Requirements not met'])]
    #[TestWith(['not_assessable', 'Assessment incomplete'])]
    public function test_stored_values_have_requirements_based_labels(string $value, string $label): void
    {
        $this->assertSame($label, ComplianceStatus::from($value)->label());
    }

    #[TestWith(['eligible', 'Requirements met'])]
    #[TestWith(['not_eligible', 'Requirements not met'])]
    #[TestWith(['not_assessable', 'Assessment incomplete'])]
    public function test_duty_labels_describe_compliance_not_deployment_authority(string $value, string $label): void
    {
        $this->assertSame($label, ComplianceStatus::from($value)->dutyLabel());
    }
}
