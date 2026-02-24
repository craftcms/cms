---
name: testing-guidelines
description: Guidance for writing and updating Craft CMS 6 tests (Pest/Laravel) including element factories, CP URL rules, custom field setup, trait testing, and event assertions. Use when creating or modifying tests, debugging test patterns, or aligning new tests with repository testing standards.
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

- Use `CraftCms\Cms\Cms::config()->cpTrigger` when asserting CP URLs; never hard-code `/admin`.
- Do not instantiate element classes directly with `new` in tests; use factories to ensure database state.
- Prefer factories and element queries over Eloquent models when asserting element behavior.
- For element traits, create minimal test elements that override only what is needed.
- Use Laravel event fakes/listeners to assert dispatch, cancellation, or data changes.
- Use Pest's `->with()` data providers to consolidate tests that share the same structure but differ only in input/expected values. Use named dataset entries for clarity.
- Tests that assert Yii2 backwards-compatibility surfaces (legacy aliases, `ValidateMixin` helpers like `hasErrors()`, other adapter-only behavior) must live in `yii2-adapter/tests-laravel/`, not `tests/Feature/` or `tests/Unit/`.

## When to Load References

- Need code samples for entry creation, custom fields, or event tests.
- Need the minimal trait-testing element example.
- Unsure about the exact factory + query pattern for elements.
- Need examples of Pest data providers with `->with()`.

See `references/testing-guidelines.md` for full examples and snippets.
