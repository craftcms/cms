---
name: testing-guidelines
description: Guidance for designing, writing, reviewing, and updating Craft CMS 6 tests with Pest and Laravel. Covers suite selection, assertions, test data, dependency isolation, element factories, CP URLs, custom fields, traits, and events. Use when creating, modifying, debugging, or reviewing tests.
---

# Testing Guidelines

## Overview

Apply Craft CMS 6 testing patterns consistently and avoid common pitfalls around elements, CP URLs, and event assertions. Use the quick rules below, then load the reference file for full examples and code snippets.

## Test Directory Structure

Tests are split into two directories based on whether they require a database:

- **`tests/Unit/`** — Unit tests that do NOT require a database. These use `UnitTestCase` (a lightweight `Orchestra\Testbench\TestCase` with no database, no Yii2 bootstrap, no migrations). Only the Laravel service container is available.
- **`tests/Feature/`** — Feature/integration tests that DO require a database. These use `TestCase` (which includes `RefreshDatabase`, full migrations via the `Install` migration, and Yii2 bootstrapping).

This is configured in `tests/Pest.php`:
```php
uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');
```

**Deciding where to put a test:**
- If the test needs database records (factories, element queries, saving elements, etc.) → `tests/Feature/`
- If the test only needs the service container (testing pure logic, config, formatting, validation rules, etc.) → `tests/Unit/`

## Workflow

1. Decide whether the test needs a database (Feature) or not (Unit).
2. Identify the test scope (CP URL, element behavior, trait, event, custom field).
3. Follow the matching rule set below.
4. Pull detailed examples from `references/testing-guidelines.md` when needed.
5. Implement tests using Pest and repo conventions.

## Core Rules

- Read nearby tests first and follow their declaration and organization conventions.
- Use Boost's `search-docs` for version-specific Pest and Laravel testing syntax. Confirm an assertion or feature before using it.
- Test observable behavior and application contracts. Cover each changed decision and applicable high-value failure path, but leave framework behavior to framework tests.
- Run the narrowest relevant test file or filter. Rerun a test after changing it.
- Do not delete tests or test files without approval.
- Do not add comments in test files — no section separators (e.g., `// -- section --`), no inline explanations, no docblocks. Test names should be descriptive enough on their own. Use `describe()` blocks to group related tests instead of comments.
- Keep test-local abstractions proportional to the repetition they remove. Small one-off helpers such as route wrapper closures or tiny passthrough methods usually shouldn’t exist; inline the setup or request unless the extraction materially improves readability or reuse.
- Use `CraftCms\Cms\Cms::config()->cpTrigger` when asserting CP URLs; never hard-code `/admin`.
- Do not instantiate element classes directly with `new` in tests; use factories to ensure database state.
- Prefer factories and element queries over Eloquent models when asserting element behavior.
- For element traits, create minimal test elements that override only what is needed.
- Use Laravel event fakes/listeners to assert dispatch, cancellation, or data changes.
- Use Pest's `->with()` data providers to consolidate tests that share the same structure but differ only in input/expected values. Use named dataset entries for clarity.
- Prefer subject-specific Laravel assertions and Pest expectations over generic status or boolean assertions. Keep each `expect()` chain focused on one subject.
- Import `use function Pest\Laravel\mock;` before using Pest's `mock()` helper.
- Use fixed expected values or calculate them independently from production logic.
- For write operations, assert the response or return value, persisted state, and relevant side effects. On failure paths, assert that no unintended change occurred.
- Control time, randomness, sleep, and outbound HTTP when they affect the test. Use Laravel fakes and real database queries instead of mocking the query builder.
- Avoid long chains of `toContain()` / `not()->toContain()` assertions against rendered HTML. They tend to test incidental markup, labels, ordering, and template structure instead of behavior. Prefer assertions that target the semantic contract directly, such as input names, selected values, option values, or data attributes. Keep raw string containment assertions for small, stable strings that are themselves the contract.
- Tests that assert Yii2 backwards-compatibility surfaces (legacy aliases, `ValidateMixin` helpers like `hasErrors()`, other adapter-only behavior) must live in `yii2-adapter/tests-laravel/`, not `tests/Feature/` or `tests/Unit/`.

## When to Load References

- Need code samples for entry creation, custom fields, or event tests.
- Need the minimal trait-testing element example.
- Unsure about the exact factory + query pattern for elements.
- Need examples of Pest data providers with `->with()`.

See `references/testing-guidelines.md` for full examples and snippets.
