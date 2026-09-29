---
paths:
  - 'tests/**'
---

# Tests

## Use the main Pest suite
Run these tests with `composer tests` or `./vendor/bin/pest --parallel tests/path/to/TestFile.php`.

## Choose the base class by runtime needs
`tests/Unit/` uses `UnitTestCase`. It boots the package, routes, Laravel
container, and Craft configuration without running Craft migrations. Use it
when the behavior needs no persisted state.

`tests/Feature/` uses `TestCase`, `RefreshDatabase`, the Craft install
migration, and the full Craft integration environment. Use it for persistence,
element queries, project config, authentication, and HTTP behavior. The base
class seeds the default admin user and prevents stray HTTP requests; make any
test-specific changes to those defaults explicit.

## Prefer real test paths
Exercise real code paths or use Laravel facades for service mocks. Production `final` and `readonly` keywords are stripped in tests, so focused test-only subclasses are acceptable.

Use factories followed by element queries when persisted element state is part
of the contract. Direct construction is valid for unsaved-element behavior,
policy inputs, configuration, and focused test subclasses when persistence is
irrelevant. Keep test subclasses minimal and exercise the production entry
point.

## Follow Craft test mechanics
Build control-panel URL expectations from
`CraftCms\Cms\Cms::config()->cpTrigger`; never hard-code `/admin`.

Inspect the production dispatch before testing an event. Use Laravel event
fakes for delivery contracts and listeners for cancellation or mutation. Do
not fake an event whose real listeners are required by the behavior under test.

Use named Pest datasets only when every case follows the same execution and
assertion shape. Import `use function Pest\Laravel\mock;` before using Pest's
`mock()` helper. Derive expected values independently from the implementation.

Control time, randomness, sleep, and outbound HTTP when they affect the test.
Use real database queries rather than mocking the query builder. Prefer
semantic HTML assertions over long raw-string containment chains; use an exact
string assertion when that string is the contract.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.

## runningInConsole() is true in HTTP tests
Under Testbench, `app()->runningInConsole()` returns true even for `get()`/`post()` feature tests. Any production code guarded by `! runningInConsole()` is silently skipped in tests, so the behaviour looks broken or untestable for reasons that have nothing to do with the code under test.

`RequestedSite::get()` had this bug: it ignored `?site=` in every test, so the CP always resolved to the primary site. Fixed by widening the guard to `(! app()->runningInConsole() || app()->runningUnitTests())`.

If a request-only code path mysteriously does nothing in a test, check for a console guard before suspecting the test.

## Admin-guard tests must grant accessCp to the non-admin
A demoted admin or factory user has no permissions on Pro, so CP routes 403 at `can:accessCp` before `RequireAdmin`/`RequireAdminChanges`/session-auth checks run. When asserting a non-admin is forbidden, grant `accessCp` first (`UserPermissions::saveUserPermissions($id, ['accessCp'])` or `withPermissions(['accessCp'])`), otherwise the test passes even if the admin guard is removed.
