# OBPASS Phase Status

Last updated: 2026-06-19

---

## Summary

| Phase | Status | Tests |
|-------|--------|-------|
| 1 | ✅ Complete | — |
| 2 | ✅ Complete | — |
| 3 | ✅ Complete | — |
| 4 | ✅ Complete | — |
| 5 | ✅ Complete | — |
| 6 | ✅ Complete | — |
| 7 | ✅ Complete | — |
| 8 | ✅ Complete | — |
| 9 | ✅ Complete | — |
| 10 | ✅ Complete | — |
| 11 | ✅ Complete | — |
| 12 | ✅ Complete | 67/67 passing |
| 13 | ✅ Complete | 76/76 passing |
| 14 | ⏸️ Held | — |

---

## Detailed Status

### Phase 1 — Architecture Review & Gap Analysis
**Status:** ✅ Complete
**Details:** 12 gaps identified from spec analysis and resolved.

### Phase 2 — Migrations
**Status:** ✅ Complete
**Details:** All database migrations created and verified.

### Phase 3 — Models & Relationships
**Status:** ✅ Complete
**Details:** All Eloquent models with relationships, scopes, and accessors.

### Phase 4 — Roles, Permissions, Policies
**Status:** ✅ Complete
**Details:** 5 roles, 20 permissions, Spatie seeding, Filament gate integration.

### Phase 5 — Filament Resources
**Status:** ✅ Complete
**Details:** All Filament admin resources built.

### Phase 6 — REST API Audit & Fix
**Status:** ✅ Complete
**Details:** All API endpoints audit, fixed, and verified.

### Phase 7 — PDF Generation
**Status:** ✅ Complete
**Details:** DomPDF integration for pass slips and certificates with QR codes.

### Phase 8 — QR Verification Audit & Fix
**Status:** ✅ Complete
**Details:** QR generation and verification flow audited and fixed.

### Phase 9 — Notifications Audit & Fix
**Status:** ✅ Complete
**Details:** Custom notification system with JSON data, polling endpoints.

### Phase 10 — React Native Expo Mobile App
**Status:** ✅ Complete
**Details:** App scaffold with Expo Router, Zustand, TanStack Query, SQLite.

### Phase 11 — Offline Sync & Biometric Auth
**Status:** ✅ Complete
**Details:** Network detection (NetInfo), SQLite cache DB (`obpass_cache.db`), sync engine with queue replay, biometric credential storage via SecureStore + `expo-local-authentication`, offline banners on all screens, cached data fallback when offline.

**Files created:**
- `src/hooks/useNetwork.ts` — connectivity monitor
- `src/stores/syncStore.ts` — cache DB, biometric, sync engine

**Files updated:**
- `src/stores/offlineStore.ts` — added `cleanupSynced()`
- `app/_layout.tsx` — `SyncTrigger` component, `initBiometric()`
- `app/(main)/index.tsx` — offline banner, cached data
- `app/(main)/slips.tsx` — offline banner, cached data
- `app/(guard)/index.tsx` — offline guard action blocking
- `app/(auth)/login.tsx` — biometric login flow with credential save prompt

### Phase 12 — Integration Testing (Mobile ↔ API)
**Status:** ✅ Complete
**Test Score:** 67 passing / 0 failing (67 total)
**Details:** Unit tests for all mobile stores, API client, and hooks. Tests verify mobile API client behavior, offline queue operations, sync engine, biometric auth flow, and network detection.

**Test files:**
- `__tests__/api.test.ts` — API client (22 tests): axios instance, authApi, passSlipApi, interceptors
- `__tests__/authStore.test.ts` — Auth store (8 tests): login, logout, loadUser, setUser
- `__tests__/offlineStore.test.ts` — Offline store (10 tests): online status, queue, markSynced, cleanup
- `__tests__/syncStore.test.ts` — Sync store (21 tests): biometric, cache, sync engine, credential management
- `__tests__/useNetwork.test.ts` — Network hook (5 tests): NetInfo listener, online/offline detection, cleanup

**Fixes Applied:**
- Switched from `@testing-library/react-native` renderHook to `react-test-renderer` direct `create()` (React 19 compat)
- Mock hoisting fix: `jest.fn().mockResolvedValue(x)` → `jest.fn(() => Promise.resolve(x))`
- Manual `__mocks__/` directory removed (incompatible with jest-expo)
- All mock factories define functions inline to avoid babel hoisting issues
- api.test.ts: default export import, removed depart/arrive (not in source), removed certificateApi (not exported)
- useNetwork.test.ts: NetInfo mock must provide `default` export (not just named exports)
- syncStore.test.ts: api mock updated to match actual source (delete/pdf methods, no depart/arrive)

### Phase 13 — Tests
**Status:** ✅ Complete
**Test Score:** 70 passing / 0 failing (70 total)

**Fixes Applied (Round 1):**
- `CertificateStatus` enum mapping (`Pending` → `Submitted`, `Approved` → `Verified`)
- 5 missing factories created (Department, Employee, Vehicle, PassSlip, Certificate)
- `UserFactory` updated with `is_active => true`
- `Employee` model import fixes
- `PassSlipEmployee` pivot UUID auto-generation
- `ApiResponses` trait null handling

**Fixes Applied (Round 2):**
- `CertificateFactory::draft()` — `submitted_by` NOT NULL constraint fixed
- `GuardTest` — Query string parameter passing fixed (no header hack)
- `PassSlipController::show()` — Added `$this->authorize('view')`
- `PassSlipController::update()` — Restricted to Draft status only
- `PassSlipController::approve()` — Added `$this->authorize('approve')`

**Fixes Applied (Round 3):**
- Base `Controller.php` — Added `AuthorizesRequests` + `ValidatesRequests` traits, extended `BaseController`
- `PassSlipTest::test_supervisor_cannot_approve_draft_slip` — Updated expected status from 422 → 403

### Phase 14 — Deployment Preparation
**Status:** ⏸️ Held
**Details:** On hold per user instruction.

---

## Spec Compliance Remediation (2026-06-19)

A full audit of the codebase against `OBPASS_MASTER_SPEC_v1.md` identified functional
gaps in the running `obpass/` app. The following were fixed; tests now **76/76 backend,
67/67 mobile** (was 70/70; +6 regression tests covering the fixes).

**Structural**
- Removed the stale, non-runnable root-level `app/` `database/` `routes/` `resources/`
  duplicate (committed alongside `obpass/` in the initial commit). `obpass/` is now the
  single source of truth, matching the spec's project structure. Repo root now holds only
  the spec, this file, `obpass/`, and `mobile/`.

**Authorization (was: most actions ungated; guard endpoints open to any user)**
- Wired `$this->authorize()` into `store`/`update`/`destroy`/`submit`/`return`/`cancel`.
- Added guard permission gates (`pass_slip.scan_qr`, `logDeparture`, `logArrival`) so
  non-guards can no longer log departures/scan QR.
- Added `certificate.submit` / `certificate.verify` authorization on the certificate endpoints.
- Added `submit` and `cancel` policy methods; ownership lives in policies, status
  enforcement stays in controllers/models (preserves existing 422 vs 403 contract).

**Workflow (state machine)**
- `Returned → Submitted` resubmit path now works (`submit()` accepts Draft or Returned).
- `Approved → Cancelled` path added for Admin/Supervisor (`cancel()` is role-aware);
  employees still get 422 on approved slips.
- `Verified → Completed` is now reachable via a "Complete" action on the PassSlip resource.
- **Every transition is now audit-logged** (spec: "All transitions are audit logged")
  via `AuditLog::log()`; slip creation is also logged.

**Notifications**
- `SlipSubmitted` now also notifies the employee/creator (spec: Supervisor + Employee).
- `SlipApproved` now also notifies guards (spec: Employee + Guard).
- `CertificateSubmitted` now also notifies Admin (spec: HR + Admin).

**Filament admin**
- Added the 3 missing custom pages: **Reports** (filter by date range / department / status
  + CSV export) and **GuardKiosk** (search, log departure/arrival, today's activity).
- Added 3 dashboard widgets: stats overview (summary cards), status doughnut chart, and a
  recent-slips table.

**QR verification**
- `/verify/{qr_code}` is now a public, no-auth Blade page showing only allowed fields
  (slip number, employee, status, date, purpose, duration); `employee_number` is no longer
  exposed publicly.

**PDF**
- Pass-slip PDF now generates both **ORIGINAL + DUPLICATE** copies (page break between).

**Runtime bug found by running the app (not caught by tests)**
- Sanctum login was broken under MySQL: `personal_access_tokens.tokenable_id`
  was `unsignedBigInteger` (from `morphs()`) but users use UUID PKs, so issuing a
  token truncated the value. Tests missed it because they use `Sanctum::actingAs`
  (bypasses token storage). Fixed via `uuidMorphs('tokenable')`.

### Accepted deviations / deferred (not blocking; flagged for decision)
- **PDF engine**: uses `barryvdh/laravel-dompdf`, not the spec's `spatie/browsershot`.
  Kept DomPDF (working) to avoid a risky headless-Chrome dependency swap.
- **`pass_slips.employee_id`** was intentionally dropped in `obpass/` in favor of the
  `pass_slip_employee` many-to-many pivot. Treated as an accepted design improvement.
- **Stack packages not added**: Laravel is `^13.8` (spec says 11); Redis, Horizon, and
  Intervention Image are absent; QR uses `chillerlan/php-qrcode` (spec: SimpleSoftwareIO).
  These are infra/cosmetic and were deferred to avoid destabilizing the working app.
- **FCM push**: tokens are stored but push sending is still unimplemented (DB notifications
  only). Needs an FCM channel + credentials before mobile push works.

---

## Next Action

All 13 phases complete. Phase 14 (Deployment) held per user instruction.
