# Phase 1 specification — Foundation and admin access

**For review before implementation.** Baseline: `e34e6be`. Reference implementation: the Node
application at `../Dhansree` (`711c14e`), which is authoritative wherever this document and it
disagree.

Phase 1 builds nothing a customer sees. It builds the things every later phase sits on: the
database mapping, the wire contract, and administrator sign-in with its 39 permissions. It is the
phase where a mistake is a **security hole** rather than a bug, which is why it is specified before
it is written.

---

## 1. Scope

### Already done (in `e34e6be`)

| | |
| --- | --- |
| Laravel 12.69.3 / PHP 8.3, Livewire 3, Tailwind standalone, no Node | done |
| Argon2id, SecretBox (AES-256-GCM), TOTP, Razorpay signatures | done, 18 tests / 89 assertions against Node-generated fixtures |
| `config/store.php`, UTC storage vs IST display, file sessions | done |

### In this phase

1. Database mapping for the 45 tables, with Eloquent models for the authentication subset.
2. The HTTP wire contract: success envelope, error envelope, error codes, validation shape, pagination.
3. Administrator sessions: issue, authenticate, idle and absolute timeout, revoke.
4. Sign-in: rate limiting, account lockout, temporary passwords.
5. Two-step sign-in (TOTP) with recovery codes, and the enrolment gate.
6. The 39-permission authorization model, **fail-closed**, with the route audit.
7. Audit logging with secret redaction.

### Explicitly NOT in this phase

Catalogue, inventory, cart, checkout, GST, coupons, orders, payments, shipping, customer accounts,
reviews, banners, SEO, email delivery, and every admin screen except sign-in. No Blade beyond what
sign-in needs.

---

## 2. Database

**The schema is not redesigned.** `../Dhansree/apps/api/prisma/schema.prisma` (1,781 lines, 45
models, 38 enums) is the authority.

1. **Prisma keeps ownership of DDL.** Laravel creates no tables and alters no columns in this phase.
   One baseline migration is written that *describes* the current schema and is marked as already
   run, so `migrate:status` is meaningful; it is never executed against a real database.
2. **Eloquent models only for what this phase touches**: `AdminUser`, `AdminRole`,
   `AdminPermission`, `AdminRolePermission`, `AdminUserRole`, `AdminSession`, `AdminTotpCredential`,
   `AdminRecoveryCode`, `AdminLoginChallenge`, `AuditLog`, `StoreSettings`. The other 34 come with
   their phases.
3. **Conventions that differ from Laravel's defaults** and must be set per model:
   - table names are `snake_case` plural as Prisma generated them — verify each against
     `@@map` in the schema rather than trusting Eloquent's pluraliser (`store_settings`, not
     `store_settings` by accident);
   - timestamps: Prisma's `createdAt`/`updatedAt` map to `created_at`/`updated_at`, which matches,
     but **any model whose Prisma definition lacks them must set `public $timestamps = false`**;
   - `$casts` for every enum column to a PHP 8.1 backed enum;
   - money columns (`*_paise`) cast to `integer`. **Never `decimal`, never `float`.**
4. **38 backed enums** under `App\Enums`, each with the exact string values MySQL stores. A value
   present in the database but unknown to the enum must not throw a 500 on a list screen — follow
   the Node rule and ignore unknown values where it reads, fail where it writes.

**Acceptance:** a test that connects to a copy of the real schema, instantiates every model written
in this phase, and asserts each one's table exists and its casts cover every enum column.

---

## 3. The wire contract

From `../Dhansree/packages/types/src/api.ts`. This is a contract, not a preference: the Next.js
frontend parses it today, and the Playwright suite asserts on it.

**Success** — HTTP 2xx:

```json
{ "data": {...} }
{ "data": [...], "meta": { "pagination": { "page": 1, "pageSize": 20, "total": 57 } } }
```

**Failure** — HTTP 4xx/5xx:

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "…", "details": [{ "path": "email", "message": "…" }], "requestId": "…" } }
```

- `code` is a stable machine-readable string; clients branch on it and never on `message`.
- Common codes: `BAD_REQUEST`, `VALIDATION_ERROR`, `UNAUTHORIZED`, `FORBIDDEN`, `NOT_FOUND`,
  `METHOD_NOT_ALLOWED`, `CONFLICT`, `PAYLOAD_TOO_LARGE`, `UNSUPPORTED_MEDIA_TYPE`, `RATE_LIMITED`,
  `INTERNAL_ERROR`, `SERVICE_UNAVAILABLE`. Domain codes are added as plain strings.
- `details` for validation is an array of `{ path, message }` with a **dot-separated** path
  (`shippingAddress.pincode`). Laravel's validator produces `field.0.sub`; it must be mapped.
- `requestId` correlates with the log line.
- **Validation failures are 422**, not Laravel's default 422-with-its-own-shape. The exception
  handler must be replaced, not decorated.

**Deliverables:** `app/Http/Responses/ApiResponse.php` (the `ok()` equivalent),
`App\Exceptions\ApiException` carrying code + status, and a `Handler` that renders every exception
into this envelope — including Laravel's own `ValidationException`, `AuthenticationException`,
`ModelNotFoundException`, `NotFoundHttpException` and `MethodNotAllowedHttpException`.

**Acceptance:** a test per code asserting status, envelope shape and that no PHP class name,
file path or stack frame ever reaches the body in production.

---

## 4. Administrator sessions

From `admin-session.service.ts`, `session-token.ts`, `admin-session.cookie.ts`.

### The token

- **32 random bytes, base64url → 43 characters** (256 bits).
- Only `SHA-256(token)` hex is stored, in `admin_sessions.token_hash`. A fast hash is correct here
  *because* the token is 256-bit random: it cannot be brute-forced, so a database leak exposes no
  usable session. **Do not substitute a password hash** — lookups are by hash and must be indexed.
- Shape is checked with `/^[A-Za-z0-9_-]{43}$/` **before any database query**.

### The cookie

- Name: `__Host-`-prefixed in staging and production, plain in development.
  `__Host-` requires `Secure`, `Path=/` and no `Domain` — which also means **admin sign-in is
  impossible over plain HTTP in production**, by design.
- `HttpOnly`, `SameSite=Lax`, `Path=/`, explicit `Expires`.
- **It must be excluded from Laravel's `EncryptCookies` middleware.** Laravel encrypts cookies by
  default; the server looks this one up by the SHA-256 of its raw value, so encryption breaks every
  lookup. This is the single most likely thing to get wrong in this phase.

### Lifetime

Two independent limits, both from configuration:

| | Default |
| --- | --- |
| Idle timeout | `ADMIN_SESSION_IDLE_TIMEOUT_MINUTES=120` |
| Absolute timeout | `ADMIN_SESSION_ABSOLUTE_TIMEOUT_HOURS=12` |

`last_seen_at` is refreshed only when it has aged past a threshold, not on every request, so an
active admin does not cause a write per request. A session that fails either limit is **revoked
with a reason**, not merely rejected — `admin_session_revoke_reason` is an enum and the value is
recorded.

Authentication, in order, matching the reference exactly:

1. no cookie → `missing`
2. malformed token → `invalid`
3. not found, or already revoked → `invalid`
4. past idle or absolute limit → revoke with the reason, then `invalid`
5. owner's status is not `ACTIVE` → revoke `ACCOUNT_DISABLED`, write an audit entry, `invalid`
6. otherwise → touch `last_seen_at` if stale, return the admin

`missing` yields `UNAUTHORIZED`; `invalid` yields **`SESSION_EXPIRED`** and clears the cookie so
the browser stops presenting it.

**Implementation:** a custom guard registered as `auth.guard('admin')`. **Not** Sanctum, Fortify or
Breeze — none of them model an opaque server-looked-up token with two timeouts and typed revoke
reasons, and bending one would cost more than writing the guard.

---

## 5. Sign-in, lockout and temporary passwords

- Per-IP rate limit on sign-in: `ADMIN_LOGIN_RATE_LIMIT=10` per 15 minutes.
- **Account lockout: `maxFailedAttempts: 5`, `lockoutMinutes: 15`** (`LOGIN_LOCKOUT_POLICY`),
  independent of the IP limit, whose window is `LOGIN_RATE_LIMIT_WINDOW_MINUTES = 15`. The lockout
  is deliberately short: a long or permanent one would let anyone deny the owner access by typing
  a wrong password five times.
- An unknown email must take the same time as a known one. The reference hashes against a
  throwaway hash (`verifyAgainstDummy`) and always returns false. Port this, including the detail
  that a failure to build the dummy hash must not be memoised — a single failure under memory
  pressure would otherwise turn every later unknown-account attempt into a 500, which is a louder
  "no such account" than the timing it hides.
- A temporary password set by another administrator sets `password_change_required`, which unlocks
  **only** `/me` and the password-change route (§6).
- Optional Have I Been Pwned check (`PASSWORD_BREACH_CHECK`), k-anonymity: only the first five
  characters of the SHA-1 are sent, matching happens locally, and **an unreachable service accepts
  the password and logs the failure** (advisory, not blocking).

---

## 6. Two-step sign-in (TOTP)

- `ADMIN_2FA_POLICY` ∈ `super_admin` (default) | `all` | `optional`.
- A pending sign-in lives in `admin_login_challenges`, in its own short-lived cookie with a
  different name: holding it means only "this password was correct", never a session.
  `ADMIN_2FA_MAX_ATTEMPTS=5`, `ADMIN_2FA_CHALLENGE_TTL_MINUTES=10`.
- Seeds are sealed with `SecretBox` using the label `admin-totp:<id>`, already ported and tested.
- Codes: SHA-1, 6 digits, 30-second step, ±1 step. **The spent step is recorded** so a code cannot
  be replayed inside its own window.
- Recovery codes: single-use, hashed, in `admin_recovery_codes`.
- Raising the policy must never lock anybody out: an administrator who now owes a second factor
  still signs in with their password and is sent to the enrolment screen.

---

## 7. Authorization — the part to get right

From `admin-auth.decorators.ts`, `admin-access.guard.ts`, `admin-route-audit.ts`,
`rbac/permissions.ts`.

### The model

- **39 permission codes** (`ADMIN_PERMISSION_CODES`), each with a description in
  `rbac.registry.ts`. Five system roles: `SUPER_ADMIN`, `ADMIN`, `ORDER_MANAGER`,
  `INVENTORY_MANAGER`, `CONTENT_MANAGER`.
- A role with `grants_all_permissions` (SUPER_ADMIN) yields every known permission; otherwise the
  union of the role grants.
- **AND semantics**: a route requiring several permissions needs all of them.
- **Codes in the database that this release does not know are ignored** — fail closed.
- Output order follows the registry, so the list is stable.

### Three rules, exactly one per route

| Rule | Meaning |
| --- | --- |
| `RequirePermissions(...)` | needs every listed permission |
| `AdminAuthenticated` | any signed-in administrator (e.g. `/me`) |
| `AllowAnonymous` | no session (sign-in, sign-out). Used sparingly |

### Fail closed — two mechanisms, both required

1. **At request time.** A route in the admin area that declares **no** rule is **denied**, and the
   denial is logged as an error naming the route. This is not a theoretical safeguard; it is the
   last line when someone adds a route and forgets.
2. **At boot.** The reference refuses to start if a controller is mounted under `admin/` without
   the decorator that activates authentication — turning "forgot it" from a silent hole into an
   immediate failure.

   Laravel has no direct equivalent, so this becomes **two** things:
   - a service provider check that enumerates `Route::getRoutes()` under the admin prefix and
     throws if any route carries none of the three rule middlewares;
   - **a CI test that does the same**, because a provider that throws in PHP-FPM produces a 500 on
     every request, which is correct behaviour but a terrible way to find out.

### A trap worth naming

The reference decides the admin area by **controller metadata, not URL prefix**, because Express
matches routes case-insensitively and a prefix check could be bypassed with `/API/V1/ADMIN/...`.
In Laravel the equivalent risk is a route group whose middleware is attached by prefix string, or a
route registered outside the group. **Attach authorization to the route definition, and let the
boot check prove every admin route has it** — do not rely on the prefix.

### Gate ordering, which is deliberate

1. authenticate
2. **two-factor enrolment gate** — before the password gate, so a stolen temporary password is not
   enough to take over an account that owes a second factor
3. password-change gate
4. permissions

Codes: `TWO_FACTOR_SETUP_REQUIRED` (403), `PASSWORD_CHANGE_REQUIRED` (403), `FORBIDDEN` (403).

---

## 8. Audit logging

- Every security-relevant action writes to `audit_logs` with actor type (`ADMIN`, `SYSTEM`,
  `CUSTOMER`), action code, entity type and id, and metadata.
- **Metadata is redacted on the way out**, recursively, by a regex over *key names* rather than a
  fixed list — `pass(word|wd|phrase)?`, `secret`, `token`, `hash`, `cookie`, `authori[sz]ation`,
  `api[-_]?key`, `private[-_]?key`, `otp`, `signature`, `cvv`, `card[-_]?number`, case-insensitive.
  Matching keys become `[redacted]`.
- It also bounds the structure, which matters as much as the redaction: **max depth 5**
  (deeper becomes `[truncated]`), **strings truncated at 500 characters**, **arrays at 50 items**.
  A port that redacts but does not bound lets a malformed metadata blob become a slow query or an
  enormous response. Port `sanitize-metadata.ts` and its tests together.
- Ordering is **`occurred_at` descending with `id` as the tiebreak** — never by `id` alone. MySQL
  fixes `now()` at transaction start but allocates the id at insert, so under concurrency id order
  and timestamp order genuinely disagree. The reference has a test and a comment about this; it was
  a real flake.
- Logs must never contain passwords, OTPs, session tokens, payment secrets, shipping credentials,
  `SECRET_ENCRYPTION_KEY`, full payment signatures, or unnecessary personal data.

---

## 9. Acceptance criteria

Phase 1 is done when all of these hold:

1. `php artisan test` green, including the existing 18 parity tests.
2. A test asserting **every** admin route carries exactly one access rule, and that a route
   without one is denied at request time.
3. A test per error code asserting the envelope, the status, and no leakage of class names or
   stack frames.
4. Session tests: issue, authenticate, idle expiry, absolute expiry, revoked, disabled owner,
   `last_seen_at` not written on every request, cookie flags including `__Host-` in production.
5. Sign-in tests: wrong password, lockout after 5, IP rate limit, constant time for unknown
   accounts, temporary-password gate.
6. 2FA tests: enrolment, code accepted in window, replay refused, challenge expiry, attempt cap,
   recovery code single use, policy escalation does not lock out.
7. Permission tests: AND semantics, SUPER_ADMIN, unknown codes ignored, stable order.
8. An administrator created by the Node application can **sign in here with their existing password
   and their existing authenticator**, against a copy of a real database. This is the test that
   proves the migration thesis, and it is the one I would run first.

---

## 10. Decisions I need from you

| # | Question | My recommendation |
| --- | --- | --- |
| 1 | **Admin panel URL.** The reference serves the API under `/api/v1/admin/...` and the panel at `/admin/...`. With Livewire the panel is server-rendered, so the JSON admin API is only needed if the Next.js frontend is kept during the transition. Build both, or Livewire only? | **Livewire only.** You chose Blade + Livewire and no Node; a JSON admin API nobody calls is surface area to secure for nothing. The *storefront* JSON endpoints are a separate question, in Phase 2. |
| 2 | **A real database to test against.** Criterion 9 needs a copy of a database with real admin rows (hashes and sealed TOTP seeds). Can you give me a dump, or should I generate one with the Node application's own seed? | A dump of a **non-production** database if one exists; otherwise I will seed one with the Node app and note that the test proves the mechanism, not your specific rows. |
| 3 | **Error-code parity strictness.** Should the PHP API return byte-identical codes and messages, or are codes-identical / messages-reworded acceptable? | **Codes identical, messages may improve.** The Playwright tests and the frontend branch on codes; messages are shown to people and some are worth rewording. |

Nothing is implemented from this document yet.
