<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case ComplianceOfficer = 'compliance_officer';
    case RegionalDirector = 'regional_director';
    case FacilityManager = 'facility_manager';
    case Staff = 'staff';
    case Auditor = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::ComplianceOfficer => 'Compliance Officer',
            self::RegionalDirector => 'Regional Director',
            self::FacilityManager => 'Facility Manager',
            self::Staff => 'Staff',
            self::Auditor => 'API Auditor',
        };
    }
}
