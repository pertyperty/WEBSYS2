# Kickstart Prompt

Copy the prompt below into a coding assistant or use it as the initial implementation brief for the development team.

```text
You are helping develop a Laravel web application called the Regional Safety Compliance Management System.

Project context:
- This is a third-year computer science project for a healthcare logistics company with multiple regions and facilities.
- The application centralizes employee credential documents, qualification requirements, compliance status, audits, and limited third-party verification.
- Keep the implementation understandable and defendable. Do not overengineer it with microservices, event-driven infrastructure, a separate SPA, or paid external integrations unless a later requirement clearly justifies one.

Required architecture:
- Use a Laravel monolith with Blade views.
- Use Tailwind CSS and Alpine.js only for simple UI interaction where useful.
- Use routes/web.php for browser workflows and routes/api.php for JSON endpoints.
- Use Eloquent models, migrations, factories, seeders, Form Requests, Policies/Gates, controllers, Blade components, and PHPUnit or Pest tests.
- Use Laravel Sanctum for token-protected API access.
- Store uploaded files on a private Laravel filesystem disk and stream them only through authorized controller actions.

Mandatory requirements:
1. Multi-role authorization for at least Administrator/Compliance Officer, Facility Manager, and Regular Staff. Enforce access server-side with policies/Gates.
2. Authentication with registration where enabled, login, logout, password reset, secure password hashing, and session management.
3. Advanced relationships, including many-to-many employee-to-qualification and job-role-to-qualification relationships, plus a Region-to-Employee has-many-through relationship through facilities/assignments.
4. Factories and seeders that create at least 1,000 total realistic records.
5. Validated PDF/image credential upload and authorized download using Laravel Storage.
6. A versioned, token-protected JSON verification API under /api/v1.
7. Unit tests for expiry/eligibility logic and feature tests for roles, uploads, downloads, authentication, and API responses.
8. Clear Laravel MVC separation with web/API controllers and business rules outside Blade templates.

Core domain:
- Regions contain facilities.
- Facilities have assigned employees.
- Employees have job roles and qualifications.
- Job roles require qualifications.
- Employees submit credential documents as evidence.
- Compliance officers verify or reject evidence.
- Audits record regional or facility readiness reviews and findings.

Implement in vertical slices:
1. Inspect the existing repository before changing files.
2. Create/configure the Laravel application and authentication foundation.
3. Implement migrations, models, factories, and policies for users, regions, facilities, job roles, and qualifications.
4. Implement credential document upload, private storage, metadata, verification, and authorized downloads.
5. Centralize compliance calculations for missing, pending, valid, expiring, and expired requirements.
6. Build staff, management, and dashboard Blade pages.
7. Add audits and findings.
8. Add Sanctum and GET /api/v1/verifications/{reference}, returning only deliberately limited verification fields.
9. Add relationship-aware seeders with at least 1,000 records.
10. Run focused tests after each slice and the full test suite before declaring completion.

Important behavior:
- Staff can manage their own profile and submit evidence but cannot approve their own credentials or other users' credentials.
- Facility managers can view and manage records for their assigned facility only.
- Compliance officers can approve/reject credentials and manage requirements.
- Every document download must check authorization; never expose storage paths publicly.
- Validate file MIME/type and size before storage.
- Do not return private document files or unnecessary personal data through the auditor API.
- Use clear validation errors and authorization responses; do not silently ignore failures.
- Keep compliance calculations in one reusable class/service so dashboards, API responses, and tests use the same rules.

Expected first deliverable:
- A working Laravel foundation with authentication, the core migrations/models, role policies, a base Blade layout, and tests for the first implemented workflows.
- Update main/docs/Implementation.md after each meaningful milestone.
- Explain any schema or design decision that differs from main/docs/System-Proposal.md.

Before finishing:
- Run the project formatter/linter if configured.
- Run focused tests and then the full test suite.
- Verify seeded counts meet the 1,000-record requirement.
- Check that unauthorized users cannot access management pages or private files.
- Check that unauthenticated API requests fail and valid Sanctum requests return the documented JSON shape.
- Report changed files, tests run, and any known limitations.
```

## Suggested First Commands

Once the Laravel application exists, the team can adapt these commands to the configured project:

```bash
php artisan migrate
php artisan db:seed
php artisan test
php artisan serve
```

The exact authentication starter and frontend build commands should follow the Laravel version selected for the project.
