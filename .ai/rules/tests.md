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
