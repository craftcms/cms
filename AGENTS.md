# Agent guidance

Follow [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance.

## Merging pull requests

When explicitly instructed to merge a PR, resolve conflicts in the final merge
commit into the PR's actual target branch if every conflict is inconsequential
and nonfunctional, such as overlapping `CHANGELOG.md` entries. Do not merge the
target branch into the PR or rebase and push the PR merely to resolve these
conflicts and trigger repeated PR CI. Judge the conflicting content, not just
the filename. Ask for confirmation before resolving substantive conflicts,
including code, dependencies, or generated artifacts.

For this path:

1. Fetch the latest PR head and actual target branch. Confirm that the exact PR
   head commit has passed all required CI and has the required approvals. Check
   the target's branch protections, required merged-result checks, and merge
   queue requirements. Do not bypass them or use an administrative override.
   If they prevent this direct merge path, stop and ask how to proceed.
2. Use a clean worktree based on the fetched target commit, then run
   `git merge --no-ff --no-commit <verified-pr-head-sha>`. Resolve only the
   inconsequential conflicts covered above, preserving both sides' intended
   content, including changelog entries and release ordering.
3. Inspect the staged merge result and the complete diff against the target.
   Validate the resolved documentation, check for leftover conflict markers,
   and run `git diff --cached --check`. These lightweight checks validate the
   documentation resolution, not the substantive merged result. Run all
   required tests and merged-result verification for the PR's substantive
   changes; passing PR-head CI does not replace required merged-result checks.
4. Before committing and pushing, confirm that the PR head is still the
   verified commit and that required CI and approvals remain valid. If the
   head changed, restart with the latest head and repeat those checks. Commit
   the merge and use an ordinary push to the actual target branch, never a
   force-push. If the target advances before the push succeeds, discard the
   unpublished merge, fetch the latest heads, and repeat from a clean worktree
   based on the latest target, including inspection and required validation.
