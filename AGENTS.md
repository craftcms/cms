# Agent guidance

Follow [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

## Translations

- Whenever translatable strings in the `app` category are added, modified, or
  removed, always update the English source translations in
  `src/translations/en/app.php` accordingly.
- English source translations for the `commerce` category belong in Commerce's
  `src/translations/en/commerce.php`. Commerce may use existing `app`-category
  translations from Craft CMS, but must not modify those translations or their
  catalog.

## Migrations

- Only add new migrations when working on a new major or minor release (such
  as 5.12.0 or 6.0.0). Never add them for a patch release (such as 5.11.3). If
  a human asks for one in a patch release, tell them that's not our policy and
  add it only after they confirm.
- Make every schema change twice: in a new migration in `src/migrations/`, and
  in the `Install` migration (`src/migrations/Install.php`), so fresh installs
  get the same schema as upgraded ones.
- Reference tables with the `craft\db\Table` constants wherever possible,
  rather than hardcoded table names.
- Bump `schemaVersion` in `src/config/app.php` with each new migration. Its
  format is `<X.Y.Z>.<n>`, where `X.Y.Z` is the target release version and `n`
  starts at `0` for the first new migration targeting that release and
  increments with each one after it (e.g. `5.12.0.0`, then `5.12.0.1`).

## CI skip markers

For commits touching only genuinely inconsequential, nonfunctional files, put
`[ci skip]` at the end of the extended commit message (the commit body). Judge
eligibility by the actual effect, not filenames alone. Code, dependencies,
executable configuration, workflows, and behavior-changing generated artifacts
are not eligible.

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
