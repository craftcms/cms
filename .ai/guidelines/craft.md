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
- Prefix the title with the origin version branch, such as `[6.x]` or `[5.x]`.
- Use a `### Description` section and add `### Related issues` only when applicable. Do not add validation summaries.
- Copilot's pull request review can arrive as a formal review, or only as ordinary comments on the conversation tab. When waiting for or checking a Copilot review, look at reviews (`gh api repos/craftcms/cms/pulls/<number>/reviews`), inline review comments (`.../pulls/<number>/comments`), and conversation comments (`gh api repos/craftcms/cms/issues/<number>/comments`). Don't conclude Copilot hasn't responded from reviews alone.
- When asked to watch for a Copilot review, schedule checks of all three until it arrives. Don't rely on a GitHub event subscription alone; it doesn't report every Copilot response.
- Read each Copilot review's full body. Findings can appear only there, such as under "previously missed issues", even when the summary says there are no open issues. The summary line can also raise concerns, such as "Needs a closer look" with no open issues; investigate each one like a finding.

- When the branch already identifies a Linear issue, reference the related GitHub issue instead of repeating the Linear identifier.

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

When explicitly instructed to merge a PR, resolve conflicts in the final merge
commit into the PR's actual target branch if every conflict is either:

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
   PR head, every subsequent PR commit ends its body with `[ci skip]`, and
   inspection of every subsequent commit plus the complete cumulative diff from
   the tested commit to the current head confirms genuinely nonfunctional-only
   changes under the eligibility rules above. Required approvals must remain
   satisfied for the current PR state. When using inherited CI, report both the
   tested SHA and current-head SHA; never claim the current head itself passed.
   Check the target's branch protections, required merged-result checks, and
   merge queue requirements before pushing. CI inheritance cannot override
   GitHub's head-check requirements or any other protection. With explicit
   authorization to merge the PR and only conflicts eligible under this policy, an
   ordinary push of the final target-based merge commit may use the credential's
   existing bypass permission solely for the PR-only rule, after the CI
   verification and current-state approval checks above. Never bypass failed or
   pending required checks, fresh merged-result checks, merge queues, or any
   other protection. Do not use an administrative override. If another
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

## Test oracles

- Take a test's expected result from an independent oracle: measure the real output (what's rendered, returned, or stored) rather than recomputing it with the model or simplifications the code itself relies on. An oracle that shares the code's assumptions agrees with it whenever those assumptions are wrong.
- Choose inputs that could make the test fail: extremes, and inputs where the code's assumptions break, such as data that's missing, stale, or doesn't match what it describes.
- Watch every new test go red once: weaken the code it guards, confirm the failure, then restore it. A red browser test leaves a failure screenshot in its `__screenshots__/` folder; delete it once the test passes again.

## Working through tasks

- When a step doesn't need my input, keep going. Put status notes in the same message as your next action. Stop and ask only when you can't continue without me, or before anything destructive.

## Reporting

- End every long run (one that edits files or spans many steps) with three headings: **Blocked** (decisions or approvals you need, or omit if nothing), **Changed** (what you changed), **Found** (issues noticed but not acted on).
- When a run changes UI (any path covered by `.ai/rules/accessibility.md`), add an **Accessibility** heading after **Changed**. Name each section of `.github/instructions/a11y.instructions.md` that applied, say how you verified it (a test name, a keyboard pass, a forced-colors check, a screen reader), and list what you didn't verify so a person can check it by hand.
