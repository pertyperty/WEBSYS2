<?php

namespace App\Enums;

enum CredentialStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
