# AGENTS.md

Guidance for AI coding agents working in this repository.

<laravel-boost-guidelines>
=== .ai/craft rules ===

# Craft CMS

Craft CMS 6 runs on Laravel 13. This repository is a package that boots through Orchestra Testbench, not a conventional Laravel application.

New core work is Laravel-first. The Yii adapter exists only for plugins and integrations that still call Yii-era Craft APIs.

Boost's `search-docs` covers Laravel ecosystem documentation. Use Craft's source and documentation for Craft-specific APIs; detecting `craftcms/cms` does not mean Boost has indexed its documentation.

Boost's tools (`search-docs`, `database-query`, `database-schema`, `get-absolute-url`, and the rest) come from the `laravel-boost` MCP server in `.mcp.json`, which not every agent loads. When they aren't available, use `php artisan` commands, the package source under `vendor/`, and the packages' online documentation instead.

This is a large codebase with some large files. Search narrowly before reading full files.

All UI — PHP view components, Twig templates, Vue components, and the Lit web components in `packages/craftcms-ui` — must conform to WCAG 2.2 Level AA. See `.ai/rules/accessibility.md` for what that means in practice here. Before planning or making any UI change, read `.github/instructions/a11y.instructions.md` in full and apply every section relevant to the change.

## Inferring conventions

- When using `infer-conventions`, treat `src/`, `database/`, `yii2-adapter/`, `packages/`, `resources/js/`, `resources/templates/`, `tests/`, and `yii2-adapter/tests-laravel/` as the application roots. Adapt checklist references to `app/**` to the matching Craft path.
- Scope candidates using the boundaries in `.ai/rules/index.md`. Keep core, adapter, frontend-stack, package, and test-suite conventions separate.

## Commands

- Use `composer fix-cs`, `composer phpstan`, and `composer ci` for repository-wide PHP checks.
- Run JavaScript tooling through pnpm (`pnpm vp test run …`, `pnpm vp run build:ui`). The repo requires pnpm, so `npm` and `npx` fail with `EBADDEVENGINES`.
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
- Load the `changelog-entries` skill for every bug fix or feature PR, and commit the release notes it calls for with the change, linking the GitHub issue it fixes. If the skill says the change gets no entry, such as a fix for behavior that was never released, say why in the PR body.
- Prefix the title with the origin version branch, such as `[6.x]` or `[5.x]`.
- Use the `pr` skill to write the pull request body.
- Use the `requesting-accessibility-reviews` skill to request an accessibility review.

## Screenshots in pull requests

- Take and add every screenshot in a pull request's description or comments by following the `feature-screenshots` skill.
- Host every image on GitHub, as a `https://github.com/user-attachments/assets/…` URL. Never use images hosted on ampcode.com, such as Amp attachment, artifact, or file URLs, or anywhere else.
- When the Claude in Chrome tools aren't available to upload the screenshots yourself, ask the user to:
  1. Download the screenshots.
  2. Drop them into any comment box on GitHub, such as the pull request's, without saving the comment.
  3. Paste the Markdown or HTML that GitHub inserts for each image back into the thread.

  Then add those images to the pull request description, as the `feature-screenshots` skill describes.

## Workspace skills

The Craft workspace skills cover processes shared with Craft 5 and the plugin repos: `releasing`, `changelog-entries`, `merging-pull-requests`, `watching-copilot-reviews`, `requesting-accessibility-reviews`, `feature-screenshots`, and `pr`. Follow them, along with the details for this repo below.

### Releasing

- Release Craft 6 from `6.x`. The version is `VERSION` in `src/Cms.php`, and the Finish commit bumps it.
- `6.x` gets Craft 5 fixes through the next-minor 5.x branch, such as `5.13`. When releasing both majors, finish the 5.x release and merge it forward into `6.x` first.
- Unreleased security fixes are on `6.x-internal` in craftcms/cms-internal.

### Changelog entries

- `CHANGELOG-WIP.md` (`# Release Notes for Craft CMS 6.0 (WIP)`) is for people upgrading from Craft 5. Something first released in a 6.0 alpha or beta is new to them, so later changes to it get no WIP entry.
- Use Craft 6 class names in code references, such as `CraftCms\Cms\Database\Claim` or `CraftCms\Cms\Support\Facades\FacadeName`, and name macros as ``the `Request::isPreview()` macro``.
- When ordering entries, front-end developer changes include Blade, and plugin and module development changes include Laravel and control panel Inertia, Vue and web components.

## Breaking changes in 6.0 pre-releases

From 6.0.0-beta.1 on, don't break anything a tagged 6.0 pre-release shipped. Prefer deprecation (`Deprecator::log()`, a "Deprecated" changelog entry, removal in Craft 7), and ask before any unavoidable break.

## Control panel trigger

Sites can use a custom `cpTrigger` to keep their control panel URL private, so
treat it as sensitive.

- Never reveal the `cpTrigger`, or control panel URLs that contain it, on the
  front end: in site responses, discovery metadata (such as `.well-known/*`),
  response headers, redirects, error messages, or anything else served to
  visitors who haven't already reached the control panel. Use site or action
  URLs instead.
- Never include a real site's `cpTrigger` or control panel URL in pull request
  descriptions, commit messages, changelog entries, issue comments, or other
  public text. Use a placeholder such as `{cpTrigger}`.
- Don't worry about revealing the `cpTrigger` on the front end when
  `headlessMode` is enabled.

## Permissions

- Every user with the `accessCp` permission has basic read-only access to all
  elements, as that permission's description notes: through element selector
  modals, chips, cards, and other means. Don't flag or add `view` permission
  checks on control panel endpoints that only show element content to them.
  Saving, deleting, and other changes still need the element's own permissions.

## CI skip markers

Decide this for every commit before you make it, including commits in a
worktree or on another branch. Once a commit is pushed, the marker can't be
added without rewriting history.

For commits touching only genuinely inconsequential, nonfunctional files, put
`[ci skip]` on its own line at the end of the extended commit message (the
commit body), before trailers such as `Co-Authored-By`. Judge eligibility by
the actual effect, not filenames alone. Code, dependencies, executable
configuration, workflows, and behavior-changing generated artifacts are not
eligible.

Commits that are usually eligible:

- Release notes only: `CHANGELOG.md` and `CHANGELOG-WIP.md`.
- Documentation and code comments only.
- Agent instructions, rules and skills only: `AGENTS.md`, `.ai/`, `.agents/`
  and `.claude/`, including scripts that only agents run.

Only add it when no other unpushed commits on the branch have significant
changes. GitHub skips CI for a whole push when its head commit carries the
marker, so it would also skip the commits beneath it.

Skip markers can leave required checks pending and do not establish green CI.
Never use them to evade required checks, merged-result verification, or merge
queues. The `merging-pull-requests` skill's safeguards still apply.

## Regression tests

- Apply the `test-audit` authoring gate after a bug fix or behavior change. Add a
  regression test only when it closes a genuine behavior-coverage gap, and
  demonstrate that it fails on the pre-fix code for the intended reason.

## Test oracles

- Take a test's expected result from an independent oracle: measure the real output (what's rendered, returned, or stored) rather than recomputing it with the model or simplifications the code itself relies on. An oracle that shares the code's assumptions agrees with it whenever those assumptions are wrong.
- Choose inputs that could make the test fail: extremes, and inputs where the code's assumptions break, such as data that's missing, stale, or doesn't match what it describes.
- Before measuring a state, assert that it's applied: the hover fill is painted, the media query matches, the selector applies. A missing token or a rule that doesn't apply falls back to a default, and a default can pass.
- Watch every new test go red once: weaken the code it guards, confirm the failure, then restore it. A red browser test leaves a failure screenshot in its `__screenshots__/` folder; delete it once the test passes again.

## Working through tasks

- When a step doesn't need my input, keep going. Put status notes and recommendations on open decisions in the same message as your next action, and carry on with the work that doesn't depend on my answer.
- End a turn only when the work is done, when nothing can move without me, or when the blocker is deliberate, such as a protected branch or access I withheld. Don't end it with a summary that announces the next step instead of taking it, with an offer to continue, or with a list of decisions that don't block the rest of the work.
- Treat errors, timeouts, failing tests, and failing tools as problems to diagnose: read the error, fix the command, wait and retry, or use another tool.
- Ask before you push, open a pull request or edit its title or body, post comments or reviews on GitHub, merge, delete remote branches, or discard changes you didn't make, such as with `git reset --hard` or `git checkout -- <path>`.
- Never close or comment on GitHub issues or discussions on your own, including after merging the pull request that fixes them. Do either only when asked to for that issue or discussion.
- Don't ask before running tests, `composer ci`, `composer fix-cs`, `composer phpstan`, Pint, or `pnpm` scripts, installing dependencies from the lockfiles, starting dev servers, or creating local branches and worktrees.

## Reporting

- Write reports for someone who hasn't seen your tool calls or the files you opened. Put the key point first, use full sentences in plain words, and call things by their names in the code or product, not by terms you coined during the work.
- End every long run (one that edits files or spans many steps) with three headings: **Blocked** (decisions or approvals you need, or omit if nothing), **Changed** (what you changed), **Found** (issues noticed but not acted on).
- When a run changes UI (any path covered by `.ai/rules/accessibility.md`), add an **Accessibility** heading after **Changed**. Name each section of `.github/instructions/a11y.instructions.md` that applied, say how you verified it (a test name, a keyboard pass, a forced-colors check, a screen reader), and list what you didn't verify so a person can check it by hand.

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== laravel-octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== pestphp/pest-plugin-agent/core rules ===

## Pest Agent Plugin

`vendor/bin/pest --agent="<code>"` runs a one-off Pest assertion without creating a test file — the fastest way to verify that a change actually works (a route response, a model relationship, a rendered page, a form submission, mail firing, a screenshot, JavaScript errors, and so on).

### ALWAYS load the skill first

Whenever the user asks you to check, verify, confirm, or "make sure" something **works** — and it can be exercised on a route, page, form, model, job, mail, notification, or screenshot — you **MUST** load the **`pest-plugin-agent` skill before doing anything else**. Do not reach for a shell command, a throwaway test file, or manual reasoning first. This includes prompts like "verify the login form works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "is the form working", or any behavioral check after a Blade, Livewire, CSS, or JS change. Load the skill, then follow it exactly.

### NEVER fight shell escaping — use SINGLE outer quotes

Inline the snippet, but wrap it in **single** quotes, not double. Single quotes tell the shell to interpret nothing, so `$variables`, `\App\Models\User`, backticks, and `!` all pass through to PHP literally — **there is nothing to escape.** Use double quotes for PHP string literals inside:

```bash
vendor/bin/pest --agent='$user = \App\Models\User::factory()->create(); visit("/login")->type("email", $user->email)->press("Log in")->assertPathIs("/dashboard");'
```

Double outer quotes are the trap the shell springs on you — `--agent="…$user…"` makes the shell interpolate `$user` to nothing. Never do that, and never hand-escape `\$`.

The one thing single quotes can't contain is a literal single quote (an apostrophe in the PHP). Only then, fall back to a file: **Write** the snippet to a `.php` file (plain body statements — no `<?php`, no `use`, fully qualified class names) and run `vendor/bin/pest --agent="$(cat /path/to/snippet.php)"`. `"$(cat …)"` passes the contents verbatim without re-parsing. The plugin resolves the test suite's `uses`/namespace itself, so the file's location does not matter (a scratch/temp path is fine — it need not live under `tests/`).

### Browser checks require the browser plugin — ask before installing

Whenever the request can only be answered in a real browser — "does login work", "is the page responsive", "screenshot the homepage", "check the mobile layout", "does the button click through", "are there JS/console errors", or any visual/interaction check — the `visit()` browser API is needed. It comes from a **separate** package, `pestphp/pest-plugin-browser`, which is powered by Playwright.

If `visit()` is undefined (or the package is not installed), **do not install it silently — ask the user for permission first**, since it pulls in Node/Playwright dependencies and downloads browser binaries. Explain that the browser check needs it and confirm before running these commands:

```bash
composer require pestphp/pest-plugin-browser --dev   # the browser plugin (needs Node.js)
npm install playwright@latest                         # Playwright driver
npx playwright install                                # download the browser binaries
```

Once the user approves and it's installed, add `tests/Browser/Screenshots` to `.gitignore` so captured screenshots aren't committed. Browser assertions then run through the same `vendor/bin/pest --agent='…'` flow:

```bash
vendor/bin/pest --agent='visit("/login")->type("email", "test@example.com")->type("password", "password")->press("Log in")->assertPathIs("/dashboard");'
vendor/bin/pest --agent='visit("/")->on()->mobile()->screenshot(fullPage: false, filename: "home-mobile");'
```

For full usage — backend examples, browser testing, screenshots, responsive checks, combining frontend and backend assertions, RefreshDatabase guidance, and pitfalls — load the **`pest-plugin-agent` skill**.

</laravel-boost-guidelines>
