<?php

namespace App\Actions;

use DateTimeImmutable;
use DateTimeZone;

class IsCredentialDateValid
{
    public function handle(?string $issuedOn, ?string $expiresOn, bool $requiresExpiry, DateTimeImmutable $asOf): bool
    {
        $today = $asOf->setTimezone(new DateTimeZone('Asia/Manila'))->format('Y-m-d');

        return $issuedOn !== null && $issuedOn <= $today
            && ($expiresOn !== null ? $expiresOn >= $today : ! $requiresExpiry);
    }
}
