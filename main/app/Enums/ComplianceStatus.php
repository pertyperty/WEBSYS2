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
            self::Eligible => 'Eligible',
            self::NotEligible => 'Not eligible',
            self::NotAssessable => 'Not assessable',
        };
    }
}
