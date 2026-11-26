<?php

namespace App\Enums;

enum ComplianceStatus: string
{
    case Eligible = 'eligible';
    case NotEligible = 'not_eligible';
    case NotAssessable = 'not_assessable';

    public function label(): string
    {
        return match ($this) {
            self::Eligible => 'Requirements met',
            self::NotEligible => 'Requirements not met',
            self::NotAssessable => 'Assessment incomplete',
        };
    }

    public function dutyLabel(): string
    {
        return $this->label();
    }
}
