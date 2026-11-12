# Implementation plan and progress

## Current status

**Stage:** all planned modules implemented; local synthetic dataset loaded; Manager/Officer workflow and Admin issuance-form reviews complete. Retained backup restored into the separate recovery checkout; table counts, private-file hashes, migrations, and restored-access invalidation verified. Fresh recovery sign-in/protected-file/history walkthrough remains pending.
**Last updated:** 2026-11-12.
**Application status:** Authentication/onboarding, Admin organization and qualification management, job requirements, scoped employee pages, safe deactivation, private-storage configuration, and MySQL tests are implemented. Courses, enrollment, completion, and scoped training progress are implemented. Private submissions, protected downloads, review events, and effective-evidence selection are implemented. Live compliance calculations, scoped dashboards/matrix, material assessment history, and daily history reconciliation are implemented. In-app expiry alerts, immutable audit snapshots, corrective findings, and scoped CSV reports are implemented. The restricted auditor API and Admin token management are implemented.

The full scope is in [System specification](System-Proposal.md), decisions and local service needs in [Development decisions](Development-Decisions.md), and the next-session brief in [Kickstart prompt](Kickstart-Prompt.md). The owner will provide the deadline and team experience later; no delivery date is promised.

**Release acceptance follow-up:** PRs #15, #16, #17, and #19 are now merged into `main` (confirmed on GitHub). A fresh branch starts from merged revision `4228dc2`. The full serial suite at that revision passed **255 tests / 2,023 assertions**, including the real MySQL/private-file recovery check; the Vite build, all 8 JavaScript form tests, and strict Composer validation passed. The Manager-to-Officer corrective workflow has now also been exercised through the real Browser, as described below. These results do not complete production deployment acceptance.

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

[IssueAuditorToken](../app/Actions/IssueAuditorToken.php) locks/rechecks the account, grants explicit document and compliance read abilities, pins its region, and stores the next Manila midnight as an exclusive UTC expiry cutoff. The Sanctum callback requires expiry strictly in the future. The controller returns plaintext once directly, without flash/session storage. Assignment changes lock the account and revoke tokens on role/region change or deactivation; numeric form IDs are normalized so unchanged assignments preserve tokens. The IP limiter wraps Laravel throttling before authentication because Laravel normally prioritizes route authentication ahead of throttling. Limits are 120/minute/IP and 60/minute/account across tokens.

**Browser review:** Administrator Auditor access was inspected at 1280×800 and 390×844. The empty state explains the required regional account assignment and links to account management. No page-level horizontal overflow was measured. With no eligible auditor or issued token present, populated issuance/list behavior remains covered by HTTP tests rather than a browser walkthrough. GitHub's official static token screenshot was compared with CheckPoint's actual captures; no authenticated GitHub or native mobile session is claimed. See [reference observations](Development-Decisions.md#auditor-api-and-access-review).

### 7 Full demonstration and handover

- [x] Seed at least 1,000 total synthetic records with valid relationships and all important states; record counts by table (1,690 added records verified on isolated MySQL).
- [x] Provide harmless evidence fixtures and an opt-in development command for accounts covering each role/scope; no shipped password or automatic token creation.
- [x] Run full PHPUnit tests on MySQL, Pint, and production asset build; record exact commands/results.
- [x] Exercise register -> assign -> enroll -> complete -> submit -> review -> assess -> audit/report -> API verification through joined HTTP tests. The full human browser walkthrough remains open.
- [x] Verify notification delivery, real log-mail password reset, unauthorized access, and snapshot/history retention in automated tests; human walkthroughs remain separately listed below.
- [x] Document fresh setup, scheduler, test-database isolation, backup/restore of MySQL and private files, limitations, and classroom walkthrough.

**Acceptance:** a teammate can reproduce the demo from a fresh checkout; all eight mandatory requirements below have implemented evidence. Do not mark these tasks complete based on documentation alone.

**Milestone 7 validation (2026-11-11):** `php artisan test --compact` passed **249 tests / 1,928 assertions** on isolated MySQL `checkpoint_test`, including 8 new tests / 150 assertions. `php vendor/bin/pint --dirty --format agent`, `npm run build`, `node --test tests/forms.test.js` (8 tests), strict Composer validation, Blade compilation/clear, command discovery, schedule inspection, Markdown local links/fences, and Git whitespace checks passed. No dependencies or migrations were added. No reset, synthetic account creation, or token issuance was run on the development database. Remote CI and the remaining human walkthroughs are not established by these local results.

### Synthetic dataset and classroom walkthrough

From `main/`, after normal setup and organization seeding, run `php artisan checkpoint:seed-synthetic` interactively. Enter and confirm a private password (12+ characters with letters and numbers). All synthetic accounts share that chosen classroom password. No password is committed, printed, accepted as a command argument, or sent to chat. The command is restricted to `local`/`testing`; it is deliberately absent from `DatabaseSeeder`, normal migrations, and deployment scripts. Do not install these accounts in a real deployment.

[CreateSyntheticDataset](../app/Actions/CreateSyntheticDataset.php) creates fictional relational history in one transaction, then uses the existing selection, assessment/history, alert, and audit actions. Sample submission/review and finding activity rows are built directly as fixture history; this does not claim they were entered through the UI. [CompleteComplianceWorkflowTest](../tests/Feature/CompleteComplianceWorkflowTest.php) separately exercises actual HTTP writes across the modules. All evidence points to a working private copy of the [harmless PNG](../database/fixtures/synthetic-evidence.png), visibly labeled synthetic and not a certificate. A failed run rolls back its database writes and removes its new file. It does not remove existing files.

The command requires active facilities in 18 distinct active regions and chooses the lowest-ID active facility per region, ordered by region ID. It never changes existing organization configuration. Reserved `@checkpoint.example` addresses or either synthetic job/domain name cause a refusal before writes; rerunning cannot reset passwords, reopen reviews, change dates, or duplicate the dataset. Existing edited records remain intact. Use a separate empty local database for a new baseline, rather than deleting a used dataset. Concurrent invocations are not supported; run setup once from one terminal.

Exact added counts on isolated MySQL (not counting the existing regions/facilities or framework tables):

| Table | Added |
|---|---:|
| users | 220 |
| safety_domains | 1 |
| job_roles | 2 |
| qualifications | 2 |
| job_role_qualification | 2 |
| training_courses | 3 |
| employee_trainings | 144 |
| credential_documents | 270 |
| credential_events | 468 |
| employee_qualification | 144 |
| compliance_assessments | 164 |
| audits | 18 |
| audit_findings | 72 |
| finding_updates | 108 |
| notifications | 72 |
| **Total** | **1,690** |

These counts are for a fresh fixture baseline with no additional existing facility managers. Additional real local managers in the chosen facilities may receive the normal expiry alerts, so the command reports actual added counts rather than hard-coding the total. It creates zero API tokens. The default organization seeder's records are additional to this count. Dates are relative to the day of creation in Asia/Manila; statuses age normally and are never reset on rerun.

| Account address | Purpose |
|---|---|
| `admin@checkpoint.example` | Administrator for configuration and token management |
| `officer@checkpoint.example` | Officer for evidence review, completion, audits, and verification |
| `manager.NN@checkpoint.example` | Manager of the selected facility in scope NN |
| `director.NN@checkpoint.example` | Director of that region |
| `auditor.NN@checkpoint.example` | API-only Auditor of that region; no token issued |
| `staff.NN.SCENARIO@checkpoint.example` | Staff scenario in that facility |
| `unassigned@checkpoint.example` | Onboarding without employee access |
| `inactive@checkpoint.example` | Disabled account, rejected by browser login |

NN is `01` through `18` in region-ID order, not the Philippine region number. Inspect each account's assignment in Accounts & access. With only the normal organization seed, 01 is NCR and 05 is Central Luzon. SCENARIO is `current`, `expiring`, `expired`, `pending`, `rejected`, `missing`, `incomplete`, `renewal`, or `observer`. The first eight require Cold Chain Handling (completed training plus expiring evidence) and non-expiring Workplace Safety Orientation. The observer job has no requirements and is not assessable.

1. Sign in as Staff current/expiring/renewal: each starts eligible. Expiring evidence expires 15 days after creation. Renewal retains an older approved document and a separate pending reference.
2. Compare expired/pending/rejected/missing/incomplete: each starts not eligible for a different reason. Incomplete has an enrolled course, pending training evidence, and approved non-training orientation. Completion alone does not make the pending evidence valid.
3. As Officer, complete the incomplete enrollment with today's date, review its pending evidence, and inspect the resulting history. Start new uploads for rejected/expired renewals; never overwrite historical files or decisions.
4. As Manager, inspect the four corrective findings in the assigned facility, update open/in-progress work, and submit it. As Officer, verify separately. Existing resolved work retains manager and officer activity. Compare a different region as Director/Manager to demonstrate denied scope.
5. Inspect Reports and download CSV. Run reconciliation twice; unchanged state adds neither duplicate history nor duplicate alerts. Explain that live status still calculates when scheduling stops.
6. Follow [API learning path](API-Learning-Path.md): privately issue an expiring token only when needed, compare approved/pending references, try another region, then revoke. Never project a token.
7. Register a fresh account, assign it, enroll, complete, upload the harmless fixture, and review it through the real UI to demonstrate the entire user journey rather than only prebuilt history.

The dataset was first verified in disposable `checkpoint_test`, then added to the owner's local `checkpoint` database on 2026-11-11 using privately supplied password input. The command reported 1,690 additions; read-only inventory confirmed 223 accounts (the original three plus 220 fictional accounts), 270 submissions, 468 credential events, 144 enrollment records, 18 audits, 72 findings, 108 finding updates, and 72 notifications. Existing assessment history remains alongside 164 added snapshots. Original organization records/accounts were preserved; zero API tokens exist. The one-use private input file was removed after setup, and no password was printed or committed. Do not rerun the dataset command in this checkout.

Remaining human checks: populated token-screen review, Officer/Manager corrective-work desktop/mobile walkthrough, password reset in the user's local browser, and restoration into a separate checkout/database from an actual retained backup. Real log-mail delivery and MySQL/private-file round-trip recovery are exercised by the operational tests below. No new interface changes or browser screenshots are claimed for the local setup operation.

The populated Officer read/form review is now complete at 1440×1000 desktop and 390×844 mobile emulation: audit list/detail, finding activity/update form, mobile region-dependent facility selection, status filtering, and modal navigation. No record was submitted or token issued. It identified and fixed a preselected In progress action on awaiting-verification findings; updates now require an explicit status choice and retain it after validation errors. See [comparison evidence and limits](Development-Decisions.md#populated-officer-acceptance-review-2026-11-11). Manager write/verification acceptance and the other human checks above remain open; pre-fix screenshots do not establish post-edit browser behavior. Focused MySQL validation passed: 15 tests / 193 assertions across InterfaceWorkflowTest and AuditWorkflowTest; Pint and Git whitespace checks passed.

**PR finalization:** at the owner's request, PRs #15, #16, #17, and #19 were marked ready for review after checking their heads were mergeable, their Foundation checks succeeded, and their discussion timelines/review threads had no feedback to address. They were subsequently merged, and `main` now includes the full stack. Merging does not complete the remaining manual acceptance or deploy the system.

### Manager and Officer acceptance follow-up

Signed-in `manager.01@checkpoint.example` was reviewed at 1440×1000 desktop and 390×844 mobile CSS pixels. The dashboard showed the assigned Manila facility and 10 people, and Corrective work showed its four fictional findings. Direct access to another facility's finding `/findings/5` returned 404. The mobile update form had associated labels, no page-level horizontal overflow, and only In progress / Submit for verification actions. Through the actual form, the Manager moved fictional finding #1 to In progress and then Awaiting verification, with explicit acceptance-walkthrough notes. After the owner privately switched accounts, `officer@checkpoint.example` independently resolved it. All three dated activity entries remain; the baseline fixture now intentionally has three additional finding updates and finding #1 is resolved. No history was reset or API token issued.

The review found that a resolved finding still offered Managers an update form whose submissions the server refused. The view now replaces it with resolved-state guidance, while Officers retain the form needed to reopen work. The existing server-side rejection remains in force. Focused MySQL verification passed **17 tests / 201 assertions** across InterfaceWorkflowTest and AuditWorkflowTest, including two new view-behavior regressions. Pint and Git whitespace checks passed. Browser captures preceded the resolved-state guidance fix; the Officer workflow does not exercise that Manager-only branch.

Compared actual screenshots with [OpenProject's work-package editor and Activity screenshot](https://www.openproject.org/docs/user-guide/work-packages/edit-work-package/edit-work-package-71ec275a.png) from its [edit-work-package guide](https://www.openproject.org/docs/user-guide/work-packages/edit-work-package/). Both keep status, responsibility, and dated activity visible; CheckPoint uses a simpler explicit status/note form and independent Officer verification. The guide describes status selection and narrower-screen comment placement, but only its static desktop image was inspected. No authenticated vendor or physical-device testing is claimed.

### Populated Admin access acceptance follow-up

The owner then privately signed in as `admin@checkpoint.example`. Auditor access was reviewed at 1440×1000 and 390×844 CSS pixels with all 18 eligible Auditors present and an empty token list. Every issuance control had an associated label. Expiry defaulted to 2026-12-11, with bounds 2026-11-11 through 2027-02-09. Selecting the longest BARMM auditor option did not cause page-level horizontal overflow, but its native select text was clipped. The form now repeats the selected verification region as wrapping text underneath, including when restoring a previously submitted selection. This uses the existing Alpine.js setup; no dependency or permission change was needed. Captures precede this small presentation fix; no post-edit browser verification is claimed.

No token was issued, exposed, or revoked for this review. Populated token metadata/revocation and one-time secret delivery remain covered by isolated HTTP tests, not a live-token browser walkthrough. The owner confirmed the private browser password-reset check passed on 2026-11-11 for `unassigned@checkpoint.example`: the reset link was opened privately, a new private password was set, and sign-in succeeded. This is owner-reported acceptance; no reset link, password, or development log was inspected or recorded by the agent. This account now uses its new private password rather than the shared sample password. Retained-backup recovery into a separate checkout/database remains open. Existing API learning examples explain how to issue/test/revoke a token privately when the team is ready.

Follow-up verification: all **16 AuditorTokenManagementTest cases / 77 assertions** passed; Blade compilation/clear and the Vite build passed. Combined with the 17 interface/audit tests above, the affected test files passed **33 tests / 278 assertions**. No new tests were added for the region text alone, since it is a presentation-only change. The full-suite result at the merged baseline remains separately identified above.

### Retained-backup rehearsal

On 2026-11-12, after PR #20 was merged, a paired backup was retained outside Git at `C:\Users\ruper\CheckPointBackups\20261112-112355`, with access restricted to the current Windows account. It contains the real local `checkpoint` SQL dump, all four private files, the original private environment/application key, revision `344f2e9`, and a private manifest of per-table counts and SHA-256 hashes. No scheduler/queue writer process was detected before the snapshot. The active application was briefly placed in maintenance for consistency, then reopened in a `finally` cleanup; source records and sessions were preserved. Its login page returned HTTP 200 afterward.

The dump contains 26 tables and 223 accounts, with zero API tokens. SQL/archive hashes and every archived private-file hash were checked successfully. A separate clone at `C:\Users\ruper\CheckPointRecovery\checkpoint-recovery` is pinned to the recorded revision. Its `main/.env` retains the application key but points only to `checkpoint_recovery`, uses a distinct session cookie, local mail logging, and file cache. Composer installed the recorded lockfile without updates; `npm ci` and the asset build passed. Package discovery/config clear completed. Herd serves the recovery app at `http://checkpoint-recovery.test` under PHP 8.4. It was kept in maintenance until the restore and access invalidation completed; its login now returns HTTP 200. The remote clone connection was interrupted; a local clone without hard links supplied the same recorded revision.

The retained backup was restored on 2026-11-12. The initial safety guard found an existing `checkpoint_recovery` schema, so it stopped before changes. Private administrator inspection confirmed the schema was empty and the existing `checkpoint_recovery@localhost` account had only schema-scoped grants. Its password was configured privately, the retained SQL imported, and all four private files restored. All **26 table counts** matched the manifest before access cleanup, every restored file matched its SHA-256 hash, and the original `APP_KEY` was retained. All 16 migrations were already applied; no migration or application-key regeneration was needed.

While the recovery app was still in maintenance, `checkpoint:invalidate-restored-access` cleared **1 session and 221 remembered logins**; there were zero password-reset links and zero API tokens. A direct database check confirmed all four access-artifact categories were empty afterward, and business table counts were preserved. The recovery app was then reopened. Both the source and recovery login pages returned HTTP 200. Temporary MySQL option files and one-use credential input files were removed; the private recovery password remains only in its ignored local environment.

The data restore and access cleanup are verified. Fresh human sign-in, protected evidence download, and recovered finding/review-history walkthrough remain pending; HTTP 200 does not establish those authenticated checks. Administrative credentials are supplied only through private secret cards; no password, key, SQL contents, or private-file contents are committed here. The source `checkpoint` and disposable test `checkpoint_test` databases were not restore targets, and access invalidation ran only in the recovery checkout. Review post-snapshot account/role/password changes before any real recovery handover.

### Operational acceptance and restore defense

[PasswordResetLogTest](../tests/Feature/PasswordResetLogTest.php) sends through the real Laravel log transport into an isolated temporary log, follows the resulting link, changes the password, and rejects link reuse. It does not use a mail/notification fake and does not read development logs or send external email. [BackupRestoreTest](../tests/Feature/BackupRestoreTest.php), in the `recovery` group, uses real MySQL 8 dump/import and PHP ZIP to remove/recover disposable records and private evidence inside `checkpoint_test`. It verifies bytes, protected downloads, opaque employee references, review events, qualification pivot state, immutable audit contents, encrypted sessions under the retained key, and token validity/revocation at snapshot time. The password-reset log and temporary MySQL option file/dump/archive are removed after the tests.

The recovery test uses `DatabaseMigrations`, not a wrapping transaction: external clients cannot dump uncommitted fixture rows. Both the shared test bootstrap and a second pre-removal check constrain it to testing/MySQL/`checkpoint_test`. Run serially; no other process may use that disposable database simultaneously. If MySQL clients or PHP ZIP are absent, it explicitly skips and establishes no recovery result. Add the existing MySQL 8 bin directory to the test process PATH; no dependency was installed. Instructions are in README's Operational acceptance checks section.

The test demonstrates that revoking a token after making a backup does not revoke the copy in that backup. [InvalidateRestoredAccess](../app/Console/Commands/InvalidateRestoredAccess.php) addresses this at recovery handover: while the recovered application is in maintenance mode with database sessions, `checkpoint:invalidate-restored-access` transactionally clears sessions, remembered logins, password-reset links, and all API tokens. It preserves account/password data, evidence, assignments, and historical records. It refuses a live application or non-database sessions; repeated cleanup is harmless. [InvalidateRestoredAccessTest](../tests/Feature/InvalidateRestoredAccessTest.php) verifies these boundaries and preservation. Maintenance is mocked in the test container so the running Herd application is never put down or logged out.

For the defense, distinguish **recovering data faithfully** from **deciding which recovered access remains authorized**. Retain APP_KEY so encrypted data remains readable, then invalidate restored login artifacts before reopening. Review account, role, and password changes made since the snapshot independently. A later revocation cannot be reconstructed from an older backup alone. No cleanup or restore command was run on the active development database. The retained separate-checkout restore and access cleanup passed; its authenticated human walkthrough remains open.

**Operational validation (2026-11-11):** the full serial MySQL suite passed **254 tests / 2,011 assertions**, including 5 new tests / 83 assertions. The MySQL 8 bin directory was added to PATH for that test process, so recovery executed rather than skipping. Focused mail/recovery and invalidation tests passed, as did Pint, command help/discovery, Markdown local links/fences, and Git whitespace checks. No frontend, dependency, migration, or service configuration changed; prior milestone build results remain recorded above. The read-only development database inventory still showed 3 accounts, no evidence, and no API tokens. No production or development restore/invalidation was performed; remote CI results are not claimed.

### Rubric alignment follow-up (2026-11-12)

The review correctly distinguished the existing optional dataset command from the standard seed entry point. The earlier milestone-7 scale evidence covered the command only; the normal `DatabaseSeeder` and meaningful isolated unit coverage were gaps until this follow-up.

- `DatabaseSeeder` now calls `CatalogSeeder` and `SyntheticDatasetSeeder`. A fresh local/testing installation creates the same 1,690 related dataset records without prompts, using factories with role, assignment, qualification, course, completion, evidence, submission, and review states plus nested relationship generators. Accounts receive random undisclosed passwords; use local log-mail reset or create a separate owner account. Production and any database with existing accounts skip synthetic population. Catalog-only seeding remains available; repeated seeds preserve used accounts and history.
- `DatabaseSeederTest` executes `migrate:fresh --seed` only inside the guarded `checkpoint_test` database, verifies 1,000+ records and working private evidence, and covers production/existing-account/repeat-run protection. Catalog preservation tests target `CatalogSeeder` explicitly instead of relying on a small `DatabaseSeeder` side effect.
- `IsCredentialDateValid` extracts the existing date predicate into one dependency-free rule used by effective-evidence selection. Isolated PHPUnit tests cover Manila-midnight inclusive expiry, future/missing issue dates, required expiry, and non-expiring qualifications. Unit label tests cover all six roles and three compliance states; database/training selection remains feature-tested.
- Two read-only v1 controllers/Resources expose scoped employee compliance and facility rates. Both require literal `compliance:read`; existing document tokens keep only their previous permission, while newly issued Admin tokens receive both read abilities. The lookup scopes are shared with document verification. Resources exclude names/emails/files/review notes. Facility rates use eligible / assessable active Staff, return null without a denominator, and process eager-loaded batches of 100. GETs create no assessment snapshots. See the [API contracts and examples](API-Learning-Path.md#employee-and-facility-compliance-summaries).

**Validation:** the final serial `php artisan test --compact` run passed **303 tests / 2,218 assertions** on isolated MySQL `checkpoint_test`, with MySQL 8 clients on PATH so the recovery group executed. The affected factory/installation checks separately passed **11 tests / 122 assertions**. All 19 tests under `tests/Unit` pass, including 18 meaningful new enum/date cases and the pre-existing placeholder. Pint, strict Composer validation, Blade compile/clear, API route discovery, Markdown local links/fences, and Git whitespace checks passed. Assets and dependencies were not changed. No destructive operation, seed run, or token issuance was performed against the populated development or recovery database. No dependencies or migrations were added. The token-form change is copy only; no new browser/screenshot comparison is claimed for this backend-focused work.

## Mandatory requirement traceability

| Requirement | Planned implementation | Completion evidence |
|---|---|---|
| 1 Multi-role authorization | Six roles, policies/Gates, record scopes | Role and cross-scope denial tests, milestones 0-1 onward |
| 2 Authentication | Register/login/logout/reset/profile/session lifecycle | Authentication and privilege-escalation tests, milestone 0 |
| 3 Advanced relationships | Employee/qualification, job/qualification, employee/course many-to-many; region/staff has-many-through | Relationship tests, milestones 1-2 |
| 4 1,000+ records | Standard `DatabaseSeeder` calls factory-based synthetic population on a fresh local/testing database | `DatabaseSeederTest` runs `migrate:fresh --seed`; relationship/scenario checks in `SyntheticDatasetTest` |
| 5 Upload/download | Private Laravel Storage, validation, protected attachments | File validation, ownership, and failure-path tests, milestone 3 |
| 6 Token API | Versioned document verification, employee compliance, facility rate | `AuditorApiTest`, `ComplianceApiTest`, explicit read abilities, scoped Resources |
| 7 Unit/feature tests | Isolated enum/date business rules plus HTTP/database workflows | `tests/Unit` label/date tests; authorization, uploads, seed installation, and API feature coverage |
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

Content-based MIME checks are not antivirus scanning or full PDF parsing. Downloads are attachments on authenticated routes, not inline public URLs. The milestone 7 command supplies a working harmless PNG for all sample evidence. Backup/restore guidance is in README; an actual restore rehearsal remains a human check.

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
