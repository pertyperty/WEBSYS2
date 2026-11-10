# Learning the auditor API as we build

We will implement the API with explanations, small requests you can run, and tests you can read. You do not need to learn every API concept before working on the rest of CheckPoint.

In our proposal, CheckPoint **provides** an API that third-party insurance auditors call. We are not connecting to an insurance company's existing API. That means we control the endpoint, response format, authentication, and permission rules. No external provider account is needed.

## 1 Start with the web foundation

The foundation uses web routes such as `GET /login` and `POST /login`. GET requests retrieve pages. POST submits information. Fortify validates login details, and Laravel stores a session for subsequent browser requests. Blade turns server data into HTML.

Try creating a staff account, signing out, and signing in again. Observe the redirect to the awaiting-assignment page. Being logged in establishes identity; it does not authorize assignment changes. Read `routes/web.php`, the `manage-users` Gate, and the authentication/assignment feature tests to see the distinction.

Browser form requests include a CSRF token. The later auditor API uses a bearer token instead of relying on a browser session. Keep those mechanisms distinct.

## 2 Define the data before exposing it

During qualification and credential milestones, learn how models, foreign keys, review decisions, and expiry rules produce a verification result. We will test those rules first. An API must use the same assessment logic as the browser; it should not become a second implementation of compliance.

The training milestone illustrates the same boundary: a session identifies the officer, the completion Gate permits the action, validation checks the date, and a transaction protects the enrollment state. Completing training alone does not produce a compliance result. The later auditor API must verify evidence as well as matching completed training.

The credential milestone now generates an opaque reference per submission and uses SelectEffectiveCredential for current evidence selection. A pending renewal leaves an older valid approval available. The later API must apply its own auditor token/region checks and explicitly choose safe response fields; private document pages/downloads do not authorize auditors. For a reference-specific verification, evaluate that submission itself; do not substitute the selected employee-level evidence or require that reference to be the newest selected record. The API route is still not implemented.

A verification reference identifies a particular evidence submission. It is not a password and is not sufficient to authorize access. It does not mean that all of the employee's other requirements are satisfied.

## 3 Build one JSON route in the API milestone

We will add `GET /api/v1/verifications/{reference}` in `routes/api.php`, with a dedicated controller and API Resource. Unlike a Blade page, its response is structured JSON for another program to read. The Resource explicitly chooses permitted fields instead of serializing the entire model.

Read the planned response in [System specification](System-Proposal.md). We will trace a request from route to authentication, authorization, lookup, calculation, and JSON response. Practice through a local REST client or curl, and then turn the same requests into PHPUnit feature tests.

## 4 Add Sanctum tokens and permissions

An administrator provisions an API-only auditor account with a region and issues an expiring token with `verification:read`. The token is shown once, stored privately by the auditor, and sent in the Authorization header. Sanctum stores a hash for validation. Revocation removes access.

The token proves which account is calling. Its ability permits the type of operation. A separate region check limits which records that account can verify. All three checks are necessary; a valid token is not permission to query every employee.

This is the request shape we will use once the endpoint exists:

```http
GET /api/v1/verifications/OPAQUE_SUBMISSION_REFERENCE HTTP/1.1
Host: websys2.test
Accept: application/json
Authorization: Bearer YOUR_PRIVATE_TOKEN
```

This is a teaching example, not a working endpoint in the foundation. Never paste real tokens into Git, screenshots, shared request collections, or chat.

## 5 Exercise failures as well as success

| Scenario | Expected response | What it teaches |
|---|---|---|
| Valid token, ability, region, and reference | 200 with limited JSON | Successful contract |
| Missing, invalid, expired, or revoked token | 401 | Authentication |
| Wrong account role or missing ability | 403 | Permission to perform the operation |
| Unknown or other-region reference | 404 | Lookup and resource scoping |
| Too many requests | 429 | Rate limiting |

We will also assert that names, medical content, file paths, private feedback, and file contents are absent. Returning a correct status field is insufficient if a response leaks private data.

## Learning checkpoint

At the end, you should be able to explain who provides and consumes this API, how a request becomes JSON, how session and token authentication differ, why abilities and region policies are separate, and how tests prove both useful behavior and denied access. We will update this guide with actual setup and runnable commands when the API milestone is implemented.
