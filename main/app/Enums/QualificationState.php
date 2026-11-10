<?php

namespace App\Enums;

enum QualificationState: string
{
    case Satisfied = 'satisfied';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Missing = 'missing';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
