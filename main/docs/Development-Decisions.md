# Development decisions and services

This record separates decisions confirmed during planning from implementation defaults and remaining scheduling context. Do not treat an unanswered question as approval. The attached CheckPoint proposal is the business source; these Markdown documents are the implementation reference. The original Word file is not modified by this documentation change.

## Confirmed decisions

| Decision | Outcome | Reason |
|---|---|---|
| Database | MySQL | Keep the database specified in the proposal. PostgreSQL is installed locally but is not the project database. |
| Account onboarding | Self-registration creates unassigned Staff accounts | An administrator assigns a facility and job before employee-record access is enabled. Registration never accepts privileged roles or assignments from the client. |
| Authorization | Separate Admin, Compliance Officer, Regional Director, Facility Manager, Staff, and API-only Auditor roles | Managers/directors stay within assigned scope; officers review evidence; nobody approves their own submissions. |
| Deployment target | Local classroom demonstration | No public hosting or paid service is required for the first release. |
| Notifications | In-app expiry notifications with a daily Laravel schedule | Use local mail logging to demonstrate password resets. Real email delivery is deferred. |
| Frontend and tests | Blade, Tailwind, Alpine.js, PHPUnit, and Pint | Keep the Laravel application understandable and use the proposal's Blade approach. |
| Employee assignments | One facility and one job role per employee | Supports direct facility scoping and the required has-many-through relationship. |
| Training | Separate courses and self-service enrollment | Each course maps to one qualification. Officers record completion; compliance also requires approved, unexpired evidence. |
| Compliance | Inclusive expiry dates in Asia/Manila; 30-day warnings | Pending renewals preserve older valid approvals. Missing/pending/rejected/expired required items block eligibility; absent configuration is not assessable. |
| Release scope | All proposal modules in staged milestones, with CSV reports | Includes scoped dashboards, review/history, training, audits/findings, notifications, and the auditor API. |

## Implementation defaults

- Group qualifications by safety domain. Separate the application role (permissions) from the employee job role (requirements).
- Use one role per account, one scope assignment for managers/directors, and region-scoped auditor tokens. Keep credential review with officers and administrative account changes with Admin.
- Non-expiring qualifications may omit expiry; expiring qualifications require it. Validate dates and never grant training qualifications on enrollment alone.
- Use private PDF/JPEG/PNG uploads up to a configurable 10 MiB. Keep immutable submissions and append-only review events.
- See the system specification for detailed policy and API defaults. Record any change in these defaults before implementing affected behavior.

## Remaining context

The owner will provide deadline and team experience later. No instructor-required version or additional package has been supplied. These are scheduling inputs, not implied constraints. The foundation uses Laravel 13.40, Fortify 1.41, PHPUnit 12.5, Tailwind 4, Alpine 3, and Vite 8; exact versions are in lockfiles.

## Local services and tools

| Component | Purpose | Need and current evidence |
|---|---|---|
| PHP and Composer | Laravel runtime and dependencies | Available: PHP 8.4.27 and Composer 2.10.3. PHP includes `pdo_mysql`, `fileinfo`, and required common extensions. Recheck requirements against the chosen framework release. |
| Laravel installer | Scaffold the application | Available: installer 5.32.0. This is not the application's Laravel framework version. Select a supported compatible framework release when development starts and commit lockfiles. |
| Herd | Serve the local application | Desktop app is running; repository linked at `http://websys2.test`. Login returned HTTP 200 on 2026-11-10. |
| MySQL | Application and test persistence | Existing `MySQL80` is running on 127.0.0.1:3306. Dedicated local application/test accounts connect to `checkpoint` and `checkpoint_test` respectively; credentials remain in ignored environment files. Development migrations/seeding and 22 tests / 141 assertions passed on 2026-11-10. The earlier temporary instance on port 3307 remains stopped. |
| Node and npm | Vite and frontend asset builds | Node 22.20.0 is available. Check package engine requirements and npm before scaffolding. |
| Private Laravel filesystem | Credential storage | Local private disk is sufficient; no object-storage service is required. Never expose credentials through `public/storage`. |
| Laravel scheduler | Daily expiry alerts | Run `php artisan schedule:work` during local demonstrations; document that alerts do not run while the process is stopped. |
| Mail log | Local password-reset demonstration | Use `MAIL_MAILER=log`; reset links stay on the local machine and are not sent to real addresses. |
| Session/cache/notification storage | Authentication and in-app alerts | Laravel database-backed drivers and database notifications are sufficient. No Redis or separate message broker is needed. |
| Git and GitHub | Version control and PR review | Origin is `pertyperty/WEBSYS2`; Git works. GitHub CLI is not on PATH; a connected GitHub tool can create PRs. |
| PHPUnit and Pint | Tests and formatting | Installed and checked. PHPUnit is the agreed test framework. Sanctum is deferred to the auditor API milestone. |
| Fortify and Boost | Authentication and development guidance | Fortify 1.41 provides session authentication with custom Blade views. The Laravel scaffold's agent instructions required Boost; it is a development-only dependency, with local generated skills ignored by Git. |

No Docker, cloud host, paid API, AI API, antivirus service, or dedicated queue worker is required for the local baseline. Add a service only for a specific accepted need. Use synthetic credentials and employees for demonstrations.

## Co-developer workflow

One coding agent working with the team is sufficient. Repository reading/editing, terminal commands, Git, and GitHub PR access cover initial development. Browser automation may help exercise the app after it exists; a REST client or curl is sufficient for the API demonstration. Specialized agents and additional plugins are optional, not prerequisites.

Use a branch and PR for each coherent milestone. Implement authorization, validation, and focused tests with each workflow. Keep application secrets out of Git and chat; `.env.example` contains placeholders only. Run MySQL-backed tests against a separate test database, never a populated development database.

The owner authorized development and asked to learn the API as it is implemented. The foundation is implemented and tested; the main Herd/MySQL setup is verified, with administrator creation and the interactive walkthrough remaining. Follow [API learning path](API-Learning-Path.md) alongside development. The auditor endpoint itself remains milestone 6.
