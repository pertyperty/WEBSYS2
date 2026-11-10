# CheckPoint

CheckPoint is a Regional Safety Compliance Management System for healthcare logistics. The Laravel foundation, organization/qualification management, and training courses/enrollment are implemented. Private credentials and review history are implemented. Live compliance calculations, scoped dashboards, the qualification matrix, and material assessment history are implemented. Audits, in-app alerts, reports, and the auditor API remain planned.

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
- Daily Manila-time reconciliation, with status calculated on read even when the scheduler is stopped.
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

The administrator command prompts for name, unique email, and a hidden password. No default administrator password is shipped. The repeatable seeder creates all 18 Philippine regions, one fictional hub per region based on real logistics locations, two jobs, two safety domains, two training-required qualifications linked to those jobs, and two published initial course offerings. Re-running it restores those demonstration requirement links only when the job, qualification, and domain are active; it preserves existing qualification settings. The 1,000-record dataset is a later milestone. Do not use `migrate:fresh` on development data.

6. From `main/`, run `herd link websys2`. Link the Laravel application directory (`main/`) in Herd; its document root must be `main/public/`. The expected local URL is `http://websys2.test`; confirm Herd's actual URL and match `APP_URL`. Use `npm run dev` for asset hot reload while working, or keep the built assets from `npm run build`.

The default local disk is `storage/app/private`. Do not expose it through a public symlink. Reset messages are written to `storage/logs/laravel.log` with `MAIL_MAILER=log`; open the reset link locally to finish the flow. Daily compliance history reconciliation is scheduled; in-app expiry notifications remain planned. Database sessions/cache are used; no queue worker is needed for the current synchronous setup.

## First walkthrough

1. Sign in as the administrator created by the command above.
2. In another browser session, register a staff account. It must land on **Awaiting your assignment**, with profile access but no employee features.
3. As Admin, open **Accounts**, expand that account, keep the Staff role, and assign a facility and a job together.
4. Refresh the staff dashboard. It shows its assignment and live compliance totals. With required evidence missing, the employee is not eligible; a job with no requirements is not assessable.
5. Try a password reset and profile/password change. Staff must not be able to open `/admin/users` or assign their own role.

Organization and qualification configuration is available through the navigation. Directors/managers can browse their scoped employee directory; staff can view only their own requirements. Dashboard, qualification matrix, employee assessments, and assessment history use the same current employee scope. Auditor accounts are blocked from browser login; their API will be implemented later.

## Checks

Run these commands from `main/`:

```powershell
composer validate --strict
php artisan config:clear
php artisan test --compact
php vendor/bin/pint --format agent
npm run build
```

Tests use MySQL and may recreate tables in `checkpoint_test`. The test bootstrap refuses any other database name/driver or a non-testing environment. Always use the dedicated test credentials; never point a development database at that name. GitHub Actions provisions a disposable MySQL service and runs tests, validation, formatting checks, and the asset build.

The current application is verified with **142 tests and 963 assertions** against MySQL80 on port 3306. Dedicated accounts isolate `checkpoint` from `checkpoint_test`; credentials stay in ignored environment files. Composer validation, Pint, Blade compilation, and the production Vite build passed. Existing development migrations/data are preserved; catalog, training, and credential migrations are additive. Herd serves `main/` at `http://websys2.test`.

## Organization and requirements walkthrough

Run commands from `main/`. After pulling this milestone, run `php artisan migrate` and optionally `php artisan db:seed` for demonstration fixtures; no database reset is needed.

1. As Admin, create a region, facility, job, safety domain, and qualification. Qualifications specify whether expiry and training completion will be required.
2. Open **Jobs & requirements**, choose a job, and select mandatory qualifications. A Compliance Officer can maintain these requirements but cannot manage organization records or account roles.
3. Register a Staff account and assign its facility/job as Admin. Open **Employees** to inspect its inherited requirements. Requirements do not award a qualification or indicate eligibility.
4. Sign in with facility manager/regional director accounts assigned through **Accounts**. They see only employees in their facility/region. A changed employee ID outside that scope returns 404. Staff see only themselves; unassigned Staff remain on onboarding.
5. Try deactivating an assigned facility/job or a required qualification. Validation explains the dependency to resolve. A facility cannot move regions while accounts are assigned. Records are retained and may be reactivated; there are no delete actions.
6. Explain the implementation using the [code walkthrough and defense questions](main/docs/Implementation.md#code-walkthrough-for-the-defense). Continue with the compliance walkthrough below; the auditor API remains a future milestone.


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
5. For date transitions, run `php artisan checkpoint:reconcile-compliance`. It appends changed results only. For continuous local scheduling, run `php artisan schedule:work` in a separate terminal during the demo; the task is due daily at 00:05 Asia/Manila. Stop with Ctrl+C. Pages calculate live state even without this process. In-app alerts arrive in milestone 5.

Evidence is valid through its expiry date in Asia/Manila. Warnings include today through 30 days ahead. No assignment, no requirements, or inactive required configuration gives a null percentage and **Not assessable**. Eligibility compares exact counts, independently of the rounded display percentage. See the [compliance defense walkthrough](main/docs/Implementation.md#compliance-calculation-and-history-defense-walkthrough).

## Documentation and next milestones

| Document | Purpose |
|---|---|
| [System specification](main/docs/System-Proposal.md) | Full scope, permissions, model, workflows, and rules |
| [Development decisions](main/docs/Development-Decisions.md) | Agreed choices, services, and local observations |
| [Implementation plan](main/docs/Implementation.md) | Progress, acceptance criteria, and eight-requirement traceability |
| [API learning path](main/docs/API-Learning-Path.md) | Learn routes, JSON, Sanctum, abilities, scope, and API tests as we build |
| [Co-developer kickoff](main/docs/Kickstart-Prompt.md) | Original development brief; consult progress before continuing |

CheckPoint will **provide** an API consumed by third-party auditors. That API is not implemented in this foundation. We will explain and exercise it incrementally during its milestone, using placeholder tokens in shared examples.

To regenerate local Laravel Boost agent guidance/skills, run `php artisan boost:install --guidelines --skills --no-interaction` after configuring `.env`. Boost is a development-only tool; generated local skill folders are ignored. The Word proposal is unchanged, and `main/docs/` is retained. Deadline and team experience are still to be supplied.
