# Regional Safety Compliance Management System

## 1. Proposal Summary

The proposed system is a web-based **Regional Safety Compliance Management System** for a healthcare logistics company with multiple facilities and a large frontline workforce. It replaces scattered email attachments and spreadsheets with one controlled place for employee credentials, qualification requirements, compliance status, audits, and third-party verification.

The system will be implemented as a **Laravel monolith using Blade views**. Laravel will provide the web application, database access, authentication, authorization, file storage, API, and tests. Blade with Tailwind CSS will provide the user interface. Alpine.js may be used for small interactive behaviors such as filters, confirmation dialogs, and upload previews; a separate Vue, React, or Angular application is not necessary for this project.

This approach is appropriate for a third-year computer science project because it keeps the architecture understandable and defensible while still demonstrating MVC, relational database design, authorization, file handling, REST APIs, and automated testing.

## 2. Pain Points and Proposed Solutions

| Pain point | System solution |
|---|---|
| Compliance files are sent by email and become difficult to find | Employees upload credentials to a protected profile, with document type, issue date, expiry date, and verification status recorded in the database. |
| Managers cannot quickly see which staff are compliant | Dashboards calculate compliance status by employee, facility, region, qualification, and expiry window. |
| Job requirements are difficult to compare with employee qualifications | A qualification matrix connects job roles to required qualifications and employees to completed qualifications through many-to-many relationships. |
| Expired or soon-to-expire credentials are missed | Compliance rules classify credentials as valid, expiring soon, expired, missing, or pending verification. |
| Regional audits require manual summaries | Regional and facility dashboards provide filtered readiness summaries and audit records. |
| External auditors need reliable verification data | A token-protected JSON API exposes limited certification status and verification details without exposing private files. |

## 3. Scope and Users

### User roles

- **Administrator / Compliance Officer**: manages users, qualifications, requirements, verification decisions, and system-wide reports.
- **Regional Safety Director**: views assigned regions, facilities, readiness summaries, and audit history.
- **Facility Manager**: manages and reviews compliance information for an assigned facility.
- **Regular Staff**: maintains their own profile and uploads credential documents.
- **Third-party Auditor**: uses a restricted Sanctum token to query approved verification endpoints.

The first implementation may begin with Administrator, Facility Manager, and Regular Staff roles. Director and auditor permissions can be added without changing the core model because authorization is policy-based.

## 4. Main Features

### 4.1 Authentication and profile management

Users can register where registration is enabled, log in, log out, reset passwords, and update their profile. Authentication will use Laravel’s standard session-based web authentication, with secure password hashing and session regeneration.

### 4.2 Employee and facility management

Authorized managers can view employees assigned to their facility. Compliance officers can create and update facilities, regions, job roles, and user assignments. Users can view their own profile and assigned requirements.

### 4.3 Credential document management

Staff upload PDF, JPEG, or PNG documents with a document type, issue date, expiry date, and optional notes. The upload flow validates file type and size, stores the file through Laravel’s `Storage` facade, and records only the storage path and metadata in the database.

Authorized users can download or stream a document through a controller action. Files will not be placed in a publicly browsable directory. Policies will check ownership or management authority before a download is returned.

### 4.4 Qualification and requirement matrix

Compliance officers define qualifications such as driver safety training, first aid, or equipment certification. Job roles have required qualifications. Employees receive qualifications after approved evidence is submitted.

The matrix shows:

- qualifications required by a job role;
- qualifications held by an employee;
- evidence and verification status;
- issue and expiry dates;
- missing, pending, valid, expiring, and expired requirements.

### 4.5 Compliance dashboard

The dashboard provides summary cards and tables for:

- total employees;
- compliant and non-compliant employees;
- missing documents;
- credentials expiring within a configurable period;
- pending verifications;
- readiness by facility and region.

Filters will use normal Laravel query parameters so views remain linkable and easy to test.

### 4.6 Audits

Authorized users can create an audit for a region or facility, record the audit date and reviewer, and attach findings or corrective actions. Audit history gives management a traceable record of readiness reviews.

### 4.7 Third-party verification API

Sanctum-protected API endpoints will allow an authorized auditor to query a deliberately limited verification record, for example:

```text
GET /api/v1/verifications/{reference}
```

The response may include the credential reference, qualification, employee verification identifier, status, issue date, expiry date, and verification timestamp. It will not return private document files, passwords, internal notes, or unrelated employee data.

## 5. Pages to Implement

### Public and authentication pages

- Login
- Registration, if enabled by the deployment
- Forgot/reset password
- Authentication error and unauthorized pages

### Staff pages

- Staff dashboard
- My profile
- My qualifications
- My credential documents
- Upload credential
- Credential details and verification status

### Management pages

- Compliance dashboard
- Employee list and employee details
- Facility list and facility details
- Region overview
- Qualification list, create, and edit
- Job role requirement matrix
- Verification queue
- Audit list, create, and details
- Reports with filters

### Administrative pages

- User list and role management
- API token management for approved auditor accounts
- System settings relevant to compliance thresholds

The initial release should prioritize authentication, staff documents, qualification tracking, the compliance dashboard, and one verification API endpoint. Additional management pages can be delivered incrementally.

## 6. Proposed Technical Design

### Application structure

- `routes/web.php`: browser pages and authenticated web workflows.
- `routes/api.php`: versioned JSON API endpoints.
- Web controllers: page and form workflows.
- API controllers: JSON-only API workflows.
- Form Requests: input validation and authorization checks.
- Policies and Gates: role and resource authorization.
- Services or domain actions: reusable compliance calculations and document workflows.
- Eloquent models and relationships: persistence and domain navigation.
- Blade components: shared navigation, status badges, forms, and tables.

### Core data model

Likely tables include:

- `users`
- `regions`
- `facilities`
- `job_roles`
- `qualifications`
- `job_role_qualification`
- `employee_qualification`
- `credential_documents`
- `audits`
- `audit_findings`
- `personal_access_tokens` (Sanctum)

The exact schema can evolve during implementation, but every relationship should be represented by migrations, foreign keys, indexes where appropriate, factories, and tests.

### Storage and privacy

Uploaded files will use a private disk configured by Laravel. The application will validate uploads before storage, generate non-guessable stored names, and authorize every download. Database records will retain metadata and a storage path rather than file contents.

### External services

The first version should avoid unnecessary paid integrations. Laravel’s database, filesystem, mail testing tools, and Sanctum are sufficient. A free public API may be added later only when a clear requirement exists; external availability must never be required for the core compliance workflow.

## 7. Implementation of the Eight Mandatory Requirements

### Requirement 1: Multi-role user system and authorization

Users will have a role or role relationship. Laravel Policies and Gates will protect both routes and individual records. Blade navigation will hide unavailable actions, but server-side policies remain the authoritative control. For example, staff may edit their own profile and upload evidence, while only compliance officers may approve a qualification.

### Requirement 2: Authentication system

Laravel Breeze or an equivalent Laravel authentication starter will provide registration, login, logout, password reset, validation, session regeneration, and secure password hashing. Protected pages will use the `auth` middleware.

### Requirement 3: Advanced database relationships

The system will include:

- many-to-many: `Employee belongsToMany Qualification` through `employee_qualification`;
- many-to-many: `JobRole belongsToMany Qualification` through `job_role_qualification`;
- has-many-through: `Region hasManyThrough Employee through Facility` (implemented through the facility-to-user assignment model used by the final schema);
- normal one-to-many relationships such as `Region -> Facilities` and `Employee -> CredentialDocuments`.

Relationship tests will verify that records can be retrieved through the intended Eloquent methods.

### Requirement 4: Database seeding and mock data scale

Factories and seeders will create at least 1,000 total records across regions, facilities, users, job roles, qualifications, documents, employee qualifications, and audits. Seeders will create relationships in a realistic order, use deterministic or documented sample values where useful, and avoid requiring uploaded binary files for the default test seed.

### Requirement 5: File uploading and downloading

Credential uploads will use a Form Request with MIME/type and size validation. Laravel’s `Storage` facade will write to a private disk. A policy-protected controller will stream files to authorized users. Feature tests will cover valid uploads, invalid file types, oversized files, ownership restrictions, and successful authorized downloads.

### Requirement 6: Public or token-based API endpoints

The API will be versioned under `/api/v1` and return JSON using API Resources or consistent response classes. Laravel Sanctum personal access tokens will protect auditor endpoints. API tests will cover unauthenticated rejection, authenticated success, invalid references, and the required response shape.

### Requirement 7: Unit and feature testing suite

Unit tests will cover expiry and eligibility rules, including boundary dates and missing evidence. Feature tests will cover authentication, role restrictions, uploads/downloads, qualification approval, dashboard access, and Sanctum API behavior. Factories will keep tests isolated and readable.

### Requirement 8: Architectural foundation

The project will follow Laravel MVC conventions with web and API routes separated, dedicated controllers, Form Requests, Policies, Eloquent models, Blade views, migrations, factories, seeders, and tests. Business rules will not be embedded in Blade templates or duplicated across controllers.

## 8. Delivery Strategy

Development will proceed in vertical slices:

1. Establish Laravel configuration, authentication, roles, and base layout.
2. Build regions, facilities, users, job roles, and qualifications.
3. Implement credential uploads, private storage, verification, and downloads.
4. Add compliance calculations and dashboards.
5. Add audits and reporting.
6. Add the Sanctum verification API.
7. Expand factories, seed data, tests, validation, and documentation.

Each slice should include its migration/model work, UI or endpoint, authorization, tests, and a short update in `Implementation.md`.

## 9. Success Criteria

The project is successful when a staff member can upload evidence, a compliance officer can verify it, the system can calculate the staff member’s current compliance status, a manager can see readiness for a facility or region, and an authorized external auditor can retrieve a limited verification result through the API. These flows must work with seeded data and be protected by automated tests.
