# Implementation Progress

## Purpose

This document is the living progress record for the Regional Safety Compliance Management System. Update it as features are implemented, tested, reviewed, or deferred. Each completed item should identify the relevant commit, pull request, or test when available.

## Current Status

**Stage:** Project kickoff and documentation  
**Status:** Not started in code  
**Last updated:** 2026-11-05

The repository currently contains the initial project proposal. The Laravel application and database schema still need to be created.

## Milestones

### Milestone 0: Project foundation

- [ ] Create or configure the Laravel application.
- [ ] Configure `.env.example`, database connection, mail testing, and filesystem settings.
- [ ] Add the selected authentication starter.
- [ ] Establish formatting, test, and local development commands.
- [ ] Create the base Blade layout and navigation.

### Milestone 1: Identity and authorization

- [ ] Add user roles and role assignment.
- [ ] Add policies and Gates for staff, managers, and compliance officers.
- [ ] Protect web routes with authentication and authorization middleware.
- [ ] Add authentication and authorization feature tests.

### Milestone 2: Core domain and database

- [ ] Add migrations and models for regions and facilities.
- [ ] Add job roles and qualifications.
- [ ] Add employee-to-qualification and job-role-to-qualification pivot tables.
- [ ] Add employee/facility assignments.
- [ ] Implement and test the required has-many-through relationship.

### Milestone 3: Credential documents

- [ ] Add credential document migration, model, factory, and policy.
- [ ] Implement validated PDF/image uploads.
- [ ] Store files on a private Laravel disk.
- [ ] Implement authorized download/streaming.
- [ ] Add upload, rejection, authorization, and download tests.

### Milestone 4: Compliance workflow

- [ ] Implement qualification approval and verification status.
- [ ] Implement expiry and eligibility calculations.
- [ ] Add staff qualification pages.
- [ ] Add the compliance dashboard and facility/region summaries.
- [ ] Add unit tests for boundary dates and missing requirements.

### Milestone 5: Audits and management views

- [ ] Add audits and findings.
- [ ] Add employee, facility, qualification, and verification queue pages.
- [ ] Add filtered audit and readiness views.
- [ ] Verify that each management action is policy-protected.

### Milestone 6: API

- [ ] Install and configure Laravel Sanctum.
- [ ] Add auditor token creation/revocation for authorized administrators.
- [ ] Implement `/api/v1/verifications/{reference}`.
- [ ] Add API Resources or an equivalent stable JSON response format.
- [ ] Add authentication, authorization, invalid-reference, and response-shape tests.

### Milestone 7: Seed data and quality

- [ ] Build relationship-aware factories.
- [ ] Seed at least 1,000 realistic records.
- [ ] Run the full test suite against a clean database.
- [ ] Review validation, file privacy, policies, and error responses.
- [ ] Update documentation and record known limitations.

## Working Definition of Done

A feature is complete when:

1. Its migration, model, controller/service, view or API response, and authorization are implemented where applicable.
2. Inputs are validated and invalid operations return a clear validation or authorization response.
3. The feature has focused unit or feature tests.
4. The feature works with seeded data.
5. Related documentation and this progress record are updated.

## Development Log

| Date | Change | Evidence / Notes |
|---|---|---|
| 2026-11-05 | Added initial system proposal, implementation plan, and kickstart prompt. | Documentation-only project kickoff. |

## Known Decisions

- Use Laravel with Blade rather than a separate SPA frontend.
- Use Sanctum for token-protected auditor endpoints.
- Keep uploaded compliance files private and stream them through authorized controller actions.
- Prefer a small number of clear services and policies over a large enterprise-style abstraction layer.
- Do not make the core workflow depend on a paid or unreliable external API.

## Known Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Scope grows beyond a student-project timeframe | Deliver the staff upload, officer verification, dashboard, and one API endpoint first. |
| Incorrect access to private documents | Use private storage, policies, and feature tests for cross-user access. |
| Seed data does not represent real relationships | Use factories with explicit relationship states and verify counts in a seeder test or command. |
| Compliance status differs between pages | Keep expiry and eligibility logic in one reusable service or domain class. |
| External API availability interrupts demonstrations | Keep third-party verification as an authenticated internal API with documented sample requests. |
