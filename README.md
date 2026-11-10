# CheckPoint

CheckPoint is a proposed Regional Safety Compliance Management System for healthcare logistics. It will centralize employee credentials, training, qualification requirements, regional readiness, audits, and restricted third-party verification.

**Status:** initial scope agreed; application development has not started. This repository contains documentation, not a runnable Laravel application. The team will provide deadline and experience details later.

## Agreed implementation

- Laravel monolith with Blade, Tailwind CSS, and small Alpine.js interactions.
- MySQL, matching the proposal. PostgreSQL is installed locally but will not be used.
- Herd for a local classroom demo, private file storage, in-app notifications, and local mail logging.
- Session authentication for web users; Sanctum tokens for API-only auditors.
- Separate Admin, Compliance Officer, Regional Director, Facility Manager, Staff, and Auditor roles.
- Separate training courses and enrollment, plus job-required qualifications grouped by safety domain.
- PHPUnit tests, Laravel Pint, synthetic seed data, and CSV reports.

No paid service, cloud deployment, separate SPA, or additional agent is required.

## Complete release scope

Staff register without privileged access, receive an administrator-assigned facility and job, enroll in published training, upload evidence, and track compliance. Officers record training completion and approve or reject evidence with a review history. Managers and directors see their assigned scope. Administrators manage accounts, assignments, requirements, and auditor access.

The complete release includes role dashboards, private downloads, a qualification matrix, eligibility calculations, daily in-app expiry alerts, audits with corrective findings, filtered CSV reports, and a restricted verification API. Modules ship in tested milestones.

Approved evidence remains valid through its expiry date in Asia/Manila, with a warning 30 days ahead. Missing, pending, rejected, or expired required evidence blocks eligibility. A pending renewal does not replace still-valid approved evidence. Missing assignments or empty requirement configurations produce **not assessable**.

## Documentation

| Document | Purpose |
|---|---|
| [System specification](main/docs/System-Proposal.md) | Scope, permissions, data model, workflows, and compliance rules |
| [Development decisions and services](main/docs/Development-Decisions.md) | Confirmed choices, local prerequisites, tools, and remaining context |
| [Implementation plan](main/docs/Implementation.md) | Milestones, acceptance criteria, rubric coverage, and progress |
| [Kickstart prompt](main/docs/Kickstart-Prompt.md) | Copyable co-developer prompt for the next development session |

These documents translate the supplied `WEBSYS2-System-Proposal-CheckPoint.docx` into an implementation plan. The original Word proposal is unchanged. The existing `main/docs/` location is retained to preserve links.

## Before development starts

1. Use the [kickstart prompt](main/docs/Kickstart-Prompt.md) when asking the co-developer to begin implementation.
2. Select a supported Laravel release and compatible authentication setup during scaffolding. The installer version is not the framework version.
3. Start/configure MySQL and create separate development and test databases. The planning machine has a `MySQL80` service that was stopped when checked; no database changes were made.
4. Verify Herd linking, npm, and dependency requirements. Commit lockfiles and document exact setup commands when the application exists.

PHP 8.4.27, Composer 2.10.3, Laravel Installer 5.32.0, Node 22.20.0, and PHP's MySQL driver were verified on 2026-11-10. These observations do not establish that the future application is configured or running. Never commit `.env`, real employee documents, or production credentials.
