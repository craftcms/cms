---
paths:
  - 'yii2-adapter/tests-laravel/**'
---

# Tests Laravel

## Use the adapter Pest suite
Run these tests through `composer tests-adapter`. Do not mix adapter and main test paths in one Pest invocation; the adapter suite must load its own `Pest.php`.

## Choose the adapter base class by runtime needs
Use the adapter `TestCase` for compatibility behavior that needs Orchestra
Testbench but no persisted Craft state. Use `DatabaseTestCase` when the path
needs the core Craft database setup. Follow the nearest adapter tests because
individual directories and files may select their base class explicitly.

## Don't comment tests unless the behaviour is odd
Leave tests uncommented; the test name and assertions say what is being checked. Only comment when the behaviour under test is decidedly odd and a reader couldn't infer why the assertion holds, and keep it to one line about the code, not the bug or history that prompted the test.
