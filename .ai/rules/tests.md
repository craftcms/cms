---
paths:
  - 'tests/**'
---

# Tests

## Use the main Pest suite
Run these tests with `composer tests` or `./vendor/bin/pest tests/path/to/TestFile.php`. Tests using `TestCase` share the database lock; `tests/Unit` tests using `UnitTestCase` do not.

## Prefer real test paths
Exercise real code paths or use Laravel facades for service mocks. Production `final` and `readonly` keywords are stripped in tests, so focused test-only subclasses are acceptable.
