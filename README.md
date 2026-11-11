# CheckPoint

CheckPoint is a Regional Safety Compliance Management System for healthcare logistics. The Laravel foundation, organization/qualification management, and training courses/enrollment are implemented. Private credentials and review history are implemented. Live compliance calculations, scoped dashboards, the qualification matrix, and material assessment history are implemented. In-app expiry alerts, fixed audit snapshots, corrective work, and scoped CSV reports are implemented. The restricted auditor API and Admin token management are implemented.

## Available now

- Laravel 13.40, Fortify 1.41, Blade, Tailwind 4, Alpine.js, and Vite 8, with committed dependency lockfiles.
- Staff registration, login/logout, password reset through local mail logs, and profile/password changes.
- Six role definitions, unassigned-staff onboarding, and administrator-only account assignments/deactivation.
- Admin management of regions, facilities, jobs, safety domains, and qualifications, with safe deactivation.
- Admin/Officer maintenance of mandatory job qualifications, grouped by safety domain.
- Employee directory and requirement pages limited to the viewer's facility, region, or own record.
- Draft/published/archived courses, Staff self-enrollment, Officer-recorded completion, and scoped training progress.
- Private PDF/JPEG/PNG submissions, protected attachment downloads, append-only submission/review events, and officer review.
- Current qualifying-evidence selection preserves older valid approvals while renewals are pending or rejected.
- Live employee eligibility, qualification matrix, scoped dashboard denominators, and append-only material assessment snapshots.
- Daily Manila-time reconciliation and deduplicated staff/manager expiry alerts, with live status calculated even when scheduling stops.
- Officer-captured facility/regional audits, fixed readiness snapshots, assigned corrective work, officer verification, and retained activity.
- Scoped readiness, expiry, training, and findings reports with CSV downloads protected against spreadsheet formulas.
- Regional, read-only auditor verification with Sanctum 4.4, opaque references, explicit ability checks, expiry/revocation, and rate limits; Admin manages Auditor access.
- An opt-in local synthetic dataset with 1,690 related records, harmless downloadable evidence, all six roles, and a joined HTTP workflow test.
- PHPUnit tests with an isolated MySQL database, Pint, and a GitHub Actions workflow.

The target is a local classroom demo using Herd and MySQL. PostgreSQL is not used. No paid service or extra coding agent is required.

## Local setup on Windows

Use PHP 8.4+, Composer 2, Node 22.12+ with npm, MySQL 8.0+, and Herd. The tested versions are PHP 8.4.27, Composer 2.10.3, Node 22.20.0, npm 11.15.0, and MySQL 8.0.46. Dependency engine requirements and lockfiles remain authoritative.

1. Open Herd. Start your existing MySQL service through Windows Services; starting `MySQL80` may require an administrator terminal. Verify its port and credentials. Do not initialize over an existing MySQL data directory.
2. Through your MySQL administrator client, create an empty `checkpoint` development database and a separate disposable `checkpoint_test` database, both using `utf8mb4`. Create local accounts `checkpoint` and `checkpoint_test` with passwords you choose privately. Grant each account privileges only on its corresponding database. Never use the test database for records you want to keep.
3. Enter the Laravel application in `main/`, install dependencies and copy templates **only if those private files do not already exist**:

```powershell
cd main
composer install
npm ci
Copy-Item .env.example .env
Copy-Item .env.testing.example .env.testing
```

4. Edit `.env` with your development account credentials and actual `APP_URL` from Herd. Edit `.env.testing` with the separate test account credentials/host/port. Keep `DB_DATABASE=checkpoint_test` in the test file. Both files are ignored by Git. Do not paste passwords into chat or commit them.
5. For a fresh installation, generate application keys, run additive migrations, and seed the small organization fixture:

```powershell
php artisan key:generate
php artisan key:generate --env=testing
php artisan migrate
php artisan db:seed
php artisan checkpoint:create-admin
npm run build
```

The administrator command prompts for name, unique email, and a hidden password. No default administrator password is shipped. The repeatable seeder creates all 18 Philippine regions, one fictional hub per region based on real logistics locations, two jobs, two safety domains, two training-required qualifications linked to those jobs, and two published initial course offerings. Re-running it restores those demonstration requirement links only when the job, qualification, and domain are active; it preserves existing qualification settings. The optional synthetic dataset command below adds the classroom records separately. Do not use `migrate:fresh` on development data.

6. From `main/`, run `herd link websys2`. Link the Laravel application directory (`main/`) in Herd; its document root must be `main/public/`. The expected local URL is `http://websys2.test`; confirm Herd's actual URL and match `APP_URL`. Use `npm run dev` for asset hot reload while working, or keep the built assets from `npm run build`.

The default local disk is `storage/app/private`. Do not expose it through a public symlink. Reset messages are written to `storage/logs/laravel.log` with `MAIL_MAILER=log`; open the reset link locally to finish the flow. Daily compliance history reconciliation and in-app expiry alerts are scheduled at 00:05 Asia/Manila. Database sessions/cache are used; no queue worker is needed for the current synchronous setup.

## First walkthrough

1. Sign in as the administrator created by the command above.
2. In another browser session, register a staff account. It must land on **Awaiting your assignment**, with profile access but no employee features.
3. As Admin, open **Accounts**, expand that account, keep the Staff role, and assign a facility and a job together.
4. Refresh the staff dashboard. It shows its assignment and live compliance totals. With required evidence missing, the employee is not eligible; a job with no requirements is not assessable.
5. Try a password reset and profile/password change. Staff must not be able to open `/admin/users` or assign their own role.

Organization and qualification configuration is available through the navigation. Directors/managers can browse their scoped employee directory; staff can view only their own requirements. Dashboard, qualification matrix, employee assessments, and assessment history use the same current employee scope. Auditor accounts are blocked from browser login; their access uses only the regional bearer-token API.

## Checks

After pulling the auditor API changes, run `composer install`, `php artisan migrate`, and `npm run build` from `main/`. Additive migrations preserve existing records and generate opaque employee references. Keep your existing private environment files.

Run these commands from `main/`:

```powershell
composer validate --strict
php artisan config:clear
php artisan test --compact
php vendor/bin/pint --format agent
npm run build
```

Tests use MySQL and may recreate tables in `checkpoint_test`. The test bootstrap refuses any other database name/driver or a non-testing environment. Always use the dedicated test credentials; never point a development database at that name. GitHub Actions provisions a disposable MySQL service and runs tests, validation, formatting checks, and the asset build.

The current application is verified with **254 tests and 2,011 assertions** against MySQL80 on port 3306. Dedicated accounts isolate `checkpoint` from `checkpoint_test`; credentials stay in ignored environment files. Composer validation, Pint, Blade compilation, and the production Vite build passed. Existing development migrations/data are preserved; catalog, training, and credential migrations are additive. Herd serves `main/` at `http://websys2.test`.

## Organization and requirements walkthrough

Run commands from `main/`. After pulling this milestone, run `php artisan migrate` and optionally `php artisan db:seed` for demonstration fixtures; no database reset is needed.

1. As Admin, create a region, facility, job, safety domain, and qualification. Qualifications specify whether expiry and training completion will be required.
2. Open **Jobs & requirements**, choose a job, and select mandatory qualifications. A Compliance Officer can maintain these requirements but cannot manage organization records or account roles.
3. Register a Staff account and assign its facility/job as Admin. Open **Employees** to inspect its inherited requirements. Requirements do not award a qualification or indicate eligibility.
4. Sign in with facility manager/regional director accounts assigned through **Accounts**. They see only employees in their facility/region. A changed employee ID outside that scope returns 404. Staff see only themselves; unassigned Staff remain on onboarding.
5. Try deactivating an assigned facility/job or a required qualification. Validation explains the dependency to resolve. A facility cannot move regions while accounts are assigned. Records are retained and may be reactivated; there are no delete actions.
6. Explain the implementation using the [code walkthrough and defense questions](main/docs/Implementation.md#code-walkthrough-for-the-defense). Continue with the compliance walkthrough below; the auditor API walkthrough is below.


## Training walkthrough

1. As Admin or Compliance Officer, open **Courses** and add a draft course mapped to a training-required qualification. Publish it when ready for enrollment. The demo seeder includes two published offerings and does not republish archived ones.
2. As assigned Staff, browse the published catalog and enroll. Refresh or submit again: the same employee/course enrollment is retained. Staff cannot enroll other accounts or record completion.
3. As Compliance Officer, open **Training progress**, record a completion date and optional feedback. The date must fall between the enrollment date and today in Asia/Manila. Admin manages courses but cannot record completion; officers cannot complete their own old enrollments.
4. View Staff progress again. It shows the officer, completion date, and feedback, without awarding a qualification. Approved, valid evidence is also required through the credential submission/review workflow.
5. Archive the course. Enrollment/completion history stays visible to the employee and scoped managers/directors. The qualification mapping is locked after the first enrollment, and recorded completions cannot be overwritten. Create a new offering for renewals.

## Credential walkthrough

1. As assigned Staff, open **Credentials**, choose an active qualification, enter its issue/expiry dates, and upload a small synthetic PDF, JPEG, or PNG. Expiring qualifications require expiry; non-expiring ones may omit it. Issue dates cannot be in the future, and expiry cannot precede issue.
2. For a training-required qualification, select your own matching enrollment. Evidence may be submitted while training is pending, but approval requires completed training. Other qualifications must not include an enrollment link.
3. As Officer, filter **Credentials** to Pending, open a submission, download its evidence, and approve or reject. Rejection requires feedback. Admin may inspect/download evidence but cannot review; managers/directors cannot access raw files or private review details. Self-review and repeated decisions are blocked.
4. Submit a renewal as Staff. Each upload gets a new private filename, opaque reference, and history. Earlier documents are preserved. A pending/rejected/expired renewal cannot hide an older still-valid approval. Approval and date validity are separate; expiry includes the full calendar day in Asia/Manila.
5. If a file is unavailable, the page explains the problem and the download returns 404. Missing files cannot receive new approval. Restore from a trusted backup or submit a new copy; there are no document-edit/delete actions.

`CREDENTIAL_MAX_UPLOAD_KIB` controls the limit (default 10240 KiB / 10 MiB). PHP's web `upload_max_filesize` and `post_max_size` must also permit the chosen limit. Never create a public link to `storage/app/private`. Downloads use authorized application routes, generated attachment names, no-store caching, and nosniff/sandbox headers. Filename extensions alone do not establish file content. No antivirus/PDF parser service is included; validation checks content-based MIME and readable JPEG/PNG image structure.

The current effective-evidence link is refreshed after review. Views calculate validity using the selection action at read time, rather than trusting a cached link after expiry. Employee-wide eligibility, percentage calculations, and assessment history reuse the same validity predicate; they never trust the cached link after expiry.

## Product interface

The workspace now groups readiness, documents, and training separately from administration. Staff get their own readiness and next steps; other roles get scoped workforce totals. Account and evidence forms adapt to selections. Fields have visible labels, statuses use both color and text, and the mobile navigation opens as a keyboard-accessible dialog. Secondary explanations are expandable.

The [product requirements and reference observations](main/docs/Development-Decisions.md#product-experience-requirements) govern future milestones. Product screens use operational language; development and deployment instructions belong in these documents. Before production use, configure real organization records and mail delivery as well as the environment and infrastructure.

To replace the legacy starter locations in an existing database without resetting it, run `php artisan db:seed --class=PhilippineOrganizationSeeder` from `main/`. This preserves the existing IDs and assignments and adds missing regions/hubs. Hub names are fictional; [data sources and preservation rules](main/docs/Development-Decisions.md#philippine-organization-data) are documented. Review your actual organization configuration before deployment.

Frontend interaction checks run from `main/` with `node --test tests/forms.test.js`, without additional dependencies. The CI workflow includes them.

## Compliance walkthrough

From `main/`, run the additive `php artisan migrate` after pulling this milestone. Existing organization/training/evidence data is preserved.

1. Open **Dashboard**. Explain the eligible / assessable denominator; not-assessable staff remain visible as a separate count. Inactive employees and incomplete/inactive configurations cannot become eligible.
2. Open **Matrix**, then an employee. The matrix combines qualifications required by staff on the current page. N/A means a qualification is not required by that employee's job; it never means awarded.
3. Submit synthetic evidence, complete required training as Officer, and review the linked evidence. The employee becomes eligible only when every mandatory qualification has valid approved evidence. A newer pending renewal keeps an older valid approval effective.
4. Open **View assessment history**. Material snapshots retain assignment names, required qualification states, counts, actor/source, and date. Refreshing an unchanged result does not append another row. Managers/directors see summaries without private files or review notes.
5. For date transitions, run `php artisan checkpoint:reconcile-compliance`. It appends changed results only. For continuous local scheduling, run `php artisan schedule:work` in a separate terminal during the demo; the task is due daily at 00:05 Asia/Manila. Stop with Ctrl+C. Pages calculate live state even without this process. The same command delivers deduplicated expiry alerts to affected Staff and facility managers.

Evidence is valid through its expiry date in Asia/Manila. Warnings include today through 30 days ahead. No assignment, no requirements, or inactive required configuration gives a null percentage and **Not assessable**. Eligibility compares exact counts, independently of the rounded display percentage. See the [compliance defense walkthrough](main/docs/Implementation.md#compliance-calculation-and-history-defense-walkthrough).

## Documentation and next milestones

| Document | Purpose |
|---|---|
| [System specification](main/docs/System-Proposal.md) | Full scope, permissions, model, workflows, and rules |
| [Development decisions](main/docs/Development-Decisions.md) | Agreed choices, services, and local observations |
| [Implementation plan](main/docs/Implementation.md) | Progress, acceptance criteria, and eight-requirement traceability |
| [API learning path](main/docs/API-Learning-Path.md) | Learn routes, JSON, Sanctum, abilities, scope, and API tests as we build |
| [Co-developer kickoff](main/docs/Kickstart-Prompt.md) | Original development brief; consult progress before continuing |

CheckPoint will **provide** an API consumed by third-party auditors. The versioned API is implemented. Follow the learning path for private requests, errors, scope checks, and revocation; shared examples use placeholder tokens.

To regenerate local Laravel Boost agent guidance/skills, run `php artisan boost:install --guidelines --skills --no-interaction` after configuring `.env`. Boost is a development-only tool; generated local skill folders are ignored. The Word proposal is unchanged, and `main/docs/` is retained. Deadline and team experience are still to be supplied.


## Alerts, audits, corrective work, and reports walkthrough

Run `php artisan migrate` from `main/` after pulling; no data reset is required.

1. As Staff or the assigned facility manager, open **Alerts** in the top bar. Required approved evidence generates one warning during the inclusive 30-day window and a separate event after expiry. Run `php artisan checkpoint:reconcile-compliance` to deliver them now. Re-running does not duplicate events. Approved renewal makes older alerts read as **No longer current**, while retaining their history. The unread count includes retained unread history; use **Mark all as read** to clear it.
2. As a Compliance Officer, open **Audits > Capture audit**. Choose a region and optionally a facility. The facility list follows the region selection. Capture saves today's assessment and the original assignment/requirement context. It cannot be edited or deleted through the application. A scope with no employees cannot be captured.
3. In the audit, assign a finding to an active manager of the selected facility, with a due date and a clear corrective task. The manager list follows the facility. Managers can view facility-specific audits, and their facility's individual findings from regional audits; they cannot open the full regional snapshot.
4. As the assigned manager, open **Corrective work**, add dated notes, and submit for verification. Only an officer can verify and resolve submitted work, and the officer cannot verify their own latest corrective submission. Officers can reopen work or reassign it within the same facility; activity retains notes and reassignment names. Old managers lose write access after a transfer or reassignment.
5. As Admin, Officer, manager, or director, open **Reports**, select the report and optional facility/search filter, and apply. The preview shows up to 25 rows; **Download CSV** includes every matching row within the same authorization scope. Findings search uses the title; other reports search employee names. Expiry uses the same current events as alerts, independently of notification delivery. Private attachments, reviewer feedback, and corrective notes are excluded.

For continuous local scheduling, keep `php artisan schedule:work` running. Production needs the normal once-per-minute `schedule:run` operating-system task. Stopping scheduling delays new alerts and date-only history entries; live readiness, expiry reports, and old-alert relevance still calculate correctly. Alerts are synchronous database writes, so no mail provider or queue worker was added.

## Auditor access walkthrough

1. As Admin, assign an intended account the Auditor role and an active region through **Accounts & access**. Auditor accounts cannot sign into the browser workspace.
2. Open **Administration > Auditor access**. When access is needed, choose the auditor, enter a token name, and select expiry (default 30 days ahead, maximum 90). The selected Manila expiry date is inclusive.
3. Store/deliver the one-time token privately. Never display it during the defense or include it in Git, screenshots, logs, or chat. Later screens show metadata and a Revoke action only.
4. Follow the [API learning path](main/docs/API-Learning-Path.md#provision-and-test-access) to call `GET /api/v1/verifications/{reference}`. It returns nine safe fields for one submission, not whole-employee eligibility. The employee's current active facility/region determines scope. Browser sessions and wildcard-only abilities are rejected.
5. Revoke and retry: the token returns 401. Role/region changes and deactivation also revoke tokens. Unknown/other-region references return 404. Limits are 120/minute/IP before authentication and 60/minute/account across tokens.

No third-party provider account or service is required: CheckPoint provides this API. Use HTTPS before delivering bearer tokens beyond the local machine. The current browser review covered the Admin empty state on desktop/mobile; no actual auditor account/token was created. Authenticated API and populated token-management behavior are covered by tests. The synthetic dataset and handover guide are available below; remaining human workflow and restore checks are recorded explicitly.

## Synthetic classroom dataset

After the normal setup above, run this **interactively** from `main/`:

```powershell
php artisan checkpoint:seed-synthetic
```

Enter and confirm a private password for the fictional accounts. There is no default password or password argument. The command is restricted to local/testing and is separate from normal seeding/deployment. It appends 1,690 related records on the baseline, with all roles, eight evidence scenarios, no-requirement/onboarding states, audits, corrective history, and expiry alerts. All 270 evidence submissions download the same harmless image marked **Synthetic evidence — Not a certificate**. No real employee/medical data or API token is generated.

Use `admin@checkpoint.example`, `officer@checkpoint.example`, and scoped `manager.01@checkpoint.example` / `director.01@checkpoint.example` accounts with the password you entered. Auditor accounts remain API-only. The [full account/scenario guide and per-table counts](main/docs/Implementation.md#synthetic-dataset-and-classroom-walkthrough) explain all addresses, scope numbering, and the defense walkthrough. Dates are relative to creation and age normally.

The command requires 18 active regional facility scopes. It preserves existing records/configuration and refuses to rerun or reuse conflicting reserved addresses/catalog names; it never resets used sample accounts or histories. Test execution verified the counts and rollback on isolated MySQL. This checkout's local database was populated on 2026-11-11 using privately supplied input: 1,690 added records and 223 total accounts, preserving the original three. There are 270 submissions, 18 audits, 72 findings, and zero API tokens. Do not rerun setup here; use the existing accounts with the private password you selected. Fresh installations still use the interactive command above.

## Backup and restore rehearsal

Back up the database, private evidence, and application key together. Keep backups outside this repository with restricted access. A SQL dump alone does not contain uploaded files; a file archive alone cannot recreate assignments and history. Before an actual backup, stop the local scheduler/other writers, run `php artisan down`, and wait for active requests to finish. Resume with `php artisan up` only after backup succeeds or after resolving a failed attempt.

With MySQL 8 tools available on PATH, run from `main/` (the database password is prompted privately):

```powershell
$checkpointBackupRoot = Join-Path $env:USERPROFILE ('CheckPointBackups\' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $checkpointBackupRoot -ErrorAction Stop | Out-Null
mysqldump.exe --host=127.0.0.1 --port=3306 --user=checkpoint --password --single-transaction --no-tablespaces --set-gtid-purged=OFF --result-file="$checkpointBackupRoot\checkpoint.sql" checkpoint
if ($LASTEXITCODE -ne 0) { throw 'Database backup failed; do not treat this folder as a complete backup.' }
Compress-Archive -LiteralPath storage/app/private -DestinationPath "$checkpointBackupRoot\private-files.zip" -ErrorAction Stop
Copy-Item -LiteralPath .env -Destination "$checkpointBackupRoot\.env" -ErrorAction Stop
git rev-parse HEAD | Set-Content -LiteralPath "$checkpointBackupRoot\commit.txt" -ErrorAction Stop
php artisan up
```

Do not send or commit the backup folder: it contains credentials, reset/session data, and private files. Confirm that the SQL dump and archive exist and are nonempty; record the dependency lockfiles/commit used. If the client cannot connect, fix the MySQL tools/account privately rather than pasting credentials. This procedure has not been applied to the owner's development database; the isolated automated recovery check is described below.

Rehearse recovery into a **new local database and separate checkout/site**. Create a least-privilege account for that new database through your private MySQL administrator client. Use MySQL Workbench's Data Import to import `checkpoint.sql` into that empty schema. Extract `private-files.zip` outside the active app and copy its `private` directory into the recovery checkout's `storage/app/private`. Restore the saved `.env` privately, retain its original `APP_KEY`, and change the database credentials/APP_URL to the recovery environment; do not point at the active or test database.

Install the recorded lockfiles, run `php artisan config:clear`, and keep the recovered checkout in maintenance mode with `php artisan down --no-interaction`. Check `php artisan migrate:status` before running any required additive migrations. Build assets and link the recovery checkout in Herd. Do not regenerate the application key during recovery.

Restoration brings authentication records back to their state at backup time. In particular, a token revoked **after** the backup can be restored as valid. Before reopening the recovered instance, stop other writers and run this **in the recovered checkout**, after verifying its database settings privately:

```powershell
php artisan checkpoint:invalidate-restored-access --no-interaction
```

This command requires maintenance mode and the database session driver. In one transaction it clears sessions, remembered-login tokens, password-reset links, and Sanctum tokens. It preserves accounts, password hashes, assignments, evidence, and history. Users must sign in again; auditors need newly issued tokens. Review account/role/password changes made since the backup before reopening, since those also revert to the snapshot. The command has been tested only on `checkpoint_test` and was not run on the active development app.

When ready for controlled local checks, run `php artisan up --no-interaction` in the recovered checkout. Check a fresh login, one protected evidence download, historical audit contents, account scoping, and rejection of old tokens. Enable scheduling and public traffic only after those checks pass. No automatic restore or destructive reset of the active database is included.

## Operational acceptance checks

The log-mail test sends a real reset notification through Laravel's log transport to a temporary test log, reads its link privately, resets the password, and rejects reuse. It does not fake mail or send to an external provider. It does not inspect or expose existing development logs.

The recovery test uses the installed MySQL 8 `mysqldump` and `mysql` clients plus PHP ZIP. It backs up committed disposable records and a private fixture, removes the test schema/file, imports/extracts the backup, and checks evidence bytes/download permissions, opaque references, review events, qualification pivots, and audit history, encrypted sessions with the same key, token snapshot behavior, and post-restore access invalidation. Test credentials are read internally from the isolated connection into a temporary client option file, never command arguments or output; temporary option files, dumps, and archives are removed afterward.

From `main/`, with MySQL 8 clients on PATH and PHP ZIP enabled:

```powershell
php artisan test --compact tests/Feature/PasswordResetLogTest.php tests/Feature/InvalidateRestoredAccessTest.php tests/Feature/BackupRestoreTest.php
```

On this Windows installation, the clients are in `C:\Program Files\MySQL\MySQL Server 8.0\bin`; add that directory to PATH for the test process if needed. The `recovery` test group skips with an explicit explanation if either client or PHP ZIP is unavailable; a skipped test does not establish recovery. It uses `DatabaseMigrations` so an external dump sees committed fixtures. The existing test bootstrap and a second guard immediately before schema removal require MySQL `checkpoint_test`. Run it alone or in the normal serial suite, never concurrently with another process using that database. No development schema, accounts, logs, files, or service state are changed.

These automated checks do not replace the remaining human desktop/mobile walkthroughs or a recovery rehearsal into a separate checkout/database using an actual retained backup. The local synthetic dataset is now loaded; its password remains private. Other installations require their own private setup.

Before a real deployment, complete the remaining human workflow/restore checks, configure HTTPS and real mail delivery, set `APP_ENV=production` and `APP_DEBUG=false`, keep the synthetic accounts out of the production database, and arrange the once-per-minute scheduler plus private backups. A production deployment is a separate decision; no hosting or paid service was added here.
