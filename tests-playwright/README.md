# Playwright tests

Browser tests for the CP. There are two ways to run them:

## Workbench mode (default)

Runs against the Testbench workbench install — the same app you get from `composer serve`.
No docker/ddev required.

```bash
npm run test:e2e                # boots workbench:serve itself (or reuses a running one)
E2E_FRESH=1 npm run test:e2e    # rebuild + reseed the sqlite db first
npx playwright test --ui        # interactive UI mode
```

If you already have `composer serve` running, the suite reuses it (outside CI). Otherwise
Playwright starts `workbench:serve` on the same stable port and shuts it down afterwards.

**Auth:** `workbench.setup.ts` hits the workbench's `/_workbench/login/1` route (user 1 is
the admin the seeder creates), verifies it via `/_workbench/user`, and saves the session to
`.auth/user.json`, which every test reuses. This requires persistent sessions —
`SESSION_DRIVER=database` in `workbench/.env` (see `workbench/.env.example`).

**Assets:** CP assets resolve through `cms-assets/resources/hot` when a Vite dev server is
running, otherwise through the built manifest (`npm run build:all`). A stale hot file (dev
server not actually running) fails the suite early with instructions.

**Env vars:**

- `E2E_PORT` — override the serve port (default: the stable port `workbench:serve` derives
  from the repo path)
- `E2E_BASE_URL` — point at an already-running install instead
- `E2E_FRESH` — pass `--fresh` to `workbench:serve`
- `CI` — never reuse a running server, retries, GitHub reporter

Tests run sequentially against one shared sqlite install; write tests that tolerate the
seeded content (see `workbench/database/seeders/DatabaseSeeder.php`) or create what they
need.

## ddev mode (legacy harness)

The original harness boots a ddev docker environment, installs Craft in it, and loads
Codeception fixtures (`tests/fixtures/*Fixture.php`). It uses
`playwright.ddev.config.cjs`; see `packages/craftcms-playwright/README.md`.

```bash
npm run test:e2e:ddev                                    # full boot → test → teardown
npx craft-playwright boot                                # just boot the env
npx playwright test --config=playwright.ddev.config.cjs  # run against a booted env
```

## Suite migration status

The test suites under `tests/` were written for the ddev harness against the Craft 5-era
CP. Suites not yet runnable in workbench mode are listed in `testIgnore` in
`packages/craftcms-playwright/src/playwright/config/workbench.js`:

| Suite            | Status in workbench mode                                          |
| ---------------- | ----------------------------------------------------------------- |
| `smoke.test.ts`  | ✅ runs                                                            |
| `account/`       | ✅ runs (one `fixme`: user-menu items lack accessible roles)       |
| `navigation/`    | ⛔ blocked: `/admin/dashboard` 500s (`Sites` twig global missing)  |
| `settings/`      | ⛔ assumes an empty install; workbench seeds sections/entry types  |
| `elementindex/`  | ⛔ needs Codeception fixtures (`testSorting` section)              |
| `elements/`      | ⛔ needs Codeception fixtures                                      |
| `matrix/`        | ⛔ needs Codeception fixtures (`testMatrix` section)               |
| `pluginstore/`   | ⛔ needs installed plugins + external network                      |

To migrate a suite: make the workbench seeder provide the content it needs (or create it
in the test), update selectors for the redesigned CP, then remove it from `testIgnore`.

> [!TIP]
> For tests on pages that use `ElementEditor`, use `.pressSequentially('text', {delay: 100})`
> instead of `.fill('text')` — custom keyboard handling makes `fill` flaky.
