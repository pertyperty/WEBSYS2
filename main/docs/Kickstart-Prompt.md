# CheckPoint co-developer kickoff prompt

Use this prompt to continue development. Milestones 0?4 are now implemented; inspect the progress record and do not recreate it. Supply deadline/team experience when known. Work in reviewable milestones and explain the auditor API as it is built.

```text
Act as our co-developer for CheckPoint, a Regional Safety Compliance Management System for healthcare logistics. Help our student team build an understandable, complete Laravel system and explain decisions we need to maintain and defend.

Start by inspecting git status, repository instructions, README.md, main/docs/System-Proposal.md, main/docs/Development-Decisions.md, main/docs/Implementation.md, and main/docs/API-Learning-Path.md. Preserve unrelated changes and existing documentation. The Laravel 13.40/Fortify 1.41 foundation already exists under `main/`. Check implemented code and remaining setup rather than scaffolding again. Authentication, organization/requirements, training, private credentials/reviews, and live compliance with scoped dashboards/matrix/history exist. Continue milestone 5 (notifications, audits, CSV reports) after checking the progress record. Reuse AssessEmployeeCompliance and SelectEffectiveCredential; preserve RecordComplianceAssessment snapshots. The original Word proposal is the business source.

Agreed stack and services:
- One Laravel application under `main/` (run Composer, npm, and Artisan there); Blade, Tailwind, and Alpine.js for small interactions. No separate SPA, microservices, or generic repository abstraction.
- MySQL, matching the proposal. Do not substitute PostgreSQL even though it is installed.
- Herd for the local classroom demo. The existing MySQL80 service and Herd were verified running on 2026-11-10; the login page is served at `http://websys2.test`. Recheck their status when resuming.
- Keep the selected Laravel 13/Fortify 1 session-auth setup with Blade. Verify installed versions and lockfiles before relying on package APIs; do not blindly reuse obsolete starter commands.
- PHPUnit, Pint, Vite, and Laravel Sanctum in the API milestone. Local private filesystem, database notifications in their milestone, database session/cache, and local mail logging for password resets. Boost is already installed as a development-only dependency.
- A daily Laravel scheduler for alerts/reconciliation. No paid services, real email provider, Redis, Docker, AI API, or dedicated queue worker is required for the baseline.
- Use placeholders in .env.example; keep secrets and real employee/medical files out of Git and chat. Configure a separate disposable MySQL test database before running tests.

Agreed identity and scope:
- Six separate roles: Admin, Compliance Officer, Regional Director, Facility Manager, Staff, and API-only Auditor. One role per account; application role is distinct from employee job role.
- Self-registration creates unassigned Staff. Only Admin assigns facility/job/role. Unassigned staff get an awaiting-assignment page and cannot access employee records or enrollment/submission workflows.
- Each employee has one facility and job. Managers see one assigned facility, directors one assigned region. Auditor tokens are limited to an assigned region plus verification:read ability.
- Admin handles account/configuration/token administration. Officers record training completion and review evidence across facilities. Managers/directors read scoped summaries; managers update assigned corrective findings. Staff access only their records. Nobody approves their own evidence.
- Raw document downloads are limited to owner, officer, and Admin. Auditor accounts have no web dashboard. Enforce policies/Gates and scoped queries at the server, including reports and API access.

Complete system scope, delivered in stages:
1. Authentication: registration, login/logout, reset, profile, secure sessions, throttling, and role-aware navigation.
2. Organization/configuration: regions, facilities, jobs, safety domains, qualifications, mandatory job requirements, and employee assignments.
3. Training: separate published/archived courses mapped to one qualification each; staff self-enrollment; unique employee/course enrollment pivot; officer-recorded completion/date/feedback. Renewed course offerings use new records. No learning-content or exam platform.
4. Credentials: staff PDF/JPEG/PNG submissions, metadata, private storage, authorized attachment downloads, officer approval/rejection, rejection reasons, and append-only submission/review history. Default upload maximum is configurable 10 MiB. Validate actual MIME, dates, enrollment ownership, and matching qualification. Every renewal is a new submission; cleanup failed storage/record writes.
5. Qualification/compliance: employee/qualification and job/qualification many-to-many relationships; training qualifications require completed linked enrollment AND approved valid evidence. Enrollment/completion alone grants nothing.
6. Dashboards and history: staff qualification matrix, officer queue, facility/regional readiness, Admin screens, and material compliance-change history. Compute through one shared service rather than duplicating rules in controllers/views.
7. Audits/reports: scoped officer-created audits, immutable readiness snapshots, corrective findings with assigned manager/due date/resolution, and filtered CSV readiness/expiry/training/findings exports. Neutralize spreadsheet formulas in exports.
8. In-app alerts: daily warning/expired events to affected staff and facility managers, deduplication, unread indicators, and mark-as-read. Explain scheduler operation; current validity is still calculated on read if scheduling stops.
9. Auditor API: GET /api/v1/verifications/{reference}, Sanctum, explicit ability, regional scope, active account checks, token expiry/revocation, rate limits, and minimal API Resource. Opaque references identify individual submissions, not employee-wide eligibility. Return no files, medical details, paths, names, or private reviewer feedback.

Compliance rules:
- Evaluate calendar dates in Asia/Manila; store event timestamps in UTC. Approved evidence is valid through its expiry date, including today. Permit absent expiry only for qualifications explicitly defined as non-expiring.
- Warn from today through 30 calendar days ahead, inclusive; expiring-soon evidence remains compliant.
- Required missing/pending/rejected/expired evidence and incomplete required training block eligibility. A pending renewal cannot invalidate older approved evidence that still qualifies; choose any qualifying approval, not simply the latest upload.
- Percentage = satisfied mandatory qualifications / all mandatory qualifications * 100. Use exact counts for eligibility and round only for display.
- Missing facility/job or no configured requirements means not assessable, never automatically 100% compliant. Optional qualifications do not affect the denominator.
- Keep review state separate from derived validity. Preserve historical reviews, submissions, assignments in recorded snapshots, and audit results. Do not delete referenced history.
- Record material changes and reconcile date-driven changes idempotently. All dashboard/report/API results must agree under the same clock and inputs.

Mandatory academic requirements:
- Multi-role policies/Gates and complete authentication.
- Actual Eloquent many-to-many employee/qualification, job/qualification, and employee/course relationships.
- Actual Region hasManyThrough Staff through Facility, using facilities.region_id and users.facility_id and filtering Staff.
- Relationship-aware factories/seeders with at least 1,000 total synthetic records; report counts by table. Use harmless evidence fixtures or clearly unavailable-file states.
- Validated private Storage uploads/downloads.
- Versioned Sanctum JSON API with auth, ability, scope, privacy, and rate-limit tests.
- Unit tests for eligibility/date logic and feature tests for authentication, roles/scopes, training completion, review transitions, uploads/downloads, reports, notifications, and API behavior.
- Laravel MVC with separate web/API routes/controllers, Form Requests, Eloquent, migrations, factories, policies, Blade components, and small shared services.

Work method:
- Propose the next small vertical slice, then implement it with authorization, validation, focused tests, and docs. Do not scaffold or ship the entire system in one change.
- Preserve main/docs/ and the existing application. Do not overwrite Git metadata, local configuration, or existing code with a new scaffold.
- Use a feature branch and coherent PR. Update Implementation.md with actual completed items and evidence; do not mark future work done. Explain deviations from agreed decisions before dependent changes.
- Run focused checks during work, then full PHPUnit, Pint, and asset build as applicable before handover. Use the separate MySQL test database, never the development data. Never run destructive database resets without explicit authorization.
- Test cross-user/facility/region denial, self-approval, concurrent review, incomplete training, expiry boundaries, pending renewals, missing configuration, scheduler deduplication, token abilities/revocation, and sensitive-field exclusion as those features arrive.
- Keep runtime setup reproducible: exact versions/commands, Herd URL, MySQL setup, frontend build, private storage, mail logs, scheduler, synthetic demo accounts, API examples, backup/restore, and known limitations.
- Ask only when missing decisions materially block work. Do not reopen choices already confirmed. Deadline and team experience can be supplied later; do not invent a delivery date.

Next development deliverable:
The existing foundation is configured against MySQL80 on port 3306 with separate development/test accounts; migrations, organization seeding, and 23 tests / 142 assertions passed. Administrator creation and the interactive walkthrough remain owner tasks. Recheck local service availability as needed. Never print or commit credentials. Milestone 1 is implemented: organization catalogs, safety domains/qualifications, job requirements, safe deactivation, and scoped employee pages. Milestone 2 is also implemented: course publishing/archival, self-enrollment, immutable officer completion, and scoped training progress. Milestone 3 is also implemented: private evidence uploads/downloads, append-only review events, and current-evidence selection. Continue with milestone 4: compliance calculation, qualification matrix, scoped dashboards, and assessment history. Reuse SelectEffectiveCredential as the date/training/evidence truth; do not treat the materialized employee qualification link as automatically valid after time passes. Training-required evidence needs a matching completed enrollment before approval. Enrollment/completion alone must not award a qualification. Keep controllers and rules explicit and document how the team can explain each workflow during the defense.

Learning requirement:
Explain relevant concepts in plain language while developing. Follow API-Learning-Path.md: first web routes and sessions, then evidence/domain rules, then the versioned JSON route and Resource, then Sanctum tokens, abilities, region checks, errors, and tests. Let the owner run small requests as each piece becomes available. Clearly distinguish implemented endpoints from planned examples.

Before changing files, report the proposed exact framework/auth setup after checking compatibility. At handover, report changed behavior, actual checks/results, local configuration still needed, PR link if created, and the next milestone. Keep the rest of the agreed system visible in the plan without presenting it as implemented.
```
