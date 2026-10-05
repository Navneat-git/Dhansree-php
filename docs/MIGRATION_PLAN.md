# Laravel migration plan

**Date:** 2026-10-05 · **Baseline commit:** `87bd5a2` · **Status:** plan for review, no code written

You have decided to move Dhansree to PHP/Laravel for team and maintainability reasons. That is a
legitimate business decision and this document plans it properly. My technical recommendation in
[PLATFORM_EVALUATION.md](PLATFORM_EVALUATION.md) was not to rewrite; you have weighed that and
chosen otherwise, so the question from here is **how to do this without losing the shop or the
twelve phases of decisions already made.**

One sequencing point I do want to press, because it costs hours and protects months: see §1.

---

## Decisions taken (2026-10-05)

| Question | Decision |
| --- | --- |
| Where | **`/home/administrator/nodegny/Dhansree-php`**, a sibling directory, its own git repository |
| The Node application | **Not modified.** It stays runnable and may be used in future |
| Frontend | **Blade + Livewire 3 + Alpine**, no Node in production (§4) |
| Scope | All features of the Node application |

Because the Node application is not being modified, two things in this plan change:

- **§1's sequencing advice is not being applied.** The pipeline fix is not going into that
  repository, so there is no COD-only interim launch on the Node stack. The consequence is that
  **there is no shop taking orders until this port reaches launch readiness** — 4.5–9 months by the
  estimate in §8. That is a real cost and it is the user's call to accept it; it is recorded here so
  it is not a surprise later. The fix remains available at any time (it is documented in the Node
  repository's `docs/PLATFORM_EVALUATION.md` §5) and needs only hours.
- **§6's parity harness cannot modify the Node repository either.** The Playwright suite is run
  from there read-only, with its base URL pointed at this application, or copied into this
  repository. Copying is the cleaner option and is what §6 now assumes.

---


---

## 1. Sequencing: launch first, migrate second

The rewrite is 4–9 months (§8). The deployment fix is a few hours. These are independent.

**Do the pipeline fix first, launch COD-only on the current stack, then migrate with no launch
pressure.** That way:

- You start taking orders in weeks, not next year.
- The Laravel port gets built against a **running reference implementation** you can diff against,
  rather than against a frozen repository.
- The 175 Playwright tests become a live parity harness (§6) instead of a historical artefact.
- If the migration stalls, you have a shop. If you block launch on the rewrite and it slips, you
  have neither.

This is not a re-argument about the rewrite. It is about not having the rewrite on the critical
path to revenue. If you accept it, Stage A from PLATFORM_EVALUATION.md §5 runs first and this plan
starts in parallel.

---

## 2. The finding that makes this safe

Measured, not assumed:

- **The web app never touches the database.** No Prisma import, no SQL, nothing. Verified by
  grep across `apps/web/src`.
- **There are zero server actions** (`'use server'`: 0 occurrences).
- All communication is HTTP to `/api/v1`, through **5 small client modules** and 8 call sites
  (`lib/api/client.ts`, `lib/api/server.ts`, `lib/api/parse.ts`, `lib/store/api.ts`,
  `lib/admin/api-errors.ts`).

So the frontend and backend are joined by a **narrow, documented HTTP contract**, not a shared
runtime. That converts a big-bang rewrite into a two-stage strangler migration:

```
Stage 1   Laravel API  +  existing Next.js frontend   ← 175 Playwright tests prove parity
Stage 2   Laravel API  +  Blade/Livewire frontend     ← same tests, re-pointed selectors
```

**Each stage is independently verifiable and independently revertible.** That is the difference
between a migration and a gamble.

The one build-time coupling to plan for: `packages/types` is **12,005 lines** of Zod schemas and
TypeScript types shared by both apps. While the Next.js frontend survives (Stage 1), that package
stays and becomes the authoritative description of the wire contract the Laravel API must satisfy.

---

## 3. What exists today

|                          | Count                                                                                   |
| ------------------------ | --------------------------------------------------------------------------------------- |
| Hand-written source      | 60,704 lines / 484 files                                                                |
| Prisma schema            | **1,781 lines, 45 models, 38 enums**                                                    |
| Migrations               | 11                                                                                      |
| API controllers / routes | **28 / 145**                                                                            |
| Admin permissions        | **40**                                                                                  |
| Web page routes          | **49** (34 admin, 15 storefront)                                                        |
| React components         | 157 (72 admin, 27 store, 58 shared); **67 client components**, 17 using react-hook-form |
| Tests                    | ~1,523 (API 587 + 295, types 449, config 17, **browser 175**)                           |
| Test code                | 26,400 lines across 118 files                                                           |

Largest business-logic units, which are the real work:

| Lines | File                                           |
| ----- | ---------------------------------------------- |
| 785   | `shipping/shipments.service.ts`                |
| 690   | `checkout/checkout.service.ts`                 |
| 671   | `catalog/products/product-variants.service.ts` |
| 633   | `catalog/categories/categories.service.ts`     |
| 632   | `storefront/storefront.service.ts`             |
| 602   | `inventory/stock.service.ts`                   |
| 587   | `admin/auth/admin-two-factor.service.ts`       |
| 554   | `shipping/shipment-ledger.ts`                  |
| 519   | `orders/orders.service.ts`                     |
| 499   | `payments/online-payments.service.ts`          |
| 448   | `payments/payment-ledger.ts`                   |

---

## 4. Target architecture

| Layer            | Choice                                | Why                                                                                                       |
| ---------------- | ------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Framework        | **Laravel 12** (PHP 8.3+)             | LTS-ish cadence, first-party auth/queue/mail/validation                                                   |
| Storefront views | **Blade + Alpine.js**                 | Server-rendered for SEO; Alpine covers the small interactions without a bundler                           |
| Admin panel      | **Blade + Livewire 3**                | 34 admin pages are forms and tables — Livewire's sweet spot, and it needs no JS build                     |
| CSS              | **Tailwind 4 via the standalone CLI** | Keeps the existing design tokens and `globals.css`; the standalone binary means **no Node on the server** |
| Database         | **MySQL 8.0.46, unchanged**           | Same instance, same version, same schema (§7)                                                             |
| Queue            | **`database` driver**                 | No Redis. Matches the existing "no extra infrastructure" rule                                             |
| Scheduler        | `cron` → `schedule:run`               | Replaces `payments:expire` and `emails:send`                                                              |
| Process model    | **PHP-FPM + Nginx**                   | No PM2, no long-running Node, no build step on the box                                                    |

**Deliberately not chosen:** Inertia.js. It would preserve the 157 React components, but it needs a
Vite build — which keeps a Node toolchain on the critical path and so forfeits the main reason you
are moving. If preserving the React admin matters more than removing Node, Inertia is the variant
to revisit, and I would plan it differently.

**Deliberately not introduced:** Redis, Docker, Horizon, Octane, Scout. Same reasoning as the
Node stack — nothing here has demonstrated the need.

---

## 5. Parity: what survives, what is rebuilt, what is lost

### Survives untouched — **verified by running it**

This is the best news in the document, and it is no longer a prediction. The PHP ports in
`app/Support/Crypto/` and `app/Support/Razorpay/` were tested against fixtures generated by the
Node application's **own compiled modules** (`apps/api/dist/auth/*.js`,
`payments/razorpay/razorpay-signature.js`): **16 tests, 87 assertions, all passing**
(`tests/Unit/Crypto/NodeParityTest.php`).

**Your admin accounts and 2FA enrolments do not have to be reset.**

| Thing               | Current                                                                                                                                       | PHP equivalent                                                                            | Note                                                                                      |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| Admin passwords     | Argon2id, `m=19456, t=2, p=1`, PHC strings                                                                                                    | `password_hash(…, PASSWORD_ARGON2ID, ['memory_cost'=>19456,'time_cost'=>2,'threads'=>1])` | **Same PHC format. `password_verify()` reads existing hashes as-is.** No password resets. |
| TOTP seeds          | AES-256-GCM, `v1.<keyid>.<iv>.<tag>.<ct>` base64url, AAD = row label e.g. `admin-totp:42`; key is the raw 32 bytes of `SECRET_ENCRYPTION_KEY` | `openssl_decrypt(…, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $label)`            | **Portable. No re-enrolment**, provided the AAD and base64url handling match exactly.     |
| TOTP codes          | SHA-1, 6 digits, 30 s step, ±1 step window                                                                                                    | `hash_hmac('sha1', …)`                                                                    | RFC 6238 test vectors already exist in `totp.spec.ts` — port them first.                  |
| Razorpay signatures | HMAC-SHA256 over `orderId                                                                                                                     | paymentId` and over the raw webhook body                                                  | `hash_hmac('sha256', …)` + `hash_equals`                                                  | Must read the **raw** body before any middleware parses it. |
| Database            | MySQL 8.0.46                                                                                                                                  | Same instance                                                                             | §7                                                                                        |
| Media               | `https://assets.dhansree.com/ds-web`                                                                                                          | Same                                                                                      | Already external as of `34d8235`                                                          |

**This was Phase 1's highest-risk item and it is now done.** `scripts/regenerate-parity-fixtures.sh`
rebuilds the fixtures from the Node application whenever it changes; if a parity test then fails,
**the PHP port is corrected, never the fixture**.

Measured results:

| Check | Result |
| --- | --- |
| PHP verifies Node-generated Argon2id hashes | PASS (3 passwords, incl. multi-byte and long) |
| PHP's own hashes match the Node PHC pattern | PASS — round-trips after a rollback |
| Malformed hashes verify false rather than throwing | PASS |
| PHP opens Node-sealed TOTP seeds | PASS (3 cases) |
| A sealed value moved to another row is refused (AAD) | PASS |
| A sealed value from another key is refused | PASS |
| TOTP codes identical to Node | **21/21** across 3 secrets × 7 time steps |
| TOTP window ±1 step, and replay refused | PASS |
| Razorpay checkout + webhook signatures | PASS, incl. re-serialised-JSON rejection |

### Must be rebuilt

- 145 routes across 28 controllers, matching the `{data}` / `{data, meta}` envelope from
  `common/http/ok.ts` and the existing error-code vocabulary.
- The **fail-closed admin authorization model**. Today `AdminController` + exactly one of
  `@RequirePermissions` / `@AdminAuthenticated` / `@AllowAnonymous` is mandatory, and _a controller
  under `admin/` without the decorator stops the application from starting_. Laravel middleware has
  no equivalent of that boot-time invariant — it must be rebuilt deliberately, as a service-provider
  check or an architecture test. **A silently unprotected admin route is the single worst failure
  mode of this migration.**
- 39 permissions with AND semantics and SUPER_ADMIN's `grantsAllPermissions`, including the
  fail-closed rule that unknown codes in the database are ignored.
- GST engine, coupon engine, inventory reservations with timed release, the payment and shipment
  ledgers, Shiprocket integration, audit logging with secret redaction, email templates, SEO
  (sitemap, canonicals, JSON-LD), reviews, banners.
- Rate limiting, CSRF origin allowlisting, and the CSP — noting that Laravel's own session/CSRF
  model differs from the current `__Host-`-prefixed, `SameSite=Lax` cookie scheme.

### Lost, and worth naming plainly

- **1,348 of ~1,523 tests.** The Jest suites are bound to Prisma, Nest DI and the TypeScript types.
  They are deleted, not translated. Only the **175 Playwright tests survive**.
- **The shared type contract.** `packages/types` (12,005 lines) has no PHP counterpart; Zod schemas
  become Laravel form requests, and the compiler no longer proves frontend and backend agree.
- **Compile-time exhaustiveness.** Patterns like `Record<AdminPermissionCode, string>` — which
  currently make a new permission with no description a _build error_ — become runtime concerns.

---

## 6. The parity harness

The 175 Playwright tests are the safety net, and they work for this because they drive a real
browser against a real stack and know nothing about the backend language.

| Spec                    | Tests | Covers                                          |
| ----------------------- | ----- | ----------------------------------------------- |
| `admin-panel.spec.ts`   | 16    | Admin CRUD across modules                       |
| `foundation.spec.ts`    | 14    | Security headers, CSP, health, 404, noindex     |
| `account.spec.ts`       | 11    | Customer accounts, addresses, orders            |
| `admin-auth.spec.ts`    | 11    | Sign-in, 2FA, lockout                           |
| `seo.spec.ts`           | 9     | Sitemap, canonicals, structured data, redirects |
| `accessibility.spec.ts` | 8     | Keyboard and a11y assertions                    |
| `storefront.spec.ts`    | 8     | Catalogue browsing, search                      |
| `catalog.spec.ts`       | 6     | Product and variant display                     |
| `inventory.spec.ts`     | 6     | Stock behaviour                                 |
| `payments.spec.ts`      | 6     | Razorpay flow against the local fake            |
| `shipping.spec.ts`      | 5     | Shiprocket flow against the local fake          |
| `checkout.spec.ts`      | 4     | Cart → order, GST, COD                          |

Across 4 projects: `desktop-chrome`, `mobile-chrome`, `shipping-desktop`, `shipping-mobile`.

**How to use it:**

1. **Stage 1** — point the existing Next.js frontend at the Laravel API. The suite runs _unchanged_.
   Every passing test is proof the Laravel endpoint behaves like the Nest one, including status
   codes, envelope shape and cookie behaviour. This is the highest-value thing in the whole plan.
2. **Stage 2** — as Blade/Livewire pages replace Next.js routes, the assertions mostly stand; only
   selectors change. Port one page at a time and keep the suite green.

The existing local fakes for Razorpay and Shiprocket (`scripts/dev/fake-providers.mjs`) can be
reused as-is — they are HTTP servers, language-agnostic.

---

## 7. Database strategy

**Keep the schema. Do not redesign it.** It carries 11 migrations of decisions and real data.

1. Point Laravel at the existing database with **no migrations of its own initially**. Use
   `php artisan db:show` / introspection to confirm it reads cleanly.
2. Generate Eloquent models from the live schema, then hand-correct relations against
   `schema.prisma` (1,781 lines is the authority).
3. Create a **single baseline migration** representing today's schema, and mark it as already run
   (`migrate --pretend` to verify, then record it). Laravel's migration table starts from there.
4. **Prisma's `_prisma_migrations` table stays** for as long as the Node app runs — during Stage 1
   both frameworks read the same schema, and only one of them may own DDL. **Rule: Prisma owns the
   schema until Stage 2 completes.** Laravel adds no columns before then.
5. Enum handling needs care: Prisma's 38 enums are MySQL `ENUM` columns. Laravel has no native enum
   casting to match; use PHP 8.1 backed enums with explicit casts, and test the ENUM-widening cases
   noted in [ROLLBACK_PLAN.md](ROLLBACK_PLAN.md).
6. Money is stored in **paise as integers** throughout. Keep that. Do not introduce floats or
   `decimal` anywhere in the port.
7. **Laravel's own infrastructure tables are a consequence of rule 4.** Sessions, the queue, cache
   and failed jobs would normally live in the database; while Prisma owns DDL they must not. So the
   foundation ships `SESSION_DRIVER=file` and `QUEUE_CONNECTION=sync`, and `config/app.php` keeps
   `timezone => 'UTC'` to match how the Node application stores timestamps — the store's IST display
   timezone is a separate setting in `config/store.php`. Changing the app timezone to `Asia/Kolkata`
   would shift every shared timestamp by 5h30m. Revisit all four once Prisma no longer owns DDL.

---

## 8. Phases and effort

Assumes one developer fluent in Laravel, working from the existing code as a **specification** —
which matters: the decisions are already made, documented and tested, so this is a port, not a
design exercise. That is the main reason it is months rather than a year.

| #   | Phase                               | Scope                                                                                                                                                                         | Estimate                     |
| --- | ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------- |
| 0   | **Pipeline fix + COD launch**       | PLATFORM_EVALUATION §5 Stage A, on the current stack                                                                                                                          | **hours**                    |
| 1   | Foundation                          | Laravel skeleton, schema baseline, Eloquent models, **crypto parity fixtures**, response envelope, error vocabulary, admin auth + 39 permissions + the fail-closed boot check | 3–4 wk                       |
| 2   | Catalogue + storefront reads        | Categories, products, variants, images, search, `storefront.service` equivalent                                                                                               | 3–4 wk                       |
| 3   | Cart, checkout, GST, coupons        | `checkout.service` (690 lines), GST engine, coupon engine, COD                                                                                                                | 4–5 wk                       |
| 4   | Orders + inventory                  | `orders.service`, `stock.service` (602), reservations with timed release                                                                                                      | 3–4 wk                       |
| 5   | Payments                            | Razorpay orders, verification, **webhooks on the raw body**, `payment-ledger` (448), refunds                                                                                  | 2–3 wk                       |
| 6   | Shipping                            | `shipments.service` (785), `shipment-ledger` (554), Shiprocket provider, tracking webhooks                                                                                    | 3–4 wk                       |
| 7   | Admin panel (Livewire)              | 34 pages, 72 components' worth of behaviour, audit log viewer, settings                                                                                                       | 6–8 wk                       |
| 8   | Email, SEO, reviews, banners, audit | Templates, sitemap, JSON-LD, redirects, moderation, redaction                                                                                                                 | 3–4 wk                       |
| 9   | Parity, hardening, cutover          | Full Playwright green on Blade/Livewire, security review, rollback rehearsal                                                                                                  | 2–3 wk                       |
|     | **Total**                           |                                                                                                                                                                               | **29–39 weeks ≈ 7–9 months** |

**A COD-only first cut** (phases 0–4 + 7 + 8, deferring Razorpay and Shiprocket) lands at roughly
**4.5–6 months** and matches your stated launch intent. Phases 5 and 6 then follow without launch
pressure. This is the variant I would plan around.

---

## 9. Risk register

| Risk                                                  | Severity     | Mitigation                                                                                                                                                   |
| ----------------------------------------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **An admin route ships without a permission check**   | **Critical** | Rebuild the boot-time invariant (§5) before any admin route exists. An architecture test that enumerates routes and fails on a missing rule. Non-negotiable. |
| Razorpay webhook verified against a parsed body       | **Critical** | Capture the raw body in middleware before Laravel parses it. Port the signature fixtures in Phase 1.                                                         |
| TOTP/password parity wrong → nobody can sign in       | High         | Cross-language fixtures in Phase 1, before any UI. Test against a copy of real hashes.                                                                       |
| Both frameworks mutate the schema in Stage 1          | High         | §7 rule 4: Prisma owns DDL until Stage 2 completes.                                                                                                          |
| GST computed wrong → incorrect invoices, tax exposure | High         | Port `checkout.service` tests as PHPUnit cases first; reconcile against the Node implementation on the same inputs.                                          |
| Money as float                                        | High         | Integers in paise everywhere; a lint/architecture test forbidding float casts on money fields.                                                               |
| Inventory oversell from a reservation race            | High         | Replicate the transaction isolation and locking exactly; load-test concurrent checkout.                                                                      |
| Audit log leaks secrets                               | Medium       | Port the redaction list and its tests (`sanitize-metadata.ts`).                                                                                              |
| Scope creep ("while we're rewriting…")                | Medium       | Parity first. Feature changes only after Playwright is green.                                                                                                |
| Migration stalls half-done                            | Medium       | §1 sequencing: a running shop means a stall is a delay, not a crisis.                                                                                        |

---

## 10. What I need from you to start

| # | Question | Status |
| --- | --- | --- |
| 1 | Fix the pipeline and launch COD-only on the Node stack first? | **Declined** — the Node app is not to be modified. Consequence recorded above. |
| 2 | Blade + Livewire (no Node) or Inertia + React (keeps components, keeps Vite)? | **Answered: Blade + Livewire** |
| 3 | Directory and repository | **Answered: sibling `Dhansree-php`, separate repository** |
| 4 | **COD-only first cut** (phases 0–4, 7, 8; Razorpay and Shiprocket deferred, ~4.5–6 months) — or the full port before launch (~7–9 months)? | **Open** |
| 5 | **Who writes it?** If a PHP developer does, I produce per-phase specifications; if I do, we work phase by phase with a review gate at each. | **Open** |
| 6 | **Git remote.** `gh` is not installed on this machine, so I cannot create the GitHub repository. Give me the remote URL (or create an empty repo and paste it) and I will push. | **Open** |

The repository now contains the Laravel skeleton and the verified cryptography parity layer
(§5). No feature work has started.
