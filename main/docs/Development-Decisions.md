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
| Herd | Serve the local application | Desktop app is running; application directory `main/` linked at `http://websys2.test`. Login returned HTTP 200 on 2026-11-10. |
| MySQL | Application and test persistence | Existing `MySQL80` is running on 127.0.0.1:3306. Dedicated local application/test accounts connect to `checkpoint` and `checkpoint_test` respectively; credentials remain in ignored environment files. Development migrations/seeding and 23 tests / 142 assertions passed on 2026-11-10. The earlier temporary instance on port 3307 remains stopped. |
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

The owner authorized development and asked to learn the API as it is implemented. The foundation, organization/qualification, training, private credential, and compliance/dashboard/history milestones are implemented and tested; the main Herd/MySQL setup is verified, with administrator creation and the interactive walkthrough remaining. Follow [API learning path](API-Learning-Path.md) alongside development. The auditor endpoint itself remains milestone 6.

The code must be explainable during the defense: use focused resource controllers, Form Requests, explicit relationships and Gates, and one reusable employee visibility scope. The code walkthrough and demonstration questions are maintained in [Implementation](Implementation.md#code-walkthrough-for-the-defense).


Compliance uses one evidence predicate and a read-only employee assessment action. Eligibility compares exact required/satisfied counts; the display percentage is rounded. Inactive employees or incomplete/inactive requirement configuration are not assessable, with null percentages. History is recorded separately for material changes and reconciled daily at 00:05 Asia/Manila; aggregate dashboards calculate live state without writing a snapshot for every employee. Other catalog edits reconcile history on scoped detail/history reads or the scheduled run. No additional service, queue worker, or dependency was added for this milestone.


## Product experience requirements

The owner confirmed on 2026-11-11 that CheckPoint must be designed as deployable software with a user-first interface. Milestone completion includes practical navigation and interaction quality, not only backend correctness.

- Organize navigation by work: overview, people/readiness, documents, training, and administration. Keep configuration out of the main task flow. Show only routes the role can use and clearly indicate the current page.
- Keep the primary task visible. Use short headings, plain language, one clear primary action, meaningful empty states, and expandable secondary explanations. Avoid repeating the same qualification information in several boxes.
- Give every form control an associated visible label. Use inline errors and an error summary. Role changes must reveal applicable assignments and disable/clear irrelevant values. Evidence selection must show matching enrollments and set expiry requirements. Server validation and scope remain authoritative.
- Use whitespace and typography for structure, with restrained surfaces and separators. Status colors must also have text labels. Controls need visible focus, touch-friendly targets, and keyboard operation.
- Check narrow mobile widths and desktop layouts. Mobile navigation uses a native modal dialog so focus and Escape work without an extra dependency. Tables can scroll within labeled, keyboard-focusable regions without making the whole page overflow.
- Use product language in the interface: no classroom/demo notices, implementation milestones, generic add-record labels, or meaningless selectable entries. A disabled selection prompt is guidance, not a fake option. Unconfigured organization records are an actual setup task.
- For significant future UI changes, inspect publicly available comparable products, including their product screenshots or guided interfaces. Compare user journeys, navigation, status presentation, and mobile behavior with CheckPoint. Record sources, observations, and differences; do not infer unobserved workflows or claim a screenshot proves authorization/security.
- A polished interface does not complete deployment preparation. Configure the actual organization, production mail delivery, HTTPS, production environment/secrets, and operational checks before launch. Local log mail remains a development setting and is documented outside product screens.

Reference interfaces inspected for the redesign: [SafetyCulture's public training tour](https://safetyculture.navattic.com/uw250w9u), reached from [its training page](https://mitti.com/training), and [Moodle's public course catalog](https://school.moodledemo.net/course/index.php). The SafetyCulture tour exposes grouped sidebar navigation, task-level course tabs, a primary action, and concise published-state indicators. Moodle's public catalog combines a labeled search with expandable categories. These observations informed CheckPoint's grouped navigation, collapsible administration, labeled search, and compact statuses; they do not imply matching every feature or backend behavior.


## Philippine organization data

The owner requested Philippine regions and fictional hubs based on real logistics locations. `PhilippineOrganizationSeeder` uses the 18 region names published in the [PSA Philippine Standard Geographic Code region directory](https://psa.gov.ph/classification/psgc/regions), accessed 2026-11-11, including the Negros Island Region. Geographic names are attributed to the Philippine Statistics Authority (CC BY 4.0). The hubs are invented names based on regional distribution locations such as Manila, Clark, Calamba, Mandaue, Iloilo, Davao, and Cagayan de Oro; they are not claims about existing operators or affiliations.

The repeatable seeder adds one hub per region and preserves existing active/inactive settings. It renames the exact legacy starter region and hub in place to NCR and Manila Gateway Distribution Hub, preserving IDs and all assignment/evidence links. Historical assessment snapshots keep their original recorded context. If a canonical name already exists alongside the legacy record, seeding stops transactionally rather than deleting or silently reassigning records. New organization-specific configuration remains editable by Admin.


## Audits, corrective work, and alerts

Milestone 5 implements the agreed database-only notifications, audits, findings, and four CSV report types. Routine workflow defaults are explicit: audits capture today (no backdated capture of current evidence); regional snapshots are director/global views, while managers see only their facility snapshots and individual facility findings. Corrective work moves from open/in progress to awaiting verification and then resolved. Only officers resolve/reopen/reassign, and they cannot verify their own latest corrective submission. Reassignment stays within the finding's facility and is retained in activity. No new service or dependency is required.

Public reference inspection on 2026-11-11 used [SafetyCulture/Mitti's Create actions help guide](https://help.mitti.com/000082), its official desktop and mobile form screenshots, and [Archive inspections](https://help.mitti.com/000035). The action guide describes inspection-linked tasks, owners, due dates, and dated activity. The desktop image shows an action board with a focused side panel for details, assignee, due date, and approval; its mobile image stacks the same task fields. The inspection guide describes sidebar navigation, search, explicit filters, and per-record actions. CheckPoint follows the ownership/deadline/activity pattern with a simpler full-page form and officer verification rather than adding a generic board, chat, recurring tasks, or custom-field engine. Visible labels remain mandatory even where the reference uses placeholders.

The report screen uses the same restrained sidebar/filter/table hierarchy observed in the public inspection screenshot, with color plus text for status and one download action. On mobile, CheckPoint stacks filters and keeps the wide preview inside its own focusable scroll region. The comparison is of observed interface patterns; it does not establish the vendor's authorization, backend design, or every interaction.

Tool limitations: the shared Browser initially timed out twice while taking public screenshots. Browser DOM inspection remained available; official linked screenshots were inspected through the HTML preview. The computer-use fallback reported an unavailable browser host/native pipe. Later CheckPoint screenshots worked. The initial signed-in account was an Administrator, so audit creation correctly returned 403. The owner later signed in as Officer; a stale/repeated login submission displayed 419, but navigating with a fresh GET recovered the active session. The Officer audit capture form was then checked at 1280px and 390px: visible labels, no page-level overflow, disabled facility before region selection, and replacement of NCR hub options with Central Luzon options. No business audit/finding was saved for screenshot purposes. HTTP tests exercise capture, assignment, verification, and the remaining new role/scope boundaries; a full manager/finding browser walkthrough is still a manual review item.


A second public comparison used [OpenProject's work package table configuration guide](https://www.openproject.org/docs/user-guide/work-packages/work-package-table-configuration/) and its official filter/table screenshot. It shows filters directly above rows, text and colored statuses, owner indicators, and configurable columns. CheckPoint uses a fixed report selection and explicit Apply filters action; it does not add OpenProject's saved views, arbitrary filters, or column editor. Comparing the screenshots exposed an overly dense nine-column readiness preview: the preview now omits repeated Region and Assessed on columns, while CSV retains them. Official SafetyCulture mobile screenshots were compared with CheckPoint's 390px report to evaluate stacked controls/touch targets only; these are different tasks, not a claim of identical workflows. No page-level horizontal overflow was observed at 390px or 1280px, and the wide table remained in its own scroll region. Guessed ERPNext and OpenProject deep links initially returned 404; the working OpenProject page was reached through its documentation navigation.
