# CheckPoint system specification

CheckPoint is a Regional Safety Compliance Management System for healthcare logistics across multiple regions and facilities. It replaces scattered attachments and spreadsheets with private evidence storage, training and qualification tracking, scoped compliance reporting, and controlled auditor verification.

This specification translates the supplied `WEBSYS2-System-Proposal-CheckPoint.docx` into the scope agreed with the project owner on 2026-11-10. The Word proposal remains unchanged. See [Development decisions](Development-Decisions.md) and [Implementation plan](Implementation.md). Authentication/onboarding, organization catalogs, qualifications, job requirements, and scoped employee pages are implemented. Training courses, enrollment, completion, and scoped progress are implemented. Private evidence, protected downloads, review history, and qualifying-evidence selection are implemented. Whole-employee compliance, audits, alerts, and CSV reports are implemented. The restricted auditor API below remains planned.

## Architecture and boundaries

Use one Laravel application with MySQL, Blade, Tailwind CSS, and Alpine.js for small interactions. Use PHPUnit and Pint. Laravel 13.40 and Fortify 1.41 are selected and locked for the foundation. Herd serves the Laravel application in `main/` at `http://websys2.test`.

Use web sessions for browser authentication, Sanctum for auditor tokens, Eloquent, Form Requests, policies/Gates, and a small number of services for shared rules. Separate web/API controllers and routes. Avoid business calculations in Blade, generic repository layers, microservices, and a separate SPA.

The complete release includes every module below. Public hosting, real email delivery, SMS, electronic signatures, learning-content delivery, exams, payments, and insurer-system integration are outside this release. CheckPoint provides the verification API; no external insurance service is needed. Eligibility is an application assessment, not an integration with actual dispatch systems.

## Identity and access

Each account has one application role. Each employee has one current facility and job role. A facility belongs to one region. A manager has one assigned facility; a director has one assigned region. An auditor token is limited to an assigned region as well as its API ability. Admin and Compliance Officer are separate roles.

| Role | Allowed scope and actions |
|---|---|
| Admin | Manage accounts, roles, assignments, domains, qualifications, jobs, requirements, regions, facilities, courses, and auditor tokens; view all reports/audits. Credential review requires the Compliance Officer role. |
| Compliance Officer | Review evidence across facilities, record training completion, maintain requirements/courses, and conduct audits with corrective findings. Cannot approve own evidence or grant roles. |
| Regional Director | Read compliance summaries, training progress, audits, dashboards, and reports for the assigned region. No evidence review or raw-document download. |
| Facility Manager | Read the same summaries for the assigned facility and update corrective findings assigned to them. No evidence review, role assignment, or raw-document download. |
| Staff | View permitted profile fields, own requirements/history/notifications, enroll in published courses, and upload/download own evidence. |
| Auditor | API-only verification in the assigned region with `verification:read`; no browser dashboard or document access. |

Admin may download evidence for oversight; officers may download for review. Nobody approves their own submission. Enforce route restrictions, resource policies, scoped queries, and API abilities; hiding buttons is insufficient.

Registration creates unassigned Staff. Only Admin assigns roles, facilities, and jobs. Unassigned staff can log in, edit permitted personal fields, and see an awaiting-assignment page; employee records, enrollment, and submission workflows remain unavailable. Protect assignment/role fields against mass assignment. Provide registration, login/logout, password reset, profile updates, session regeneration, password hashing, CSRF protection, and authentication throttling. Auditor accounts cannot log into the browser interface.

Deactivate accounts rather than deleting history. Assignment changes immediately affect visibility, requirements, and eligibility; historical records retain their recorded context.

## Data model

| Table or group | Relationships and constraints |
|---|---|
| `regions`, `facilities` | Region has many facilities; each facility has `region_id`. |
| `users`, `job_roles` | Employee users reference `facility_id` and `job_role_id`; director/auditor accounts reference their permitted region. Validate assignments by role. |
| `safety_domains`, `qualifications` | Each qualification belongs to a domain and defines whether expiry and training completion are required. |
| `job_role_qualification` | Unique job/qualification pivot for mandatory requirements. |
| `employee_qualification` | Unique employee/qualification pivot referencing effective approved evidence where available. Keep historical submissions separately. |
| `training_courses` | Each published/archived course maps to one qualification; multiple courses may satisfy the same qualification. |
| `employee_trainings` | Employee/course enrollment pivot with enrolled date, status, completed date, and completing officer. Unique employee/course; renewal offerings use new course records. |
| `credential_documents` | Employee, qualification, optional enrollment, private path, issue/expiry dates, opaque verification reference, and pending/approved/rejected review state. Each renewal is a new immutable submission. |
| `document_reviews` | Append-only submission/review events with actor, decision, reason/feedback, and timestamp. |
| `audits`, `audit_findings` | Region or facility scope, author/date, readiness snapshot, findings, assignee, due date, open/resolved state, and resolution notes. Finding assignees must belong to the audit scope. |
| `compliance_history` | Material assessment changes with employee, facility/job context, outcome, counts, timestamp, and rule version. |
| Framework tables | Sessions, cache, password-reset tokens, database notifications, and Sanctum tokens as required by the chosen setup. |

Use foreign keys, unique pivots/references, and indexes for scope/status/expiry filters. Restrict deletion of referenced records; archive qualifications/courses and deactivate users. Store calendar dates as dates, event timestamps in UTC, and evaluate compliance dates in Asia/Manila.

Implement actual Eloquent many-to-many relationships for employee/qualification, job/qualification, and employee/course. Implement `Region -> Users through Facilities` using `hasManyThrough(User::class, Facility::class, 'region_id', 'facility_id')`, filtered to Staff for employee reports. Direct `users.facility_id` supports the agreed single-facility assignment without an extra assignment pivot.

## Credentials and verification

Staff submit PDF, JPEG, or PNG evidence up to 10 MiB with qualification, issue date, required expiry date, and an enrollment link for training qualifications. Validate actual MIME/content, size, dates, ownership, and course/qualification consistency. Non-expiring qualifications may omit expiry; expiring ones require it. Reject future issue dates and expiry before issue date. Keep the size limit configurable.

Use generated storage names on a private Laravel disk. Authorize every download and return attachments with safe response headers. Never expose private paths or public storage URLs. Clean up stored files if database creation fails and handle missing files gracefully.

Officers review pending evidence with transactional state checks to prevent conflicting decisions. Rejection requires a reason. Record the reviewer and every decision. Approved/rejected submissions are not overwritten; resubmission creates a new record. Expiry is calculated separately: an approved document may become expired without losing its approval event.

Update effective employee/qualification evidence transactionally after review. Select any currently qualifying approval rather than simply the latest upload. An expired old approval must not mask a newer valid one; a pending renewal must not invalidate a still-valid approval.

## Training and qualification matrix

Officers/Admin publish courses. Staff self-enroll once per published course and cannot mark completion. Officers record completion, date, and feedback. Course content, exams, and attendance integrations are not included.

Each course maps to one qualification. Enrollment or completion alone never confers compliance: a training-required qualification requires completed linked enrollment plus approved, unexpired evidence. An officer cannot approve training evidence while enrollment is incomplete. Other qualifications need evidence without enrollment. Archiving a course preserves its history.

The matrix compares job requirements with effective qualifications, including domain, training completion, review state, expiry, and feedback. Optional qualifications do not affect the mandatory-compliance denominator.

## Compliance calculations and history

Use one service across dashboards, reports, audit snapshots, and API responses. The same clock and inputs must produce the same assessment.

- Approved evidence is valid when issued on/before today and expiry is absent where permitted or on/after today. Validity includes the full expiry date in Asia/Manila.
- Expiring soon means valid evidence expires between today and 30 calendar days ahead, inclusive. It remains compliant.
- Each required qualification needs qualifying approved evidence and, when applicable, completed training. Missing, pending, rejected, expired, or incomplete required items block eligibility.
- If no evidence qualifies, display pending when a pending submission exists; otherwise rejected when the latest reviewed submission was rejected, expired when approved evidence has expired, or missing when no applicable evidence exists. Include underlying history and training-incomplete details.
- Percentage is satisfied mandatory qualifications divided by all mandatory qualifications times 100. Round only for display; eligibility uses exact counts.
- All mandatory requirements satisfied means eligible; otherwise not eligible. No facility, no job, or no configured mandatory requirements means not assessable, without a misleading 100% value.
- Record material changes from assignments, requirements, completions, reviews, and expiry in compliance history. A daily idempotent command reconciles date-driven changes. Compute current status on read so a stopped scheduler cannot leave expired evidence valid.

## Dashboards, audits, reports, and alerts

Provide staff self-service pages, officer review queue, administrative configuration, and facility/regional dashboards. Include eligible/non-compliant/not-assessable totals, missing evidence, pending reviews, expiring qualifications, and training progress. Dashboard denominators explicitly identify assessable staff and show not-assessable counts separately. Use scoped filters, pagination, and eager loading.

Officers create audits with scope, author/date, immutable readiness snapshot, findings, due dates, and corrective actions. Managers update findings assigned to them within their facility. Historical snapshots do not change when current evidence or requirements change. Employee assessment history and audit findings remain distinct. Managers submit corrective work for officer verification; officers can reopen or reassign within the facility, retaining dated notes and assignment activity. Neither an assigned manager nor the latest corrective author may verify their own work. Captures use today's readiness; backdated capture is not supported.

CSV reports cover readiness, expiry, training progress, and findings with the same authorization/filters as browser pages. Exclude private paths and medical content; neutralize cells beginning with spreadsheet formula markers. PDF exports are deferred.

Send in-app database notifications to staff and their facility managers when required qualifications enter the 30-day expiry window and when they expire. Deduplicate by recipient, evidence/qualification, and event type. Renewal makes old alerts obsolete; retain them as history. Provide unread counts and mark-as-read. Explain that scheduled delivery requires the scheduler process. Password reset uses local mail logs; no SMTP provider is required.

## Auditor API

Implement `GET /api/v1/verifications/{reference}` using Sanctum, explicit `verification:read` ability, active-account checks, regional authorization, and rate limiting. Use opaque non-sequential submission references. An ability never grants access outside the auditor's region. Admin provisions/revokes tokens with a documented expiry; show plaintext only on creation.

Use an API Resource with this minimal contract, finalized in implementation:

```json
{
  "data": {
    "reference": "opaque-reference",
    "employee_reference": "opaque-employee-reference",
    "qualification": "First Aid",
    "review_status": "approved",
    "validity": "valid",
    "issued_on": "2026-10-01",
    "expires_on": "2027-10-01",
    "reviewed_at": "2026-10-02T02:00:00Z",
    "evaluated_on": "2026-11-10"
  }
}
```

The result describes that specific evidence submission, not employee-wide eligibility. Renewals have separate references. Return 401 for absent/invalid/expired/revoked tokens, 403 for disallowed role or missing ability, 404 for unknown/out-of-scope references, and 429 for throttling. Expose no files, names, medical details, private paths, rejection feedback, or unrelated qualifications. Examples use placeholder tokens.

## Acceptance and operations

Each workflow includes validation, authorization, empty/error states, and focused tests. Use MySQL integration tests in a dedicated disposable database. Seed at least 1,000 synthetic records covering valid/expired/pending/rejected/missing evidence, incomplete/completed training, and cross-scope accounts. Generate harmless fixture files or clearly label unavailable seed evidence; do not show apparently working links to nonexistent downloads.

The local handover documents fresh setup, migrations, seeding, Vite builds, Herd, scheduler operation, mail-log resets, API examples, demo accounts, test commands, and backup/restore of both MySQL and private files. Document only development demo credentials. Never run destructive resets on an existing database without an explicit request. Claim no runtime or feature test result before code exists and the check actually runs.
