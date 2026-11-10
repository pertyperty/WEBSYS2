# CheckPoint

CheckPoint is a Regional Safety Compliance Management System for healthcare logistics. The Laravel foundation is implemented; training, credentials, compliance calculations, audits, alerts, reports, and auditor API remain planned.

## Available now

- Laravel 13.40, Fortify 1.41, Blade, Tailwind 4, Alpine.js, and Vite 8, with committed dependency lockfiles.
- Staff registration, login/logout, password reset through local mail logs, and profile/password changes.
- Six role definitions, unassigned-staff onboarding, and administrator-only account assignments/deactivation.
- Minimal region/facility/job records for assignment. Full organization management is milestone 1.
- Private local storage configuration with automatic file serving disabled; uploads/downloads arrive later.
- PHPUnit tests with an isolated MySQL database, Pint, and a GitHub Actions workflow.

The target is a local classroom demo using Herd and MySQL. PostgreSQL is not used. No paid service or extra coding agent is required.

## Local setup on Windows

Use PHP 8.4+, Composer 2, Node 22.12+ with npm, MySQL 8.0+, and Herd. The tested versions are PHP 8.4.27, Composer 2.10.3, Node 22.20.0, npm 11.15.0, and MySQL 8.0.46. Dependency engine requirements and lockfiles remain authoritative.

1. Open Herd. Start your existing MySQL service through Windows Services; starting `MySQL80` may require an administrator terminal. Verify its port and credentials. Do not initialize over an existing MySQL data directory.
2. Through your MySQL administrator client, create an empty `checkpoint` development database and a separate disposable `checkpoint_test` database, both using `utf8mb4`. Create local accounts `checkpoint` and `checkpoint_test` with passwords you choose privately. Grant each account privileges only on its corresponding database. Never use the test database for records you want to keep.
3. From the repository root, install dependencies and copy templates **only if those private files do not already exist**:

```powershell
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

The administrator command prompts for name, unique email, and a hidden password. No default administrator password is shipped. The foundation seeder is repeatable and creates a demo region/hub and two jobs; the 1,000-record dataset is a later milestone. Do not use `migrate:fresh` on development data.

6. Link this repository root in Herd. Its document root must be `public/`, not the repository root. The expected local URL is `http://websys2.test`; confirm Herd's actual URL and match `APP_URL`. Use `npm run dev` for asset hot reload while working, or keep the built assets from `npm run build`.

The default local disk is `storage/app/private`. Do not expose it through a public symlink. Reset messages are written to `storage/logs/laravel.log` with `MAIL_MAILER=log`; open the reset link locally to finish the flow. In-app expiry notifications and scheduler jobs are not implemented yet. Database sessions/cache are used; no queue worker is needed for the current synchronous setup.

## First walkthrough

1. Sign in as the administrator created by the command above.
2. In another browser session, register a staff account. It must land on **Awaiting your assignment**, with profile access but no employee features.
3. As Admin, open **Accounts**, expand that account, keep the Staff role, and assign the demo facility and a job together.
4. Refresh the staff dashboard. It shows its assignment and clearly states that compliance assessments are not implemented yet.
5. Try a password reset and profile/password change. Staff must not be able to open `/admin/users` or assign their own role.

Full CRUD and scope-specific compliance pages are deliberately deferred. Director/manager dashboards currently show only foundation account information. Auditor accounts are blocked from browser login; their API will be implemented later.

## Checks

```powershell
composer validate --strict
php artisan config:clear
php artisan test --compact
php vendor/bin/pint --format agent
npm run build
```

Tests use MySQL and may recreate tables in `checkpoint_test`. The test bootstrap refuses any other database name/driver or a non-testing environment. Always use the dedicated test credentials; never point a development database at that name. GitHub Actions provisions a disposable MySQL service and runs tests, validation, formatting checks, and the asset build.

The foundation was verified locally with **22 tests and 141 assertions** against the running MySQL80 service on port 3306 on 2026-11-10. Dedicated local accounts isolate `checkpoint` from `checkpoint_test`; credentials are stored only in ignored environment files. Development migrations and the organization fixture seeder completed, and Herd returned HTTP 200 for `http://websys2.test/login`. Pint, Composer validation, Blade compilation, and the Vite build passed during foundation implementation. The earlier disposable server on port 3307 remains stopped. The owner still needs to run `php artisan checkpoint:create-admin` and perform the interactive walkthrough.

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
