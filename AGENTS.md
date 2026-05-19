# AGENTS.md

Guidance for AI coding agents working in this repository.

## Overview

Craft CMS 6 runs on Laravel 13. The Yii2 port is complete; `craftcms/yii2-adapter` exists only as a backwards compatibility package for plugins and integrations that still call Yii-era Craft APIs.

New core work should be Laravel-first. Do not add Yii dependencies to `src/`; put plugin compatibility shims, legacy constants, event bridges, and Yii fallbacks in `yii2-adapter/`.

This is a large codebase with some large files. Search narrowly before reading full files.

## Testing

- Pest tests using `tests/TestCase.php` or `yii2-adapter/tests-laravel/TestCase.php` share a database lock. If another process has the lock, the next process will wait and print `Another Pest process is already using the shared test database. Waiting for the lock...`.
- `tests/Unit/` tests using `UnitTestCase` do not take that lock and can still run concurrently.
- When writing tests, prefer real code paths, or use Laravel facades to set up service mocks.
- Classes marked `final` or `readonly` have that keyword stripped during testing. Test-only subclasses of production classes are acceptable when they make focused setup easier.
- When creating or adjusting tests, use the `testing-guidelines` skill for repository-specific patterns.

## Core Patterns

- Content is queried through elements and element queries, not Eloquent models, when an element type exists.
- Use `CraftCms\Cms\Database\Table` constants for table names.
- Element work must respect the base `elements` and `elements_sites` tables.
- Craft-specific configuration belongs on `GeneralConfig`; do not add generic Laravel config for Craft behavior.
- Use `t()` for translations. Its second argument is replacement parameters; pass the category as the third argument or as a named argument.
- Laravel events are the native event system. Yii event constants and bridge registration belong in `yii2-adapter` for compatibility only.
- Services that should be singletons generally use Laravel's `#[Singleton]` or `#[Scoped]` attribute.

## Frontend

The Control Panel contains both legacy Twig/jQuery surfaces and newer Inertia + Vue screens. Prefer `@craftcms/cp` components when building UI, and match whichever surface the surrounding feature already uses.

## Adapter Work

`yii2-adapter` is compatibility code, not the implementation path for new core behavior. If you need to add adapter classes, follow its Composer autoload mapping. Do not put general adapter classes in `yii2-adapter/lib/`; that area is for vendored or library-style code.

## Pitfalls

- Some files contain Unicode characters in comments and strings. If a text edit fails unexpectedly, inspect the exact bytes or copy the surrounding text from the file.
- PHP 8.2+ does not allow accessing constants on traits directly. Access trait-provided constants through a class that uses the trait.
- `declare(strict_types=1)` is required in PHP files.
- Classes are non-final by default; use `readonly` when it fits.
- You do not need to manually remove unused imports; Pint will fix them.
