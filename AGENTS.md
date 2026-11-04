# Agent guidance

Follow [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

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
commit into the PR's actual target branch if every conflict is inconsequential
and nonfunctional, such as overlapping `CHANGELOG.md` entries. Do not merge the
target branch into the PR or rebase and push the PR merely to resolve these
conflicts and trigger repeated PR CI. Judge the conflicting content, not just
the filename. Ask for confirmation before resolving substantive conflicts,
including code, dependencies, or generated artifacts.

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
   authorization to merge the PR and only genuinely nonfunctional conflicts, an
   ordinary push of the final target-based merge commit may use the credential's
   existing bypass permission solely for the PR-only rule, after the CI
   verification and current-state approval checks above. Never bypass failed or
   pending required checks, fresh merged-result checks, merge queues, or any
   other protection. Do not use an administrative override. If another
   protection prevents this path or compliance is unclear, stop and ask how to
   proceed.
2. Use a clean worktree based on the fetched target commit, then run
   `git merge --no-ff --no-commit <verified-pr-head-sha>`. Resolve only the
   inconsequential conflicts covered above, preserving both sides' intended
   content, including changelog entries and release ordering.
3. Inspect the staged merge result and the complete diff against the target.
   Validate the resolved documentation, check for leftover conflict markers,
   and run `git diff --cached --check`. These lightweight checks validate the
   documentation resolution, not the substantive merged result. Run all
   required tests and merged-result verification for the PR's substantive
   changes; passing or inherited PR CI does not replace required merged-result
   checks.
4. Before committing and pushing, confirm that the PR head is still the
   verified commit and that CI verification and current-state approvals remain
   valid. If the head changed, restart with the latest head and repeat those
   checks. Commit the merge and use an ordinary push to the actual target branch,
   never a force-push. If the target advances before the push succeeds, discard the
   unpublished merge, fetch the latest heads, and repeat from a clean worktree
   based on the latest target, including inspection and required validation.
