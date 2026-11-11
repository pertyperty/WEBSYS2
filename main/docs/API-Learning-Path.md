# Learning the auditor API as we build

The auditor API is implemented with Sanctum 4.4.0. Use the walkthrough below to trace it and practice requests privately. You do not need to learn every API concept before working on the rest of CheckPoint.

In our proposal, CheckPoint **provides** an API that third-party insurance auditors call. We are not connecting to an insurance company's existing API. That means we control the endpoint, response format, authentication, and permission rules. No external provider account is needed.

## 1 Start with the web foundation

The foundation uses web routes such as `GET /login` and `POST /login`. GET requests retrieve pages. POST submits information. Fortify validates login details, and Laravel stores a session for subsequent browser requests. Blade turns server data into HTML.

Try creating a staff account, signing out, and signing in again. Observe the redirect to the awaiting-assignment page. Being logged in establishes identity; it does not authorize assignment changes. Read `routes/web.php`, the `manage-users` Gate, and the authentication/assignment feature tests to see the distinction.

Browser form requests include a CSRF token. The later auditor API uses a bearer token instead of relying on a browser session. Keep those mechanisms distinct.

## 2 Define the data before exposing it

During qualification and credential milestones, learn how models, foreign keys, review decisions, and expiry rules produce a verification result. We will test those rules first. An API must use the same assessment logic as the browser; it should not become a second implementation of compliance.

The training milestone illustrates the same boundary: a session identifies the officer, the completion Gate permits the action, validation checks the date, and a transaction protects the enrollment state. Completing training alone does not produce a compliance result. The later auditor API must verify evidence as well as matching completed training.

Each submission has an opaque reference. SelectEffectiveCredential keeps older valid approvals available while renewals are pending. The API evaluates the requested submission itself, without substituting another approval or requiring the qualification to be a current job requirement. Private document/download routes remain separate and do not authorize auditors.

A verification reference identifies a particular evidence submission. It is not a password and is not sufficient to authorize access. It does not mean that all of the employee's other requirements are satisfied.

## 3 Follow the implemented JSON route

`GET /api/v1/verifications/{reference}` in [routes/api.php](../routes/api.php) returns JSON through a dedicated controller and Resource. The Resource explicitly chooses permitted fields instead of serializing the entire model.

Read the implemented response in [System specification](System-Proposal.md). Request flow: pre-authentication IP limit (120/minute) → Sanctum token hash and required future expiry → active Auditor, active region, pinned token region and literal `verification:read` ability → account limit (60/minute shared across tokens) → current regional Staff lookup → shared qualification predicate → nine-field Resource. The IP limiter wraps Laravel's throttle middleware so anonymous failures are limited before authentication. Wildcard-only abilities and browser-session authentication are rejected.

## 4 Add Sanctum tokens and permissions

An administrator provisions an API-only auditor account with a region and issues an expiring token with `verification:read`. The token is shown once, stored privately by the auditor, and sent in the Authorization header. Sanctum stores a hash for validation. Revocation removes access.

The token proves which account is calling. Its ability permits the type of operation. A separate region check limits which records that account can verify. All three checks are necessary; a valid token is not permission to query every employee.

This is the implemented request shape:

```http
GET /api/v1/verifications/OPAQUE_SUBMISSION_REFERENCE HTTP/1.1
Host: websys2.test
Accept: application/json
Authorization: Bearer YOUR_PRIVATE_TOKEN
```

Replace placeholders privately. Never paste real tokens into Git, screenshots, shared request collections, or chat. No real token was generated for the interface review.

## 5 Exercise failures as well as success

| Scenario | Expected response | What it teaches |
|---|---|---|
| Valid token, ability, region, and reference | 200 with limited JSON | Successful contract |
| Missing, invalid, expired, or revoked token | 401 | Authentication |
| Wrong account role or missing ability | 403 | Permission to perform the operation |
| Unknown or other-region reference | 404 | Lookup and resource scoping |
| Too many requests | 429 | Rate limiting |

Tests assert that names, medical content, file paths, private feedback, and file contents are absent. Returning a correct status field is insufficient if a response leaks private data.

## Learning checkpoint

Explain who provides and consumes this API, how a request becomes JSON, how session and token authentication differ, why abilities and regional scope are separate, and how tests prove useful behavior and denied access.


## What the reporting milestone adds

The four CSV downloads are authenticated web responses under `/reports/download`. Their preview/download share scoped row building. Audit capture stores a fixed historical assessment. API tokens grant neither report access nor private-file access.

## Provision and test access

For a local classroom baseline, the optional `php artisan checkpoint:seed-synthetic` command creates `admin@checkpoint.example`, `auditor.01@checkpoint.example`, and Staff evidence scenarios using a password entered privately. It creates **no tokens**. See the [scenario walkthrough](Implementation.md#synthetic-dataset-and-classroom-walkthrough). Scope 01 is the first region by database ID, not an official region number. Use Accounts & access to confirm the assignment and Credentials to obtain a submission reference. Compare `staff.01.current@checkpoint.example` with the two references under `staff.01.renewal@checkpoint.example`; a pending renewal is not valid while the prior approval remains valid. Never project or commit the privately issued bearer value.

1. Let the intended auditor register. As Admin, use **Accounts & access** to assign the Auditor role and an active region. The Auditor cannot sign into the browser workspace.
2. Open **Administration > Auditor access**, select the auditor, enter a recognizable name, and choose expiry. Default: 30 days ahead; allowed dates: today through 90 days ahead.
3. Issue only when needed. Plaintext appears once in a direct no-store/no-referrer response, never session flash. Deliver it privately. Later screens show metadata only.
4. Revoke from the list when access ends. Role/region changes and deactivation revoke all account tokens; unchanged assignments preserve them. Each token also pins its issuance region.

Expiry includes the full selected Asia/Manila date. Storage uses the next midnight converted to UTC as an exclusive cutoff. Expiry-less tokens and tokens exactly at cutoff return 401. Current employee facility/region determines lookup scope; transfers remove the old region's access. Inactive Staff/facilities are excluded.

Restoring an older database also restores its token hashes. A token revoked after that backup can therefore become valid again. The [recovery instructions](../../README.md#backup-and-restore-rehearsal) require maintenance-only `checkpoint:invalidate-restored-access` before reopening: it clears restored sessions, remembered logins, reset links, and tokens. Administrators then issue fresh auditor tokens. The isolated recovery test demonstrates both snapshot behavior and subsequent invalidation; it does not issue or restore tokens in the development database.

`valid` means this submission passes shared evidence/date/training rules; `expired` means approved evidence has a past expiry; otherwise `not_valid`. This is not whole-job eligibility. Review time comes from the latest approval/rejection event, not document update time. `expires_on` and `reviewed_at` may be null; timestamps are UTC, evaluated dates Manila. Successful responses use `private, no-store`.

The local anonymous smoke returned 401 JSON (`Unauthenticated.`):

```powershell
curl.exe --silent --show-error --include http://websys2.test/api/v1/verifications/unknown
```

After privately issuing a token, this PowerShell request avoids echoing it:

```powershell
$privateAuditorToken = Read-Host 'Auditor token' -AsSecureString
$verificationHeaders = @{
    Accept = 'application/json'
    Authorization = 'Bearer ' + [System.Net.NetworkCredential]::new('', $privateAuditorToken).Password
}
Invoke-RestMethod -Uri 'http://websys2.test/api/v1/verifications/REPLACE_WITH_SUBMISSION_REFERENCE' -Headers $verificationHeaders
Remove-Variable privateAuditorToken, verificationHeaders
```

Use HTTPS before sending tokens beyond the local machine. Postman is optional; keep its bearer value in a private secret variable. No Postman workspace/collection run or authenticated live smoke is claimed.

From `main/`, run the real hashed-token tests against `checkpoint_test`:

```powershell
php artisan test --compact tests/Feature/CredentialVerificationTest.php tests/Feature/AuditorApiTest.php tests/Feature/AuditorTokenManagementTest.php
```

For the defense, compare an older valid approval and its pending renewal: their references return different results. Try another region (404), revoke and retry (401). Never project a token. The full suite passed 254 tests / 2,011 assertions.
