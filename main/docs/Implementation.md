# Implementation plan and progress

## Current status

**Stage:** milestone 6 implemented; synthetic dataset and handover remain in milestone 7.
**Last updated:** 2026-11-11.
**Application status:** Authentication/onboarding, Admin organization and qualification management, job requirements, scoped employee pages, safe deactivation, private-storage configuration, and MySQL tests are implemented. Courses, enrollment, completion, and scoped training progress are implemented. Private submissions, protected downloads, review events, and effective-evidence selection are implemented. Live compliance calculations, scoped dashboards/matrix, material assessment history, and daily history reconciliation are implemented. In-app expiry alerts, immutable audit snapshots, corrective findings, and scoped CSV reports are implemented. The restricted auditor API and Admin token management are implemented.

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

- [x] Add course management, publication/archival, course-to-qualification mapping, and employee/course enrollment pivot.
- [x] Allow assigned staff to self-enroll in published courses; enforce one enrollment per course.
- [x] Let officers record completion and feedback; prevent staff from recording their own completion.
- [x] Add staff progress pages and scoped management summaries.

**Acceptance:** enrollment alone grants no qualification; unpublished courses cannot accept enrollment; completed training and its recorded actor/date survive course archival.

**Milestone 2 evidence:** 105 tests / 702 assertions pass on isolated MySQL, including 36 training cases. Course/enrollment tables are additive and the repeatable seeder creates two published offerings without enrolling anyone or republishing archived records. Completion date/actor/feedback survive archival. Staff see their own progress; manager/director totals and rows share one scope. Admin can manage courses but only Officer records completion. Qualification mapping is locked after enrollment; archived courses and recorded completions cannot be reopened/overwritten. Training alone does not populate employee qualification awards.

### 3 Private credentials and review history

- [x] Add immutable submissions, metadata, opaque references, configurable upload limits, and append-only submission/review events.
- [x] Implement validated PDF/JPEG/PNG storage and authorized downloads with failed-write cleanup.
- [x] Add officer review queue, approval/rejection with reasons, and transactional concurrent-review protection.
- [x] Validate completed matching enrollment before approving training-required evidence.
- [x] Preserve existing valid approvals while a renewal is pending; do not overwrite prior files/reviews.

**Acceptance:** cross-user downloads, manager/director raw-file access, self-approval, invalid file content/size, and conflicting reviews are rejected. Approved training evidence has completed linked enrollment.

**Milestone 3 evidence:** 142 tests / 963 assertions pass against isolated MySQL, including 37 credential cases. Credential/event tables and the effective-document FK are additive. Submissions retain facility/job IDs from the time of submission. Raw evidence/history is limited to Staff ownership and Admin/Officer oversight; only Officer reviews, never their own evidence. Missing files cannot receive new approval. No evidence records/files are seeded yet; staff upload synthetic files through the real workflow.

### 4 Compliance, dashboards, and history

- [x] Centralize required-qualification selection, training/evidence checks, eligibility, and percentage calculation.
- [x] Implement inclusive Asia/Manila expiry, 30-day warnings, and not-assessable configuration states.
- [x] Add qualification matrix and staff/officer/facility/regional/admin dashboards with correct scoped denominators.
- [x] Record material assessment history and recalculate after requirement, assignment, review, completion, and date changes.

**Acceptance:** a fixed clock yields matching dashboard and service results; expiry today, tomorrow, day 30/31, non-expiring qualifications, incomplete training, pending renewals, and empty requirements have tests. Rounding never grants eligibility.

### 5 Notifications, audits, and reports

- [x] Add daily idempotent expiry reconciliation and in-app notifications for staff/managers, with deduplication and unread controls.
- [x] Add officer-authored facility/regional audits with immutable readiness snapshots.
- [x] Add findings, assigned managers, due dates, corrective notes, and resolution workflow.
- [x] Add scoped CSV readiness, expiry, training, and findings reports with formula-injection handling.
- [x] Document `php artisan schedule:work` and the effect of stopping it.

**Acceptance:** repeating the scheduled command does not duplicate alerts/history; status-on-read stays correct without it. A later credential renewal does not rewrite old audit snapshots. Exports and finding updates respect scope.

### 6 Restricted auditor API

- [x] Install/configure Sanctum compatibly with the chosen framework; separate API routes/controllers/resources.
- [x] Add Admin token provisioning/revocation, expiry, API-only accounts, abilities, regional scoping, and throttling.
- [x] Implement `GET /api/v1/verifications/{reference}` and the documented minimal response.
- [x] Add example requests with placeholders and tests for 200/401/403/404/429, revocation, expiry, and sensitive-field exclusion.

**Acceptance:** an auditor can verify only assigned-region evidence; tokens do not grant browser or private-file access. A reference describes its submission rather than employee-wide eligibility.

**Milestone 6 evidence:** 241 tests / 1,778 assertions passed against isolated MySQL, including 41 new tests / 321 assertions. Sanctum 4.4.0 was explicitly approved and installed without other package updates. Three additive development migrations preserved records and backfilled opaque employee references. Pint, strict Composer validation, and Vite build passed. The local Herd anonymous request returned 401 JSON with the IP limiter header. Authenticated API behavior is established by tests with real hashed tokens; no development token was issued for screenshots.

**Defense walkthrough:** [API routes](../routes/api.php) use pre-authentication IP limiting, Sanctum, explicit auditor checks, and an account limiter. [EnsureAuditorToken](../app/Http/Middleware/EnsureAuditorToken.php) separates identity, literal ability, active account/region, and pinned-region checks. [VerificationController](../app/Http/Controllers/Api/V1/VerificationController.php) scopes current active Staff assignments before lookup. [VerifyCredentialReference](../app/Actions/VerifyCredentialReference.php) evaluates the requested submission through the shared predicate; [VerificationResource](../app/Http/Resources/VerificationResource.php) allows nine safe fields only. The review timestamp comes from the latest review event. An older valid approval is never substituted for a pending reference.

[IssueAuditorToken](../app/Actions/IssueAuditorToken.php) locks/rechecks the account, grants one ability, pins its region, and stores the next Manila midnight as an exclusive UTC expiry cutoff. The Sanctum callback requires expiry strictly in the future. The controller returns plaintext once directly, without flash/session storage. Assignment changes lock the account and revoke tokens on role/region change or deactivation; numeric form IDs are normalized so unchanged assignments preserve tokens. The IP limiter wraps Laravel throttling before authentication because Laravel normally prioritizes route authentication ahead of throttling. Limits are 120/minute/IP and 60/minute/account across tokens.

**Browser review:** Administrator Auditor access was inspected at 1280×800 and 390×844. The empty state explains the required regional account assignment and links to account management. No page-level horizontal overflow was measured. With no eligible auditor or issued token present, populated issuance/list behavior remains covered by HTTP tests rather than a browser walkthrough. GitHub's official static token screenshot was compared with CheckPoint's actual captures; no authenticated GitHub or native mobile session is claimed. See [reference observations](Development-Decisions.md#auditor-api-and-access-review).

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
- Each qualification belongs to one safety domain and has `requires_training` and `requires_expiry` flags. Training completion is recorded separately. Evidence validation, review, and employee-wide compliance are implemented.
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

### Training request walkthrough

| File (inside `main/`) | Responsibility |
|---|---|
| [TrainingCourseController.php](../app/Http/Controllers/TrainingCourseController.php) | Publishes/archives offerings and prevents qualification remapping after enrollment. An archived offering stays archived so renewals use a new record. |
| [SaveTrainingCourseRequest.php](../app/Http/Requests/SaveTrainingCourseRequest.php) | Validates title, description, status, and an active training-required qualification/domain. Archived records may retain their inactive qualification. |
| [TrainingEnrollmentController.php](../app/Http/Controllers/TrainingEnrollmentController.php) | Saves the signed-in Staff account's enrollment, never a submitted employee ID. Locks the course while checking it is open, then uses `firstOrCreate()` and a unique employee/course pair for repeat submissions. |
| [TrainingCompletionController.php](../app/Http/Controllers/TrainingCompletionController.php) | Officer-only completion. Locks the enrollment, rejects own/already-completed records, and stores completion date, officer ID, and optional feedback together. |
| [CompleteTrainingRequest.php](../app/Http/Requests/CompleteTrainingRequest.php) | Validates a calendar date between enrollment and today in Asia/Manila. Enrollment timestamps stay in UTC; completed_at is a calendar-date column. |
| [EmployeeTraining.php](../app/Models/EmployeeTraining.php) | A normal Eloquent model for the enrollment pivot because the relationship has its own ID, dates, state, and reviewer. Its visibility scope also constrains aggregate counts. |
| [TrainingCourse.php](../app/Models/TrainingCourse.php) | Belongs to one qualification, has many enrollment records, and belongs to many employees through the same pivot. Published course metadata is browsable; unpublished history is visible only within the viewer's enrollment scope. |

Defend the distinction between the two relationships: `User::trainingCourses()` describes course enrollment through `employee_trainings`; `User::qualifications()` describes effective qualification awards through `employee_qualification`. Neither enrolling nor recording completion writes the qualification award pivot. Training evidence references an owned matching enrollment, which must be completed before Officer approval.

The database unique key enforces one enrollment per employee/offering, even when requests repeat. A transaction and row lock serialize completion updates; a second request sees the completed state and cannot overwrite the first officer's record. Course updates and enrollment use the same course lock so an archive and enrollment do not race on the publication state. All update paths still require their Gates; a valid date alone never authorizes completion.

Useful training demonstrations: Staff submits again without getting a duplicate; Admin is denied completion despite managing courses; a manager sees only scoped totals; an archived completed course remains in Staff history; changing a course's qualification after enrollment is rejected; a Manila date is accepted across a UTC midnight boundary. `TrainingCourseTest` and `TrainingEnrollmentTest` exercise these rules. Course content delivery, exams, completion corrections, and CSV progress exports are outside this milestone; renewal offerings use new records.

### Credential request walkthrough

| File (inside `main/`) | Responsibility |
|---|---|
| [StoreCredentialDocumentRequest.php](../app/Http/Requests/StoreCredentialDocumentRequest.php) | Validates content-based MIME/size, readable images, issue/expiry dates, active qualification/domain, and ownership/mapping of a training enrollment. Submission can precede training completion. |
| [SubmitCredential.php](../app/Actions/SubmitCredential.php) | Stores a generated name on the private local disk, creates metadata and a submitted event in one DB transaction, and removes the new file if persistence fails. Existing evidence is preserved. The employee identity/assignment, reference, path, and status are server controlled. |
| [CredentialDocumentController.php](../app/Http/Controllers/CredentialDocumentController.php) | Provides a scoped list/status filter, submission form, and read-only details/history. Its detail view uses the current selection query and handles unavailable files. |
| [CredentialDownloadController.php](../app/Http/Controllers/CredentialDownloadController.php) | Resolves permission/ownership before opening a private stream. Returns an attachment with a reference-derived name, no-store, nosniff, and sandbox headers; missing files return a controlled 404. |
| [ReviewCredential.php](../app/Actions/ReviewCredential.php) | Serializes reviews with employee/document row locks. Checks pending state, actor separation, rejection reason, stored file availability, and completed matching training before approval. Saves status, a decision event, and the effective pivot link atomically. |
| [SelectEffectiveCredential.php](../app/Actions/SelectEffectiveCredential.php) | Finds an approved document valid today in Asia/Manila, with required completed training. It filters candidates before choosing the newest issue date/ID, so a new pending/rejected/expired submission cannot mask an older valid approval. |
| [CredentialDocument.php](../app/Models/CredentialDocument.php) and [CredentialEvent.php](../app/Models/CredentialEvent.php) | Define metadata, current terminal review state, append-only workflow events, and relationships. Private paths are hidden from serialization. There are no HTTP metadata/event edit or delete routes. |

A credential's review status is `pending`, `approved`, or `rejected`. Expiry is evaluated separately, so a historical approval is never rewritten into an expiry decision. The events record submission and review actors, dates, and feedback. The record keeps its submission-time facility/job IDs; current employee assignment still controls later scoped compliance access. Neither managers nor directors may read raw private evidence or review details through these routes.

The `employee_qualification.credential_document_id` link is a materialized current-evidence pointer updated after review. It is not a source of truth for date validity: time may pass without a new write. The detail page calls the selection action on read. Milestone 4 reuses the selection predicate for whole-employee eligibility and reconciles assessment history, rather than treating an old pivot as proof of current compliance.

Defend the failure boundaries: a database transaction cannot roll back a filesystem write, so submission explicitly cleans up its newly stored file. A failed history insert rolls back the new document; a failed review event rolls back the review status and pivot. A review locks the employee before the document so simultaneous reviews of different submissions for the same employee serialize their effective-link update. A stale second decision sees the terminal state and fails. Rejection feedback is required and HTML-escaped. Approval checks that the file is available; missing historical files are shown without exposing a storage path.

`CredentialSubmissionTest` uses real test-mode uploaded files for content cases. Laravel's upload fake normally derives MIME from the supplied name; relying on that fake would miss renamed script payloads. Tests also cover JPEG/PNG bytes, file-size limits, failed writes, rollback cleanup preserving old evidence, transfer context, role/ownership denial, safe attachment headers, stale decisions, incomplete training, pending renewals, non-expiring qualifications, and the Manila midnight expiry boundary. Tests use a fake disk and the dedicated MySQL test database; no private demonstration uploads are reset.

Content-based MIME checks are not antivirus scanning or full PDF parsing. Downloads are attachments on authenticated routes, not inline public URLs. Backups and the final demonstration dataset remain milestone 7 work; no nonexistent seeded evidence is presented as a working download.

## Compliance calculation and history defense walkthrough

Start with `AssessEmployeeCompliance::handle()`. It reads the current job's required qualifications, evaluates each one, counts the satisfied requirements, and compares **exact counts** for eligibility. The percentage is a rounded display value. Missing facility/job, inactive employee/assignment/region, no requirements, or an inactive required qualification/domain returns **Not assessable** with a null percentage. Inactive requirements remain in the required set: silently removing them could falsely grant eligibility.

`SelectEffectiveCredential::isQualifying()` is the shared evidence predicate used by review-time selection and assessment-time selection. It checks active configuration, approval, issued/expiry calendar dates, and the linked completed enrollment when training is required. The enrollment must belong to that employee and its course must map to that qualification. Completion alone is never enough. `choose()` selects the latest issue date and then highest ID among valid evidence; a pending/rejected/expired renewal cannot displace an older valid approval. Course archival retains completed training history.

Each request captures one immutable Manila date. Evidence is valid through expiry day. An effective approval expiring on or before today + 30 days shows a warning without losing eligibility. Without effective approval, the display priority is pending, latest reviewed rejection, expired approval, then missing. Required training incompleteness is also displayed separately. Optional evidence never changes the required count. The legacy award pivot is a convenient review-time pointer and may become stale; assessment never uses it as the source of validity.

The assessor has no writes. `DashboardController` and `QualificationMatrixController` eager-load its declared relations once for the scoped staff set. The calculation performs no per-person/per-qualification queries once loaded. Dashboard counts include current Staff accounts, including inactive staff as not assessable; its eligibility rate uses assessable staff only. Requirement warning/missing/expired/training counts cover assessable employees only. Pending documents and enrollment totals include all scoped Staff, with optional evidence explicitly included. Matrix columns are the union of required qualifications on the current paginated page, with N/A for a requirement absent from a job.

`RecordComplianceAssessment` handles the separate history concern. It locks the employee, reloads inputs, computes a primitive snapshot, and hashes material fields while excluding the assessment date. It compares only with the latest history row; an unchanged result is skipped, while returning to an earlier state appends a new row. The returned result always has today's date even when no row was appended. Stored snapshots keep assignment names, qualification/domain names, training/expiry rules and states, selected evidence ID, counts, actor/source, and assessment date. They exclude private paths, downloads, review notes, and completion feedback. There are no update/delete routes for these snapshots.

Assignment changes (including Staff role removal), submissions, reviews, and completions record history inside the same business transaction. Submission/review/completion all lock the employee before child records to keep lock ordering consistent. Requirement sync and qualification updates commit their configuration change first, then reconcile affected Staff one at a time. This avoids holding a configuration write lock while waiting for an employee lock; if bulk reconciliation is interrupted, the already-saved configuration remains authoritative and reads/the command repair history. Other catalog edits are reconciled on employee detail/history reads or the next daily run. The current data is computed live immediately on aggregate pages; aggregate reads do not create one history write per employee.

`ComplianceHistoryController` scopes the employee before reading snapshots. After a transfer, the old manager gets 404 and the new manager can see retained historical assignment context. Managers/directors receive summary states without access to private evidence or notes. `checkpoint:reconcile-compliance` runs idempotently for Staff at 00:05 Asia/Manila with overlap protection. Run it manually for a classroom demonstration or keep `php artisan schedule:work` running. A stopped scheduler delays history recording of date-only changes; it does not make dashboard/matrix/detail eligibility stale. This command also delivers deduplicated expiry notifications; see the milestone 5 walkthrough below.

Defense questions to practice:

- **Why not calculate from the award pivot?** A cached review-time link does not expire itself; the predicate must check today's date.
- **Why can a training-completed employee be ineligible?** Every required qualification still needs approved, valid evidence linked to the matching completion.
- **Why is an employee with no requirements not 100% compliant?** There is no configured basis for assessment; report null and request configuration.
- **Why keep calculation and history separate?** Reads can calculate accurate status without unnecessary writes, while workflows preserve a material audit trail.
- **Does a 30-day warning block duty?** No. The approval stays valid through expiry day; the warning requests renewal.
- **How does this help the later API?** The shared predicate can evaluate a referenced submission. The API must verify that reference itself, rather than substitute a newer approval or report employee-wide eligibility. Authentication, regional scope, resources, and rate limiting remain milestone 6.

`ComplianceAssessmentTest` covers evidence precedence, required-only counts, training ownership/linkage, future dates, inactive configuration, null percentages, midnight/day-30/day-31 boundaries, stale award pointers, and eager-load query behavior. `ComplianceHistoryTest` covers deduplication, state returns, retained snapshots, workflow hooks, transfers, and date reconciliation. `ComplianceDashboardTest` covers denominators, scope, N/A cells, escaped names, access rejection, and private-data exclusion. Tests run only against `checkpoint_test`.

## Interface redesign and product quality

The product shell uses grouped role-aware navigation, an account menu, a mobile native-dialog drawer, and shared status/empty-state components. Status colors always include text. Forms keep visible labels, and shared inputs associate validation feedback with controls. Lists use one surface with subtle row separators instead of one bordered card per record. Secondary rules and references are disclosed on demand.

`resources/js/forms.js` contains two small Alpine data factories. `assignmentForm` reveals and requires the applicable facility/job/region fields and clears values that no longer apply. Staff can deliberately remain awaiting assignment; managers require one facility; directors/auditors require a region; global roles have neither. `evidenceForm` narrows enrollments to the selected qualification and changes training/expiry requirements. The server's existing Form Requests still validate every submitted combination. Browser state cannot grant permissions or approve evidence.

The Staff overview leads to the employee's own readiness and next steps. Readiness details combine each requirement's status, training progress, and next action in a single list. Managers and officers retain scoped summaries and task links. Account and employee search validate length and retain the existing query scope; pagination preserves search input. A selection prompt has no data value and is disabled, while every real option describes a domain choice.

The [product experience requirements and reference comparison](Development-Decisions.md#product-experience-requirements) are ongoing acceptance criteria. The current public references are guided/sample interfaces; only observed interface patterns are claimed. Deployment still needs real organization configuration and a working mail transport. No deployment or production-readiness claim follows from this UI work alone.

Run `node --test tests/forms.test.js` for dependent-selection transitions and PHPUnit's `InterfaceWorkflowTest` for scoped search, Staff next-step routing, and label coverage across current forms. Use the browser to verify dynamic rendering, dialog keyboard/focus behavior, and mobile overflow, which server-side tests alone cannot establish.

Browser review on 2026-11-11 covered the authenticated Admin overview, mobile navigation, and account role transitions. At 390px, the overview/account/matrix pages did not overflow horizontally. The navigation dialog focused its close control, locked background scrolling, and returned focus to the opener after Escape. Client-only role switches correctly enabled/required facility for managers and region for directors, and disabled/cleared scope fields for global roles; no account changes were saved during these checks. Additional checks covered the course catalog at desktop/390px and the qualification matrix at 320px, with no page-level horizontal overflow. Account options contained 18 regions and 18 hubs without legacy starter names. The reference tour and CheckPoint screenshots were captured for visual comparison. These manual checks used the signed-in Admin session; they do not claim a separate manual Staff/Officer session.

The organization seeder now includes all 18 Philippine regions and fictional hubs in regional logistics locations. It renames only the exact legacy stock names, preserves primary keys and inactive settings, and refuses ambiguous name collisions without partial writes. The existing assessment snapshots are not rewritten. See the [source and data policy](Development-Decisions.md#philippine-organization-data).

## Definition of done

A workflow is complete when its persistence, validation, policy, UI/API, error states, focused tests, usable seed data, and documentation are present. Run relevant tests and formatter/build checks; before handover run the full suite. Report actual results and limitations. Never run tests or destructive seed/reset commands against a populated development database.

## Development log

| Date | Change | Evidence |
|---|---|---|
| 2026-11-05 | Initial proposal, implementation plan, and prompt | Existing documentation-only commits |
| 2026-11-10 | Reconciled Word proposal with owner decisions; added training/enrollment, precise rules, services, milestones, and co-developer prompt | Documentation PR; no application implementation or runtime tests |
| 2026-11-10 | Implemented foundation and added API learning guide | Laravel 13.40/Fortify 1.41, 22 tests/141 assertions on isolated MySQL, Pint, Composer validation, Blade compilation, and Vite build; main local service configuration pending |
| 2026-11-10 | Implemented organization/qualification management, job requirements, scoped employee pages, and defense walkthrough | 69 tests/454 assertions on isolated MySQL; additive migration; Pint, Composer validation, Blade and Vite checks |
| 2026-11-10 | Implemented training courses/enrollment, officer completion, scoped progress, and training defense walkthrough | 105 tests/702 assertions on MySQL; additive migration; Pint, Composer validation, Blade and Vite checks |
| 2026-11-10 | Implemented private credentials, immutable review events, current-evidence selection, and defense walkthrough | 142 tests/963 assertions on MySQL; additive migration; upload/privacy/failure-path coverage; Pint, Composer validation, Blade and Vite checks |
| 2026-11-10 | Implemented live compliance, scoped dashboards/matrix, material snapshots, and daily history reconciliation | 169 tests/1,142 assertions on isolated MySQL; additive migration; Pint, Composer validation, Blade and Vite checks |
| 2026-11-11 | Reworked task navigation, responsive layout, dependent forms, accessible labels/statuses, and Philippine organization data; recorded product/reference standards | 178 tests/1,261 assertions on isolated MySQL; six Node interaction tests; desktop/mobile Admin browser checks; Pint, Composer, Blade, Vite, and documentation checks |

## Risks and responses

| Risk | Response |
|---|---|
| Full scope exceeds available course time | Use staged vertical slices; record deadline/team capacity before scheduling milestones. |
| Private evidence or reports cross scope | Central policies plus scoped queries and cross-region/facility tests. |
| Renewal/expiry/training rules diverge | One assessment action, fixed-clock tests, immutable evidence, and explicit enrollment requirements. |
| Alerts silently stop | Document scheduler process and calculate current validity on read. |
| Test commands destroy local work | Separate MySQL test database and environment guards. |
| Existing MySQL service is unavailable | Check service/version/access before scaffolding; document setup without silently switching database engines. |


## Milestone 5 walkthrough for the defense

`ReconcileExpiryAlerts::currentEvents` reads the same current assessment and qualifying-evidence predicate as the readiness pages. It emits warnings only for effective required approvals in the 30-day window. Without a current approval, it selects the most recently expired qualifying approval, including when a renewal is pending. Optional evidence, unassigned/inactive employees, and inactive/incomplete configuration do not generate alerts. Non-expiring approvals need no warning.

`checkpoint:reconcile-compliance` records material history and reconciles alerts per employee. The alert operation locks the employee row and creates a Laravel database-notification row with a stable UUID derived from recipient, employee, qualification, document, and event. The primary key makes retries safe even after a partial command run. `notifications()` and `unreadNotifications()` are Laravel's existing relationships. A separate notification class/queue is unnecessary for these database-only, synchronous events. Notification reads, unread counts, and read updates additionally require ownership and current employee scope. A manager's former employee alerts are retained in storage but hidden after transfer. Renewed/removed requirements show old alerts as no longer current without deleting them; relevance is calculated on read, not trusted from yesterday's scheduler result. Unread retained history remains in the unread count until marked read.

`CaptureAudit` uses a transaction to read a consistent MySQL snapshot, evaluates employees at one Manila date, and stores summary results as JSON with author and scope. It does not create missing compliance-history rows as a side effect. There are no audit update/delete routes. Later evidence, job/requirement changes, and assignment names do not rewrite this JSON. `Audit::visibleTo` gives directors regional access and managers only facility-specific snapshots. A manager can still see their own facility's finding from a regional audit through `AuditFinding::visibleTo`, without receiving the full regional readiness snapshot.

Finding creation validates audit/facility/active-manager consistency, then checks mutable assignment again inside its transaction. `FindingUpdateController` locks the actor/optional replacement manager before the finding. It appends activity before changing the current status. Assigned current-facility managers can record progress or submit for verification. Officers can reopen, reassign within the facility, and resolve submitted work; the latest corrective author and assignee cannot self-verify. Activity has no edit/delete route. Resolution timestamps clear when an officer reopens work. Overdue means unresolved with a due date before today in Manila.

`BuildComplianceReport` supplies both preview and CSV rows. Employee reports reuse `User::visibleTo`; finding reports use the fixed finding scope. Explicit facility filters are restricted to the viewer's assignments. The preview stops at 25 rows and omits repeated Region/Assessed on context columns; CSV keeps every column and iterates in 100-row chunks. Training shows enrollments, not awarded qualifications. Expiry shares the alert event calculation and stays current when scheduling stops. CSV excludes private paths, review feedback, and corrective notes, quotes cells with `fputcsv` and no proprietary escape character, and prefixes formula markers (including leading whitespace/BOM/control characters) with an apostrophe. PDF export remains deferred.

Defense exercise: capture an audit; record and approve a renewal; compare the unchanged audit with live People/readiness; assign and submit a finding; attempt manager self-resolution; verify it as a separate officer; filter and download a report; run reconciliation twice and show that notification/history counts are unchanged. Use synthetic evidence. Database factories support isolated tests; no fabricated audits or findings are inserted into the development database.

Operationally, run `php artisan checkpoint:reconcile-compliance` manually, or `php artisan schedule:work` locally. The existing schedule remains daily at 00:05 Asia/Manila with overlap protection. Production must invoke `schedule:run` each minute and monitor failures. If scheduling stops, new alerts and date-only history are delayed; all current assessments, expiry reports, and alert relevance remain accurate. No new package, external provider, or queue service was added.

Validation for this milestone: the full isolated MySQL suite passes with **200 tests / 1,457 assertions**, including 22 new notification, audit/finding, scope, and CSV cases. Eight Node interaction tests, Pint, strict Composer validation, production Vite build, and Blade compilation pass. Additive migrations were applied to the local development database without resetting existing data. Remote CI is not claimed by these local results.

Manual browser evidence: signed-in Admin reports at 1280px/390px and Officer audit capture at 1280px/390px, labels and overflow measurements, NCR/Central Luzon dependent facility options, and comparison with official SafetyCulture/Mitti and OpenProject screenshots. No audit/finding business records were created for this review; full finding/manager browser interaction remains to be reviewed.
