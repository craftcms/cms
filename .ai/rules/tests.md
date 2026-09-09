---
paths:
  - 'tests/**'
---

# Tests

## Use the main Pest suite
Run these tests with `composer tests` or `./vendor/bin/pest --parallel tests/path/to/TestFile.php`.

## Prefer real test paths
Exercise real code paths or use Laravel facades for service mocks. Production `final` and `readonly` keywords are stripped in tests, so focused test-only subclasses are acceptable.
