# Implementation plan and progress

## Current status

**Stage:** agreed project planning, documentation only.
**Last updated:** 2026-11-10.
**Application status:** not scaffolded; no database migrations, dependencies, or runtime tests yet.

The full scope is in [System specification](System-Proposal.md), decisions and local service needs in [Development decisions](Development-Decisions.md), and the next-session brief in [Kickstart prompt](Kickstart-Prompt.md). The owner will provide the deadline and team experience later; no delivery date is promised.

## Milestones and acceptance criteria

Each milestone is a coherent PR with its own validation and documentation update. Implement models, policies, factories, and tests with the workflow that needs them. Expand seed data throughout development rather than postponing all factories until the end.

### 0 Foundation and local setup

- [ ] Inspect the current repository and preserve `main/docs/`; scaffold Laravel at the repository root without overwriting existing content.
- [ ] Record supported framework/PHP/Node/MySQL versions and lock dependencies. Choose compatible session authentication with Blade; do not assume a legacy starter works with the chosen release.
- [ ] Verify/start the existing MySQL service as appropriate; configure dedicated development/test databases and placeholder-only `.env.example`.
- [ ] Establish Herd domain, Vite scripts, private storage, mail log, PHPUnit, and Pint.
- [ ] Deliver registration, login/logout, password reset, profile, base layout, and awaiting-assignment screen. Only Admin controls role/job/facility assignment.
- [ ] Document exact fresh setup commands and verify frontend build, formatting, and authentication tests against the test database.

**Acceptance:** a fresh checkout can be configured from the README; sessions work; a new account has no employee-record access or role escalation path. Password reset is demonstrated through local logs. Only this foundation is the first development deliverable.

### 1 Organization, roles, and requirements

- [ ] Add regions, facilities, jobs, safety domains, qualifications, and job requirement management.
- [ ] Implement six roles and resource scoping, including Admin/Officer separation and API-only Auditor.
- [ ] Implement employee assignment and employee/qualification/job pivots.
- [ ] Implement and test `Region -> Staff through Facilities` as a real Eloquent has-many-through relationship.
- [ ] Add CRUD validation, safe archive/deactivation rules, pagination, and representative factories.

**Acceptance:** unrelated facilities/regions cannot be read or exported by changing URL/filter IDs; staff cannot assign roles or change their job/facility. An employee's job requirements can be retrieved through tested Eloquent relationships.

### 2 Training courses and enrollment

- [ ] Add course management, publication/archival, course-to-qualification mapping, and employee/course enrollment pivot.
- [ ] Allow assigned staff to self-enroll in published courses; enforce one enrollment per course.
- [ ] Let officers record completion and feedback; prevent staff from recording their own completion.
- [ ] Add staff progress pages and scoped management summaries.

**Acceptance:** enrollment alone grants no qualification; unpublished courses cannot accept enrollment; completed training and its recorded actor/date survive course archival.

### 3 Private credentials and review history

- [ ] Add immutable submissions, metadata, opaque references, configurable upload limits, and append-only submission/review events.
- [ ] Implement validated PDF/JPEG/PNG storage and authorized downloads with failed-write cleanup.
- [ ] Add officer review queue, approval/rejection with reasons, and transactional concurrent-review protection.
- [ ] Validate completed matching enrollment before approving training-required evidence.
- [ ] Preserve existing valid approvals while a renewal is pending; do not overwrite prior files/reviews.

**Acceptance:** cross-user downloads, manager/director raw-file access, self-approval, invalid file content/size, and conflicting reviews are rejected. Approved training evidence has completed linked enrollment.

### 4 Compliance, dashboards, and history

- [ ] Centralize required-qualification selection, training/evidence checks, eligibility, and percentage calculation.
- [ ] Implement inclusive Asia/Manila expiry, 30-day warnings, and not-assessable configuration states.
- [ ] Add qualification matrix and staff/officer/facility/regional/admin dashboards with correct scoped denominators.
- [ ] Record material assessment history and recalculate after requirement, assignment, review, completion, and date changes.

**Acceptance:** a fixed clock yields matching dashboard and service results; expiry today, tomorrow, day 30/31, non-expiring qualifications, incomplete training, pending renewals, and empty requirements have tests. Rounding never grants eligibility.

### 5 Notifications, audits, and reports

- [ ] Add daily idempotent expiry reconciliation and in-app notifications for staff/managers, with deduplication and unread controls.
- [ ] Add officer-authored facility/regional audits with immutable readiness snapshots.
- [ ] Add findings, assigned managers, due dates, corrective notes, and resolution workflow.
- [ ] Add scoped CSV readiness, expiry, training, and findings reports with formula-injection handling.
- [ ] Document `php artisan schedule:work` and the effect of stopping it.

**Acceptance:** repeating the scheduled command does not duplicate alerts/history; status-on-read stays correct without it. A later credential renewal does not rewrite old audit snapshots. Exports and finding updates respect scope.

### 6 Restricted auditor API

- [ ] Install/configure Sanctum compatibly with the chosen framework; separate API routes/controllers/resources.
- [ ] Add Admin token provisioning/revocation, expiry, API-only accounts, abilities, regional scoping, and throttling.
- [ ] Implement `GET /api/v1/verifications/{reference}` and the documented minimal response.
- [ ] Add example requests with placeholders and tests for 200/401/403/404/429, revocation, expiry, and sensitive-field exclusion.

**Acceptance:** an auditor can verify only assigned-region evidence; tokens do not grant browser or private-file access. A reference describes its submission rather than employee-wide eligibility.

### 7 Full demonstration and handover

- [ ] Seed at least 1,000 total synthetic records with valid relationships and all important states; record counts by table.
- [ ] Provide harmless evidence fixtures or explicit unavailable-file states and development-only demo accounts for each role/scope.
- [ ] Run full PHPUnit tests on MySQL, Pint, and production asset build; record exact commands/results.
- [ ] Exercise register -> assign -> enroll -> complete -> submit -> review -> assess -> audit/report -> API verification.
- [ ] Verify notification delivery, password reset logs, unauthorized access, and snapshot/history retention.
- [ ] Document fresh setup, scheduler, test-database isolation, backup/restore of MySQL and private files, limitations, and demo script.

**Acceptance:** a teammate can reproduce the demo from a fresh checkout; all eight mandatory requirements below have implemented evidence. Do not mark these tasks complete based on documentation alone.

## Mandatory requirement traceability

| Requirement | Planned implementation | Completion evidence |
|---|---|---|
| 1 Multi-role authorization | Six roles, policies/Gates, record scopes | Role and cross-scope denial tests, milestones 0-1 onward |
| 2 Authentication | Register/login/logout/reset/profile/session lifecycle | Authentication and privilege-escalation tests, milestone 0 |
| 3 Advanced relationships | Employee/qualification, job/qualification, employee/course many-to-many; region/staff has-many-through | Relationship tests, milestones 1-2 |
| 4 1,000+ records | Relationship-aware factories and synthetic seeders | Per-table and total counts on MySQL, milestone 7 |
| 5 Upload/download | Private Laravel Storage, validation, protected attachments | File validation, ownership, and failure-path tests, milestone 3 |
| 6 Token API | Versioned Sanctum verifier | Contract/auth/scope/privacy tests and sample request, milestone 6 |
| 7 Unit/feature tests | PHPUnit rules and workflow coverage | Focused runs per slice and full suite, all milestones |
| 8 MVC foundation | Web/API routing/controllers, requests, policies, models, Blade, shared services | Code review, working pages/API, build and tests |

Training, compliance enforcement, expiry alerts, regional views, audits, review history, and reports are business requirements in addition to the eight technical requirements. They are not optional merely because they are outside that rubric list.

## Definition of done

A workflow is complete when its persistence, validation, policy, UI/API, error states, focused tests, usable seed data, and documentation are present. Run relevant tests and formatter/build checks; before handover run the full suite. Report actual results and limitations. Never run tests or destructive seed/reset commands against a populated development database.

## Development log

| Date | Change | Evidence |
|---|---|---|
| 2026-11-05 | Initial proposal, implementation plan, and prompt | Existing documentation-only commits |
| 2026-11-10 | Reconciled Word proposal with owner decisions; added training/enrollment, precise rules, services, milestones, and co-developer prompt | Documentation PR; no application implementation or runtime tests |

## Risks and responses

| Risk | Response |
|---|---|
| Full scope exceeds available course time | Use staged vertical slices; record deadline/team capacity before scheduling milestones. |
| Private evidence or reports cross scope | Central policies plus scoped queries and cross-region/facility tests. |
| Renewal/expiry/training rules diverge | One assessment service, fixed-clock tests, immutable evidence, and explicit enrollment requirements. |
| Alerts silently stop | Document scheduler process and calculate current validity on read. |
| Test commands destroy local work | Separate MySQL test database and environment guards. |
| Existing MySQL service is unavailable | Check service/version/access before scaffolding; document setup without silently switching database engines. |
