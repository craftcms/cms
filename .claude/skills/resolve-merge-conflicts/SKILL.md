---
name: resolve-merge-conflicts
description: Use when the user asks to resolve merge conflicts, fix a conflicted merge/rebase, or `git status` shows unmerged paths (UU/AU/UA/DU/UD/AA) in this repo. Systematic approach for resolving conflicts by understanding each side's intent rather than guessing from diff markers, with heuristics specific to this repo's Yii2-to-Laravel/TypeScript migration.
---

# Resolve Merge Conflicts

The goal is never "make the markers go away" — it's reproducing what both sides were
actually trying to do, on top of each other. Guessing from the `<<<<<<<`/`=======`/`>>>>>>>`
text alone is how conflicts get silently mis-resolved. Read history first, then resolve.

## 1. Triage

```sh
git status --short | grep -E '^(UU|AU|UA|DU|UD|AA|DD)'
cat .git/MERGE_MSG          # which branch is being merged into which
git rev-parse MERGE_HEAD    # the incoming side ("theirs")
```

Work through the conflicted paths one at a time. Don't touch files that aren't listed —
"M"/"A"/"D" entries without a U already auto-merged cleanly.

## 2. Understand each conflicted file before editing it

For every conflicted path, find the merge-base and look at what each side actually did
to it since then — not just the raw conflict hunk, which only shows where the *lines*
collide, not *why*:

```sh
BASE=$(git merge-base HEAD MERGE_HEAD)
git log --oneline "$BASE"..HEAD       -- path/to/file   # our commits touching it
git log --oneline "$BASE"..MERGE_HEAD -- path/to/file   # their commits touching it
git diff "$BASE" HEAD       -- path/to/file
git diff "$BASE" MERGE_HEAD -- path/to/file
```

Read the actual commit(s) (`git show <sha> -- path/to/file`) when the diff isn't
self-explanatory. The commit message usually says exactly why something changed, which
tells you whether it should win, lose, or merge with the other side.

## 3. Classify the conflict, then resolve accordingly

- **Stale value vs. intentional change** — one side edited something the other side
  had already moved past (e.g. a leftover attribute a later commit removed repo-wide,
  or a value bumped for a reason the other side doesn't have). Take the side that
  reflects the more recent/intentional change; drop the stale one. Don't average them.
- **Two independent additions to the same list/spot** — e.g. two branches each adding
  one new import, one new array entry, one new changelog bullet, at the same line.
  These usually aren't exclusive: keep **both**, in whatever order the surrounding list
  already uses (alphabetical, chronological, etc.).
- **Two features touching the same hook** — e.g. two branches each want their own
  logic on the same `@click`/event/lifecycle method. Don't pick one; compose them
  (early-return for one condition, fall through to the other's handler).
- **Modify/delete (`UD`/`DU`)** — one side deleted a file the other side edited.
  Check which side actually deleted it and *why* (`git log --diff-filter=D`) before
  assuming "restore it": in this repo, legacy Yii/jQuery files are frequently deleted
  because they were ported to a Laravel class or a `resources/js/modules/*.ts` module
  (see AGENTS.md). In that case, don't resurrect the old file — port the other side's
  *content change* forward into wherever the code now lives, then keep the delete.
  Confirm the new location actually exists and doesn't already have the fix before
  assuming work is needed.
- **Generated/lockfile-style files** (`composer.lock`, etc.) — resolve `composer.json`
  first, then fix the lock file's conflicting entries to match (add/remove package
  blocks consistent with the resolved `composer.json`); don't try to hand-average JSON
  hashes. Re-validate with `python3 -c "import json; json.load(open('composer.lock'))"`
  (or equivalent) since these files are large and easy to leave subtly malformed.

## 4. Danger zone: structured prose files (CHANGELOG.md and similar)

Large hand-maintained files with repeated similar-looking blocks (version sections,
changelog bullets) are the highest-risk case: git's 3-way merge can find a *coincidentally
identical line* on both sides (e.g. the same bugfix bullet appears verbatim under two
different version headers) and use it as a sync point, silently splicing content from
the wrong section together **without leaving a conflict marker**. A file with zero
`<<<<<<<` markers left is not proof it merged correctly.

After resolving this kind of file:
1. Diff the unchanged tail/head of the result against `git show HEAD:<file>` and
   `git show MERGE_HEAD:<file>` for the regions that should be untouched by either side.
2. Diff each newly-inserted block against the original side it came from
   (`git show MERGE_HEAD:<file> | sed -n '/marker/,/marker/p'`) to confirm it wasn't
   accidentally combined with the other side's content.
3. Check header/section counts aren't duplicated: `grep -oE "^## [^ ]+ [^ ]*" file | sort | uniq -c`.

If something doesn't match byte-for-byte, rebuild that block explicitly rather than
trusting the auto-merged text.

## 5. Verify before finishing

```sh
git grep -n '^<<<<<<<\|^=======$\|^>>>>>>>'   # nothing left, anywhere in the repo
php -l path/to/file.php                        # or the language-appropriate parser/linter
git add <resolved files>
git status --short | grep -E '^(UU|AU|UA|DU|UD|AA|DD)'   # must be empty
```

Run relevant fast checks if available (`php -l`, `python3 -m json.tool`, `vp check`,
`composer validate`) — don't wait for CI to catch a resolution mistake.

## 6. Don't

- Don't `git checkout --ours`/`--theirs` a whole file as a shortcut — it throws away
  one side's intent by construction, exactly what step 2–3 exist to avoid.
- Don't run `git commit` to conclude the merge unless the user asked for that —
  resolving and staging is the deliverable; committing is a separate, explicit step.
- Don't delete/restore files based on which side "looks bigger" — always check
  *why* one side deleted something (step 3) before deciding.
