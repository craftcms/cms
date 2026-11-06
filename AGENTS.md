# AGENTS.md

Guidance for AI coding agents working in this repository.

<laravel-boost-guidelines>
=== .ai/craft rules ===

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
- Use the `pr` skill to write the pull request body.
- Copilot's pull request review can arrive as a formal review, or only as ordinary comments on the conversation tab. When waiting for or checking a Copilot review, look at reviews (`gh api repos/craftcms/cms/pulls/<number>/reviews`), inline review comments (`.../pulls/<number>/comments`), and conversation comments (`gh api repos/craftcms/cms/issues/<number>/comments`). Don't conclude Copilot hasn't responded from reviews alone.
- When asked to watch for a Copilot review, schedule checks of all three until it arrives. Don't rely on a GitHub event subscription alone; it doesn't report every Copilot response.
- Read each Copilot review's full body. Findings can appear only there, such as under "previously missed issues", even when the summary says there are no open issues. The summary line can also raise concerns, such as "Needs a closer look" with no open issues; investigate each one like a finding.

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

Skip markers can leave required checks pending and do not establish green CI.
Never use them to evade required checks, merged-result verification, or merge
queues. The merge safeguards below still apply.

## Merging pull requests

When explicitly instructed to merge a PR, follow the path below. It also applies
when there are no conflicts, such as when GitHub only blocks the merge because
the current head's required checks are missing after `[ci skip]` commits.
Resolve conflicts in the final merge commit into the PR's actual target branch
if every conflict is either:

- Inconsequential and nonfunctional, such as overlapping `CHANGELOG.md` entries.
- A generated build artifact that can be regenerated from merged sources with
  the appropriate documented build script. Regenerate outputs; do not hand-merge
  generated content.

Do not merge the target branch into the PR or rebase and push the PR merely to
resolve these conflicts and trigger repeated PR CI. Judge the conflicting
content, not just the filename. Ask for confirmation before resolving substantive
source or dependency conflicts, or any other conflicts outside these categories.
Eligibility for build artifact regeneration does not make behavior-changing
artifacts eligible for `[ci skip]` or nonfunctional-only CI inheritance.

For this path:

1. Fetch the latest PR head and actual target branch. Confirm that the PR head
   has passed all required CI, or use earlier successful required CI for agent
   pre-merge verification only if the tested commit is an ancestor of the current
   PR head, every subsequent PR commit ends its body with `[ci skip]` (before any
   trailers), and
   inspection of every subsequent commit plus the complete cumulative diff from
   the tested commit to the current head confirms genuinely nonfunctional-only
   changes under the eligibility rules above. Required approvals must remain
   satisfied for the current PR state. When using inherited CI, report both the
   tested SHA and current-head SHA; never claim the current head itself passed.
   Check the target's branch protections, required merged-result checks, and
   merge queue requirements before pushing. When inherited CI is valid under
   these conditions, nothing relevant has changed since the tested commit, so do
   not wait for or trigger a rerun of the head's required checks. With explicit
   authorization to merge the PR and only conflicts eligible under this policy, an
   ordinary push of the final target-based merge commit may use the credential's
   existing bypass permission for the PR-only rule and for the required status
   checks that the inherited CI covers, after the CI verification and
   current-state approval checks above. Never bypass failed required checks,
   required checks that inherited CI doesn't cover, fresh merged-result checks,
   merge queues, or any other protection. Do not use an administrative override,
   such as `gh pr merge --admin`. If another
   protection prevents this path or compliance is unclear, stop and ask how to
   proceed.
2. Use a clean worktree based on the fetched target commit, then run
   `git merge --no-ff --no-commit <verified-pr-head-sha>`. Resolve only the
   eligible conflicts covered above, preserving both sides' intended content,
   including changelog entries and release ordering. For generated build artifact
   conflicts, run the appropriate documented build script on the merged sources.
3. Inspect the staged merge result and the complete diff against the target.
   Validate the resolved documentation and inspect regenerated outputs. Check for
   leftover conflict markers and run `git diff --cached --check`. These lightweight
   checks do not validate the substantive merged result. Run applicable build
   verification and tests for regenerated artifacts, plus all required tests and
   merged-result verification for the PR's substantive changes; passing or
   inherited PR CI does not replace required merged-result checks.
4. Before committing and pushing, confirm that the PR head is still the
   verified commit and that CI verification and current-state approvals remain
   valid. If the head changed, restart with the latest head and repeat those
   checks. Commit the merge and use an ordinary push to the actual target branch,
   never a force-push. If the target advances before the push succeeds, discard the
   unpublished merge, fetch the latest heads, and repeat from a clean worktree
   based on the latest target, including inspection and required validation.

## Regression tests

- Apply the `test-audit` authoring gate after a bug fix or behavior change. Add a
  regression test only when it closes a genuine behavior-coverage gap, and
  demonstrate that it fails on the pre-fix code for the intended reason.

## Working through tasks

- When a step doesn't need my input, keep going. Put status notes in the same message as your next action. Stop and ask only when you can't continue without me, or before anything destructive.

## Reporting

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
