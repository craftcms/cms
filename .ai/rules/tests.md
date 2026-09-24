---
paths:
  - 'tests/**'
---

# Tests

## Use the main Pest suite
Run these tests with `composer tests` or `./vendor/bin/pest --parallel tests/path/to/TestFile.php`.

## Prefer real test paths
Exercise real code paths or use Laravel facades for service mocks. Production `final` and `readonly` keywords are stripped in tests, so focused test-only subclasses are acceptable.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.

## runningInConsole() is true in HTTP tests
Under Testbench, `app()->runningInConsole()` returns true even for `get()`/`post()` feature tests. Any production code guarded by `! runningInConsole()` is silently skipped in tests, so the behaviour looks broken or untestable for reasons that have nothing to do with the code under test.

`RequestedSite::get()` had this bug: it ignored `?site=` in every test, so the CP always resolved to the primary site. Fixed by widening the guard to `(! app()->runningInConsole() || app()->runningUnitTests())`.

If a request-only code path mysteriously does nothing in a test, check for a console guard before suspecting the test.
