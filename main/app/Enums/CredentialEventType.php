<?php

namespace App\Enums;

enum CredentialEventType: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
