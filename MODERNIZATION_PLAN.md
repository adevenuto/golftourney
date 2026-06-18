# Black League — Modernization Plan

> Goal: modernize the application's stack, architecture, and patterns **before** adding new
> functionality. Decisions locked in: **adopt Inertia.js**, **drop jQuery/DataTables**,
> **upgrade all dependencies to latest**.

---

## 0. Current State (baseline)

| Layer | Today |
|-------|-------|
| Framework | Laravel 10.48, PHP 8.1+ (running 8.3) |
| Frontend | Vue 3 (Options API) + axios → JSON web routes; **jQuery + DataTables vendored in `public/assets/`** |
| Build | Vite 4, Tailwind 3, Sass |
| Auth | `laravel/ui` scaffold + Sanctum (largely unused) |
| DB | MySQL; `Golfer`, `Round`, `User` with **no relationships defined** |
| Tests | Example stubs only |

**Known issues to fix along the way**
- 🔴 Authorization is client-side only (`role` passed as a Vue prop; write/delete routes have no server check).
- 🟡 `config/database.php` SQL modes removed to make a `GROUP BY` query work (not strict-mode-safe).
- Handicap logic in a controller trait with magic numbers (`31.5`, `113`, `104`) and hardcoded course `'Robert A. Black'`.
- Broad `try/catch` leaking `$e->getMessage()` to clients with blanket `400`s.
- Non-RESTful, unnamed routes (`POST /golfers/{id}/edit`, `POST /rounds/store`).
- Duplicate password-reset migrations (`password_resets` **and** `password_reset_tokens`).
- Names stored lowercased and re-capitalized via CSS; timestamps mass-assigned from request input.
- `APP_DEBUG=true`; empty scaffolded `tournament/index.blade.php`.

**Target State**
- Laravel 12 + PHP 8.3, Inertia 2 + Vue 3 `<script setup>`, Vite 6, Tailwind 4.
- No jQuery, no DataTables, no axios-to-JSON plumbing.
- Server-owned authorization (Policies + role enum), Form Requests, service-layer handicap logic.
- Eloquent relationships, RESTful named routes, a real Pest test suite, CI.

---

## Phase 1 — Safety & Hygiene (small, high-value, do first)

Goal: close the real hole and clean cruft **before** restructuring, on the current stack.

1. **Server-side authorization**
   - Create a `Role` string-backed enum (`admin`, `player`).
   - Add a `Gate`/middleware (or simple policy) guarding all golfer/round **write & delete** routes.
   - Keep the Vue `role` prop only for *showing/hiding* UI — never for enforcement.
2. **Fix the GROUP BY / SQL modes issue**
   - Rewrite the golfers-with-round-count query to be `ONLY_FULL_GROUP_BY`-safe
     (`withCount('rounds')` once relationships exist, or an explicit aggregate).
   - Restore strict SQL modes in `config/database.php` (revert the working change properly).
3. **Cruft removal**
   - Delete the redundant `password_resets` migration (keep `password_reset_tokens`).
   - Remove/disable empty `tournament` view until that feature is built.
   - Set `APP_DEBUG=false` for non-local; confirm `.env` stays untracked (already gitignored ✓).
4. **Error handling**
   - Stop returning raw exception messages; return structured errors with correct status codes.

**Deliverable:** a secure, tidy baseline still on Laravel 10. Independently shippable.

---

## Phase 2 — Backend Refactor & Tests

Goal: a clean, testable domain layer. Still framework-version-agnostic, so safe to do before the big upgrade.

1. **Eloquent relationships**
   - `Golfer hasMany Round`; `Round belongsTo Golfer`. Add `$casts` for dates/decimals.
2. **Handicap service**
   - Extract `HandicapTrait` → `App\Services\HandicapService` (or `Golfer::recalculateHandicap()`).
   - Promote magic numbers to named constants/config; make course-name a column-driven value, not hardcoded.
   - Document the rule: *best 8 of last 20 rounds*.
3. **Form Requests + Resources**
   - `StoreGolferRequest`, `UpdateGolferRequest`, `StoreRoundRequest`, etc.
   - Stop normalizing names to lowercase in storage; store as entered, present with CSS only if desired.
4. **RESTful named routes**
   - `Route::resource('golfers', ...)`, nested `golfers.rounds`. Replace ad-hoc verbs.
5. **Tests (Pest)**
   - Unit: handicap math edge cases (0 rounds, <8 rounds, ties).
   - Feature: authorization (player cannot delete), CRUD happy/sad paths.

**Deliverable:** controllers thin, logic tested, routes RESTful.

---

## Phase 3 — Framework & Dependency Upgrade

Goal: get to latest. Do this **after** tests exist so regressions are caught.

1. **PHP/Laravel**: 10 → 11 → 12 (incremental, using Laravel Shift or manual upgrade guide). Run the suite at each step.
2. **Auth**: migrate `laravel/ui` scaffold → **Laravel Breeze (Inertia + Vue preset)**, which also seeds the Inertia setup for Phase 4. Decide whether to keep Sanctum (only needed if a real API/mobile client is added later — likely drop).
3. **Frontend deps**: Vite 6, Tailwind 4, Vue latest. Remove unused (`boxicons`, `tippy.js`, `oh-vue-icons` if replaced).
4. **Tooling**: add Pint (config), Larastan/PHPStan, and a GitHub Actions CI running tests + static analysis. Dependabot.

**Deliverable:** latest stack, green CI.

---

## Phase 4 — Inertia + Vue Frontend Overhaul (the big one)

Goal: retire jQuery/DataTables and the JSON-over-web-routes pattern; server-driven Inertia pages.

1. **Wire Inertia** (from Breeze preset): root template, `app.js` Inertia setup, Vite plugin.
2. **Convert controllers** to return `Inertia::render('Golfers/Index', [...])` with props
   (golfers + round counts + the *server-resolved* permissions). Remove the JSON endpoints + axios calls.
3. **Rebuild pages as `<script setup>` Vue components**
   - `Golfers/Index` — replace DataTables with a Vue-native table (TanStack Table or a small composable: client-side sort/search/paginate; server-side later if the roster grows).
   - `Golfers/Rounds` (a.k.a. ManageRounds) — Inertia page; drop `window.location` path parsing in favor of route params.
   - Reusable `Modal`, table, and form components; keep `@vuepic/vue-datepicker` (still maintained) or evaluate a lighter native date input.
4. **Kill jQuery**: delete `public/assets/js/jquery*.js`, all `datatables-*` files, and the `pagespecific-header-items` script block in `golfers/index.blade.php`.
5. **PDF export** (currently a DataTables button): re-implement server-side (e.g. `barryvdh/laravel-dompdf`) — more reliable and removes the last DataTables dependency.

**Deliverable:** no jQuery, no DataTables, SPA navigation, server-owned auth in the UI.

---

## Phase 5 — Design & UX Polish

Goal: lift the visual quality and accessibility now that the structure is clean.

1. Semantic, accessible interactions (real `<button>`s instead of clickable `<div>`s; focus states; aria on modals).
2. Cohesive design system on Tailwind 4 (tokens, spacing scale), optional dark mode.
3. Responsive table/empty/loading states; toast notifications for actions.
4. Refresh the `welcome`/landing page (currently just an image).

**Deliverable:** a polished, accessible UI — and a clean base to start *new feature* work (e.g. the tournament module that was scaffolded).

---

## Sequencing rationale

- **Phases 1–2 first** because they harden and test the domain on the *current* stack — low risk, independently shippable, and they make the upgrade safe.
- **Phase 3 (upgrade) before Phase 4** so the Inertia work targets the latest APIs once, not twice.
- **Phase 4 is the headline modernization** (jQuery/DataTables removal + Inertia) and depends on everything above.
- **Phase 5** is intentionally last so polish lands on stable foundations and flows directly into new feature development.

## Suggested PR breakdown

Each numbered item above ≈ one focused PR. Phase 1 and 2 are ~2–3 PRs each; Phase 4 is best split per page (Golfers index, Rounds, shared components, PDF, jQuery removal).
