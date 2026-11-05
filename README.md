# WEBSYS2 – Regional Safety Compliance Management System

## Project Overview
This Laravel project proposes a **Regional Safety Compliance Management System** for a healthcare logistics company with distributed hubs and large frontline teams.

The platform centralizes:
- employee document uploads (licenses, safety certificates, medical clearances),
- qualification tracking across job roles and safety domains,
- regional audit visibility for management,
- and secure API-based verification for third-party insurance auditors.

---

## Problem Context
Current process issues include:
- compliance files sent by email and frequently lost,
- difficulty tracking multi-topic training requirements per job,
- limited regional visibility into facility-level readiness,
- and no direct machine-readable verification pipeline for external insurers/regulators.

---

## Core Domain Model
Actors:
- Regional Safety Directors
- Local Facility Managers
- Compliance Officers
- Logistics Staff (Drivers / Warehouse Workers)
- Third-party Insurance Auditors

Domain concepts:
- Document Verification Lifecycle
- Multi-Domain Qualification Matrix
- Regional Compliance Auditing
- Compliance Enforcement Rules
- Third-Party Verification Pipeline

---

## Technical Requirements Coverage (Mandatory 8)

### 1) Multi-Role User System & Authorization
- Roles include at minimum: **Admin/Compliance Officer** and **Regular Staff** (expandable to manager/director roles).
- Route/action/UI restrictions are enforced with **Laravel Gates/Policies**.
- Example: regular staff cannot approve certifications or manage regional audit settings.

### 2) Authentication System
- Full authentication lifecycle using Laravel auth stack (Breeze/Fortify or equivalent):
  - registration
  - login
  - logout
  - secure session management

### 3) Advanced Database Relationships
The schema must include:
- **Many-to-Many** (pivot table): e.g., `employees <-> qualifications` via `employee_qualification`.
- **Has-Many-Through**: e.g., `Region -> Facilities -> Employees` to aggregate workforce compliance at regional level.

### 4) Database Seeding & Mock Data Scale
- `DatabaseSeeder` must generate **1,000+ total records** using factories and relationship-aware state generation.
- Seed data should simulate realistic scale: regions, facilities, users, certifications, qualifications, and audit records.

### 5) File Uploading and Downloading
- Users upload credential PDFs/images with validation (file type and size).
- Files are stored using Laravel’s **Storage** facade.
- Authorized users can securely download/stream stored compliance files.

### 6) Public or Token-Based API Endpoints
- Dedicated REST API in `routes/api.php` returning JSON (API Resources or structured responses).
- API access secured with token-based auth (e.g., **Laravel Sanctum**).
- Supports third-party verification queries for real-time certification status.

### 7) Unit & Feature Testing Suite
- Automated tests (PHPUnit or PEST) include:
  - Unit tests for compliance-rule logic (expiration checks, eligibility flags).
  - Feature tests for:
    - role-based route access,
    - file upload behavior,
    - API authentication and response structure.

### 8) Architectural Foundation (Routing, Controllers & UI)
- Standard Laravel MVC separation:
  - Web routes in `routes/web.php`
  - API routes in `routes/api.php`
  - Separate Web and API Controllers
- UI can be Blade-based or SPA frontend, with controllers encapsulating business flow cleanly.

---

## Expected Outcome
This system provides end-to-end visibility and enforcement of workforce safety compliance while enabling trusted, token-authenticated third-party verification for insurer/regulator audits.
