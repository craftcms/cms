# Craft CMS

Craft CMS 6 runs on Laravel 13. This repository is a package that boots through Orchestra Testbench, not a conventional Laravel application.

New core work is Laravel-first. The Yii adapter exists only for plugins and integrations that still call Yii-era Craft APIs.

Boost's `search-docs` covers Laravel ecosystem documentation. Use Craft's source and documentation for Craft-specific APIs; detecting `craftcms/cms` does not mean Boost has indexed its documentation.

This is a large codebase with some large files. Search narrowly before reading full files.

All UI — PHP view components, Twig templates, Vue components, and the Lit web components in `packages/craftcms-ui` — must conform to WCAG 2.2 Level AA. See `.ai/rules/accessibility.md` for what that means in practice here. Before planning or making any UI change, read `.github/instructions/a11y.instructions.md` in full and apply every section relevant to the change.

## Inferring conventions

- When using `infer-conventions`, treat `src/`, `database/`, `yii2-adapter/`, `packages/`, `resources/js/`, `resources/templates/`, `tests/`, and `yii2-adapter/tests-laravel/` as the application roots. Adapt checklist references to `app/**` to the matching Craft path.
- Scope candidates using the boundaries in `.ai/rules/index.md`. Keep core, adapter, frontend-stack, package, and test-suite conventions separate.

## Commands

- Use `composer fix-cs`, `composer phpstan`, and `composer ci` for repository-wide PHP checks.
- Pass `--no-interaction` to Artisan commands.

## Routes

- For links to registered Laravel routes, use named routes and `route()` instead of hard-coded paths.

## PHP

- Add `declare(strict_types=1)` to PHP files.
- Access trait-provided constants through a class that uses the trait; PHP 8.2 and later do not support accessing them on the trait itself.
- Classes are non-final by default. Use `readonly` when it fits.
- Let Pint remove unused imports.
- Never call `json_encode()` or `json_decode()` directly; use `CraftCms\Cms\Support\Json`.
- Never import `Illuminate\Support\Env`; use `CraftCms\Cms\Support\Env`. In `src/`, also use `CraftCms\Cms\Support\Arr`, `Str`, and `File` instead of `Illuminate\Support\Arr`, `Illuminate\Support\Str`, and `Illuminate\Support\Facades\File`. Each Craft helper extends its Laravel equivalent.

Some files contain Unicode characters in comments and strings. If a text edit fails, inspect the exact bytes or copy the surrounding text from the file.

## Pull requests

- Run `composer ci` before creating a pull request.
- Prefix the title with the origin version branch, such as `[6.x]` or `[5.x]`.
- Use a `### Description` section and add `### Related issues` only when applicable. Do not add validation summaries.

- When the branch already identifies a Linear issue, reference the related GitHub issue instead of repeating the Linear identifier.

## Regression tests

- Apply the `test-audit` authoring gate after a bug fix or behavior change. Add a
  regression test only when it closes a genuine behavior-coverage gap, and
  demonstrate that it fails on the pre-fix code for the intended reason.

## Test oracles

- Take a test's expected result from an independent oracle: measure the real output (what's rendered, returned, or stored) rather than recomputing it with the model or simplifications the code itself relies on. An oracle that shares the code's assumptions agrees with it whenever those assumptions are wrong.
- Choose inputs that could make the test fail: extremes, and inputs where the code's assumptions break, such as data that's missing, stale, or doesn't match what it describes.
- Watch every new test go red once: weaken the code it guards, confirm the failure, then restore it.

## Working through tasks

- When a step doesn't need my input, keep going. Put status notes in the same message as your next action. Stop and ask only when you can't continue without me, or before anything destructive.

## Reporting

- End every long run (one that edits files or spans many steps) with three headings: **Blocked** (decisions or approvals you need, or omit if nothing), **Changed** (what you changed), **Found** (issues noticed but not acted on).
- When a run changes UI (any path covered by `.ai/rules/accessibility.md`), add an **Accessibility** heading after **Changed**. Name each section of `.github/instructions/a11y.instructions.md` that applied, say how you verified it (a test name, a keyboard pass, a forced-colors check, a screen reader), and list what you didn't verify so a person can check it by hand.
