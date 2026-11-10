# Implementation plan and progress

## Current status

**Stage:** milestone 1 implemented; training courses/enrollment are next.
**Last updated:** 2026-11-10.
**Application status:** Authentication/onboarding, Admin organization and qualification management, job requirements, scoped employee pages, safe deactivation, private-storage configuration, and MySQL tests are implemented. Training, evidence, compliance, audits, alerts, reports, and the auditor API remain planned.

The full scope is in [System specification](System-Proposal.md), decisions and local service needs in [Development decisions](Development-Decisions.md), and the next-session brief in [Kickstart prompt](Kickstart-Prompt.md). The owner will provide the deadline and team experience later; no delivery date is promised.

## Milestones and acceptance criteria

Each milestone is a coherent PR with its own validation and documentation update. Implement models, policies, factories, and tests with the workflow that needs them. Expand seed data throughout development rather than postponing all factories until the end.

### 0 Foundation and local setup

- [x] Inspect the current repository and preserve `main/docs/`; integrate the foundation into the owner-created Laravel scaffold under `main/`, preserving its directory layout.
- [x] Record supported framework/PHP/Node/MySQL versions and lock dependencies. Choose compatible session authentication with Blade; do not assume a legacy starter works with the chosen release.
- [x] Verify/start the existing MySQL service as appropriate; configure dedicated development/test databases and placeholder-only `.env.example`.
- [x] Establish Vite scripts, private storage, mail log, PHPUnit, and Pint. Herd serves the repository at `http://websys2.test` (login HTTP 200 verified).
- [x] Deliver registration, login/logout, password reset, profile, base layout, and awaiting-assignment screen. Only Admin controls role/job/facility assignment.
- [x] Document exact fresh setup commands and verify frontend build, formatting, and authentication tests against the test database.

**Integration:** The foundation was reapplied onto the owner-created scaffold in `main/` after PR #6 was merged and reverted. CI and setup commands use that application directory. The scaffold's unit example is retained, bringing the suite to 23 tests.

**Verification:** 23 tests / 142 assertions passed against the existing MySQL80 service on 127.0.0.1:3306 on 2026-11-10, using the isolated `checkpoint_test` database/account. Development migrations and the repeatable organization fixture seeder completed in `checkpoint` using a separate application account. Existing database accounts were preserved. Herd is running and the login page returned HTTP 200 at `http://websys2.test/login`. Administrator creation and the interactive account walkthrough remain owner tasks. Pint, Composer validation, Blade compilation, and Vite build passed during foundation implementation. A GitHub Actions MySQL workflow is included; remote results are not claimed here. The earlier temporary server on port 3307 remains stopped. The foundation includes minimal organization models/foreign keys, a tested regional has-many-through relation, an idempotent fixture seeder, and an interactive administrator creation command. These do not complete milestone 1's CRUD/requirements scope.

**Acceptance:** a fresh checkout can be configured from the README; sessions work; a new account has no employee-record access or role escalation path. Password reset is demonstrated through local logs. Only this foundation is the first development deliverable.

### 1 Organization, roles, and requirements

- [x] Add regions, facilities, jobs, safety domains, qualifications, and job requirement management.
- [x] Implement six roles and resource scoping, including Admin/Officer separation and API-only Auditor.
- [x] Implement employee assignment and employee/qualification/job pivots.
- [x] Implement and test `Region -> Staff through Facilities` as a real Eloquent has-many-through relationship.
- [x] Add CRUD validation, safe archive/deactivation rules, pagination, and representative factories.

**Acceptance:** unrelated facilities/regions cannot be read or exported by changing URL/filter IDs; staff cannot assign roles or change their job/facility. An employee's job requirements can be retrieved through tested Eloquent relationships.

**Milestone 1 evidence:** 69 tests / 454 assertions pass against isolated MySQL, including 46 new catalog, requirement, permission, and scope cases. Composer validation, Pint, Blade compilation, and production Vite build pass. Existing development data is preserved by an additive migration. Admin controls all five catalogs; Admin/Officer maintain job requirements. Employee qualification relationships exist for the later evidence module and are not populated by assigning job requirements. Scope is enforced on both directory and detail queries; reports/API will reuse that boundary when implemented.

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

## Code walkthrough for the defense

Follow one action first: an officer saves a job's mandatory qualifications.

| File (inside `main/`) | Responsibility |
|---|---|
| [routes/web.php](../routes/web.php) | Maps the URL/method to a controller and enforces authentication and the `manage-requirements` Gate. |
| [AppServiceProvider.php](../app/Providers/AppServiceProvider.php) | Defines who may manage catalogs, requirements, accounts, or view employees. Admin and Officer remain separate roles. |
| [UpdateJobRequirementsRequest.php](../app/Http/Requests/UpdateJobRequirementsRequest.php) | Authorizes the write and validates a unique array of active qualification IDs. An empty checkbox selection becomes an empty array. |
| [JobRequirementController.php](../app/Http/Controllers/JobRequirementController.php) | Uses `sync()` to replace the mandatory set. A database transaction keeps the detach/attach operations together. Returns a redirect and success message. |
| [JobRole.php](../app/Models/JobRole.php) | Defines the many-to-many relationship through `job_role_qualification`, which records mandatory requirements. |
| [requirements.blade.php](../resources/views/job-roles/requirements.blade.php) | Renders escaped names, grouped checkboxes, CSRF protection, validation feedback, and the save form. It makes no database queries. |
| [User.php](../app/Models/User.php) | Its `visibleTo` local scope restricts employee queries by role and assignment. `#[Scope]` marks a Laravel reusable query method; it is not a global filter. |
| [EmployeeController.php](../app/Http/Controllers/EmployeeController.php) | Applies that scope before pagination or `findOrFail()`, returning 404 for unknown and out-of-scope employees. Eager loads related facility/job/requirements to avoid repeated queries. |
| [SaveFacilityRequest.php](../app/Http/Requests/SaveFacilityRequest.php) | Example of resource-specific validation: unique names per region, active parent, and no moving/deactivating an assigned facility. |

The other catalogs follow the same short `index/create/store/edit/update` controller pattern. There is no generic CRUD engine, custom repository layer, or new dependency for this milestone. Gates answer **who may act**; validation answers **whether the submitted values and state are allowed**; scoped queries answer **which employee records may be read**. Hiding links supports usability but does not enforce permission.

Explain the data distinctions:

- `users.job_role_id` assigns one job to a Staff account. `facility_id` assigns one facility, whose `region_id` determines the region. Directors have a direct regional assignment; managers have a facility assignment.
- `job_role_qualification` defines what a job requires. `employee_qualification` is a separate many-to-many relationship reserved for effective earned qualifications after evidence review in milestone 3. Setting requirements does not write employee awards.
- Each qualification belongs to one safety domain and has `requires_training` and `requires_expiry` flags. Training completion and evidence checks are not implemented yet.
- Inactive records remain in the database. Region/domain deactivation is blocked by active child records; assigned accounts block region/facility/job deactivation. Required qualifications cannot be deactivated until removed from job requirements. Account assignments accept only active configuration records.
- A facility with assigned accounts cannot be moved between regions. Reassign the accounts first so an indirect edit cannot silently move their visibility. Account-role validation still requires complete assignments for managers/directors.
- No requirements means not assessable in the future compliance module, never automatically 100% compliant. The current page displays requirements without claiming an eligibility result.

Useful demonstration questions:

1. **Can a manager change the employee ID in the URL?** Yes, but the same scoped query is applied before lookup, so another facility's record returns 404. `EmployeeScopeTest` exercises this and attempts to widen scope with filter parameters.
2. **Can an officer grant an Admin role?** No. Accounts and organization configuration require the Admin Gate; officers can maintain job requirements. `OrganizationManagementTest` checks the six-role Gate matrix and route enforcement.
3. **Why use pivots?** Jobs and qualifications have a many-to-many relationship; a unique pair prevents duplicate requirements. Employee awards use a different pivot because needing a qualification does not mean having it.
4. **Why preserve inactive rows?** Removing records would break assignments and later training/evidence history. Database foreign keys restrict deletion and the UI exposes no delete action.
5. **How do we prove correctness?** Feature tests send real HTTP requests against separate MySQL test data, then check responses and persisted state. They cover allowed and denied actions, cross-scope IDs, malformed inputs, deactivation dependencies, and escaped user-supplied names.
6. **What about third-party APIs?** This milestone uses server-rendered web routes and session authentication. The later auditor API will return a minimal JSON representation and use tokens; it will still need role and regional scope checks. No external provider or API token is required now.

## Definition of done

A workflow is complete when its persistence, validation, policy, UI/API, error states, focused tests, usable seed data, and documentation are present. Run relevant tests and formatter/build checks; before handover run the full suite. Report actual results and limitations. Never run tests or destructive seed/reset commands against a populated development database.

## Development log

| Date | Change | Evidence |
|---|---|---|
| 2026-11-05 | Initial proposal, implementation plan, and prompt | Existing documentation-only commits |
| 2026-11-10 | Reconciled Word proposal with owner decisions; added training/enrollment, precise rules, services, milestones, and co-developer prompt | Documentation PR; no application implementation or runtime tests |
| 2026-11-10 | Implemented foundation and added API learning guide | Laravel 13.40/Fortify 1.41, 22 tests/141 assertions on isolated MySQL, Pint, Composer validation, Blade compilation, and Vite build; main local service configuration pending |
| 2026-11-10 | Implemented organization/qualification management, job requirements, scoped employee pages, and defense walkthrough | 69 tests/454 assertions on isolated MySQL; additive migration; Pint, Composer validation, Blade and Vite checks |

## Risks and responses

| Risk | Response |
|---|---|
| Full scope exceeds available course time | Use staged vertical slices; record deadline/team capacity before scheduling milestones. |
| Private evidence or reports cross scope | Central policies plus scoped queries and cross-region/facility tests. |
| Renewal/expiry/training rules diverge | One assessment service, fixed-clock tests, immutable evidence, and explicit enrollment requirements. |
| Alerts silently stop | Document scheduler process and calculate current validity on read. |
| Test commands destroy local work | Separate MySQL test database and environment guards. |
| Existing MySQL service is unavailable | Check service/version/access before scaffolding; document setup without silently switching database engines. |
