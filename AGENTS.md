# AGENTS.md

Guidance for AI coding agents working in this repository.

## Overview

Craft CMS 6 runs on Laravel 13. The Yii2 port is complete; `craftcms/yii2-adapter` exists only as a backwards compatibility package for plugins and integrations that still call Yii-era Craft APIs.

New core work should be Laravel-first. Do not add Yii dependencies to `src/`; put plugin compatibility shims, legacy constants, event bridges, and Yii fallbacks in `yii2-adapter/`.

This is a large codebase with some large files. Search narrowly before reading full files.

## Commands

### PHP

```bash
composer tests                # Run all Pest tests
composer tests-adapter        # Run yii2-adapter tests only
./vendor/bin/pest path/to/TestFile.php          # Run a single test file
./vendor/bin/pest --filter "test description"   # Run tests matching a name
composer fix-cs               # Run Rector + Pint + ECS (auto-fixes code style)
composer phpstan              # Run PHPStan static analysis (level 5)
composer ci                   # Full CI pipeline: pint, rector, phpstan, tests, tests-adapter
composer serve                # Start the testbench dev server
```

### Frontend

```bash
npm run dev           # Vite dev server (HMR) for the Inertia/Vue CP
npm run build         # Production Vite build (cp.ts + legacy.ts + cp.css)
npm run build:all     # Build legacy bundles + CP component package + Vite
npm run dev:bundles   # Webpack dev watch for legacy jQuery bundles
npm run dev:ui        # Dev build for the @craftcms/ui component package
npm run build:ui      # Production build for the @craftcms/ui component package
npm run lint          # ESLint + Stylelint + TypeScript type-check
npm run typecheck     # TypeScript type-check only (vue-tsc)
npm run test:ui       # Vitest tests for the @craftcms/ui package
```

> **Note:** `@craftcms/ui` must be built (`npm run build:ui`) before building or running the main Vite app if you've
> made changes to it.

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

## Frontend Architecture

The CP has two parallel rendering stacks that are actively being consolidated:

**Inertia/Vue (new):** `resources/js/cp.ts` is the entrypoint. Inertia pages live in `resources/js/pages/`, shared Vue
components in `resources/js/common/`. `HandleInertiaRequests` middleware provides shared CP config, navigation, and
global props to all Inertia pages. The root Blade template is `resources/views/app.blade.php`.

**Legacy jQuery (old):** `resources/js/legacy.ts` loads the old surface. The individual jQuery modules live in
`packages/craftcms-legacy/` and are bundled with webpack (separate from Vite). Pages still on this stack return `view()`
from their controllers.

**`CpScreenResponse`** is an intermediate state used by pages mid-migration: the outer CP shell is rendered via Inertia,
but the inner content is PHP-rendered HTML injected into the page. Controllers returning `CpScreenResponse` are
partially migrated; full migration means converting the inner form to a Vue component and switching to
`Inertia::render()`.

**Packages:**

- `packages/craftcms-ui` — the `@craftcms/ui` component library (Web Components built on Lit/WebAwesome). Imported as
  `@craftcms/ui` in Vue pages. Has its own build (`npm run build:ui`) and Vitest tests (`npm run test:ui`).
- `packages/craftcms-legacy` — webpack-bundled jQuery modules used by legacy CP surfaces.

**TypeScript types** for PHP classes are auto-generated via `spatie/laravel-typescript-transformer` and written to
`resources/js/generated/`. This runs automatically on `vite dev`/`vite build` when relevant PHP files change; run
`./vendor/bin/testbench typescript:transform` manually if needed.

**Wayfinder** generates typed route URL helpers into `resources/js/` from Laravel routes. Regenerate with
`./vendor/bin/testbench wayfinder:generate`.

**Custom elements** (anything with a hyphen in the tag name) are treated as native web components by the Vue compiler —
they pass through to the browser without Vue trying to resolve them as Vue components.

When porting behavior out of the legacy jQuery bundle (`packages/craftcms-legacy/cp/src/js/*.js`) into modern TypeScript, follow the shared module pattern documented in `resources/js/modules/README.md` — a logic class (`<name>.ts` on `@craftcms/garnish` `Base`), a `ControllerElement` custom element (`<name>.ce.ts`), an instance-registry `support.ts` WeakMap, and an `index.ts` shim that registers the element and assigns the legacy `window.Craft.*` global. Note the source-vs-`dist` gotcha in that README: after editing `packages/craftcms-garnish/src` (or `@craftcms/cp`), rebuild the package's `dist` or `npm run typecheck` won't see the change.

## Adapter Work

`yii2-adapter` is compatibility code, not the implementation path for new core behavior. If you need to add adapter classes, follow its Composer autoload mapping. Do not put general adapter classes in `yii2-adapter/lib/`; that area is for vendored or library-style code.

## Pitfalls

- Some files contain Unicode characters in comments and strings. If a text edit fails unexpectedly, inspect the exact bytes or copy the surrounding text from the file.
- PHP 8.2+ does not allow accessing constants on traits directly. Access trait-provided constants through a class that uses the trait.
- `declare(strict_types=1)` is required in PHP files.
- Classes are non-final by default; use `readonly` when it fits.
- You do not need to manually remove unused imports; Pint will fix them.
