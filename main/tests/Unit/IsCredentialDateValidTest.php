<?php

namespace Tests\Unit;

use App\Actions\IsCredentialDateValid;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class IsCredentialDateValidTest extends TestCase
{
    #[TestWith(['2026-11-01', '2026-11-12', true, '2026-11-12T15:59:59Z', true], 'valid through the final Manila second')]
    #[TestWith(['2026-11-01', '2026-11-12', true, '2026-11-12T16:00:00Z', false], 'expired at next Manila midnight')]
    #[TestWith(['2026-11-13', '2027-11-13', true, '2026-11-12T15:59:59Z', false], 'future issue date')]
    #[TestWith(['2026-11-13', '2027-11-13', true, '2026-11-12T16:00:00Z', true], 'issue date reached in Manila')]
    #[TestWith([null, '2027-11-12', true, '2026-11-12T04:00:00Z', false], 'missing issue date')]
    #[TestWith(['2026-11-01', null, true, '2026-11-12T04:00:00Z', false], 'required expiry missing')]
    #[TestWith(['2026-11-01', null, false, '2026-11-12T04:00:00Z', true], 'nonexpiring qualification')]
    #[TestWith(['2026-11-01', '2026-11-11', false, '2026-11-12T04:00:00Z', false], 'provided expiry still enforced')]
    #[TestWith(['2026-11-01', '2027-11-12', true, '2026-11-12T04:00:00Z', true], 'future expiry')]
    public function test_date_window_follows_inclusive_manila_dates(?string $issuedOn, ?string $expiresOn, bool $requiresExpiry, string $instant, bool $expected): void
    {
        $this->assertSame($expected, (new IsCredentialDateValid)->handle($issuedOn, $expiresOn, $requiresExpiry, new DateTimeImmutable($instant)));
    }
}
