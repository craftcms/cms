---
name: changelog-entries
description: >
  Write changelog entries for CHANGELOG.md, and for CHANGELOG-WIP.md when it exists and the change matters to
  people upgrading from the previous major/minor version. Use when work has been completed
  and changelog entries need to be added, or when the user asks to write, add, or update changelog
  entries, release notes, or document changes. Triggers on requests like "add changelog entry",
  "update the changelog", "write release notes", or after completing a feature/fix that needs documenting.
---

# Changelog Entries

Write entries that match the established format in the target changelog file.

## Skip Fixes for Unreleased Changes

Release notes describe changes from one release to the next, so a fix for behavior that was never in a release gets no entry in either file. That covers a bug introduced since the last tagged release, counting alpha and beta pre-releases as releases, and a bug in a feature or change that's still under `## Unreleased`. Nobody upgrading has seen the broken behavior, so there's nothing to report.

To check, find the commit that introduced the bug and test whether the latest tag contains it:

```sh
git describe --tags --abbrev=0                                   # the latest tag reachable from this branch
git merge-base --is-ancestor <introducing-commit> <latest-tag> && echo released
```

If it isn't released, don't add an entry. If the fix changes how an unreleased feature works, update that feature's existing entry instead, when it no longer describes the feature accurately. When it's unclear whether the behavior shipped, ask rather than adding an entry.

## Determine Target Files

1. If the user specifies a file, use that, otherwise use `CHANGELOG.md`.
2. If a `CHANGELOG-WIP.md` file exists, also update it only when the change passes the test in [CHANGELOG-WIP.md Format](#changelog-wipmd-format): it must matter to someone upgrading from the version before the one named in the file's h1. Most changes don't, including anything that changes or extends a feature that's new in this release. When a change passes the test, update the file even if the user only mentions `CHANGELOG.md`.
3. Read the first ~80 lines of each target file to confirm its current format and find the insertion point.

## CHANGELOG.md Format

Versioned release changelog. Entries go under `## Unreleased` or a version header like `## X.Y.Z - YYYY-MM-DD`.

Each entry is a `- ` prefixed line. No blank lines between entries within a section.

```markdown
## Unreleased

- Added `craft\helpers\SomeHelper::someMethod()`.
- Fixed a bug where something wasn't working properly. ([#12345](https://github.com/craftcms/cms/pull/12345))
- Deprecated `craft\old\Thing`. `craft\new\Thing` should be used instead.
```

**Patch releases** use a flat list with no subheaders.

**Major/minor releases** may group entries under `###` subheaders:
`### Content Management`, `### Accessibility`, `### Administration`, `### Development`, `### Extensibility`, `### System`

**Warnings** go above the entry list using GitHub callout syntax:
```markdown
> [!WARNING]
> Important note about breaking changes.
```

## CHANGELOG-WIP.md Format

`CHANGELOG-WIP.md` is **not** a second copy of `CHANGELOG.md`. It holds the release notes for an upcoming major or minor release as a whole, written for **one audience: people upgrading from the previous major or minor version**. Most changes made while that release is in development don't belong in it.

### Who it's for

Read the h1 at the top of the file. It names the release, and the audience is whoever is coming from the version before it:

- `# Release Notes for Craft CMS 6.0 (WIP)` → people upgrading from **Craft 5**.
- `# Release Notes for Craft CMS 5.9 (WIP)` → people upgrading from **Craft 5.8**.

Use the h1, not the branch name or the latest tag, to decide this.

### The test

Before touching `CHANGELOG-WIP.md`, ask: **would someone upgrading from the previous version, who has never run any alpha, beta, or RC of this release, see a difference from what they have today?** Compare against the previous version only, never against earlier pre-releases of this one.

Add or change a WIP entry only when the change:

- adds something that doesn't exist in the previous version, and isn't part of a feature that already has a WIP entry, or
- changes, deprecates, or removes something that exists in the previous version and that users, developers, or plugins rely on.

### Never add a WIP entry for changes to things that are new in this release

If the thing being changed or extended didn't exist in the previous version, the change does **not** get its own WIP entry, even when it's a whole new capability with its own `CHANGELOG.md` entry. To the upgrader, the feature is simply new, and its existing WIP entry already covers it. This includes:

- New functionality, options, checks, or integrations added to a feature introduced in this release.
- Fixes, refinements, and behavior changes to such a feature.
- Anything first released in an alpha, beta, or RC of this release.

For example, the Yii2 Loose Ends utility is new in Craft 6.0 (it shipped in 6.0.0-beta.1). A later change that makes it report Craft 5 Yii cache components gets a `CHANGELOG.md` entry, but `CHANGELOG-WIP.md` must not be touched: someone coming from Craft 5 never had the utility, so to them it's all part of "Added the Yii2 Loose Ends utility."

The only time such a change touches `CHANGELOG-WIP.md` is when the feature's existing WIP entry now says something false or misleading. Then edit that entry so it stays accurate. Don't append a new entry, and don't expand the existing one just to mention the new capability.

When it's unclear whether something existed in the previous version, check that version's source or ask, rather than adding an entry.

### Format

Entries are grouped by area under the file's `###` and `####` headings (e.g. `### Assets`, `#### AI`, `### Extensibility`), not by version. Add each entry under the heading that fits, following the same writing rules and ordering as `CHANGELOG.md`. Describe the end state relative to the previous version, rather than the steps it took during development.

## Entry Ordering

Within a section (`## Unreleased` or a version header, and within each `###` subheader if the release uses them), order entries highest-priority-first:

1. Blockquote notes ([GitHub callout syntax](https://github.com/orgs/community/discussions/16925): `[!NOTE]`, `[!TIP]`, `[!IMPORTANT]`, `[!WARNING]`, `[!CAUTION]`) — these sit above the `- ` entry list itself, not interleaved with it.
2. Changes affecting a wide range of users.
3. Changes affecting most control panel users.
4. Changes affecting administrators.
5. Changes affecting front-end developers (Twig, Blade, etc.).
6. Changes affecting plugin/module development (PHP, Laravel, control panel Inertia/Vue/web components, etc.).
7. Bug fixes.
8. Security fixes, ordered by severity, high to low.

Within each of those tiers, order entries by popularity/impact, highest first, judging by:
- The referenced GitHub issue/discussion's apparent popularity (reactions, comments, how long-standing or frequently-requested it is).
- Whether it's new functionality (ranks higher) versus a minor tweak.
- How niche the change sounds — broadly-applicable changes outrank edge-case ones.

## Writing Rules

1. **Start with a past-tense verb** — capitalize it:
   - `Added` — new classes, methods, features, settings
   - `Fixed` — bug fixes: `Fixed a bug where...` or `Fixed an error that...`
   - `Deprecated` — include replacement: `` `old\Thing`. `new\Thing` should be used instead. ``
   - `Removed` — removed classes/features (with replacement if applicable)
   - `Improved` — performance or UX improvements
   - Other verbs as appropriate: `Updated`, `Renamed`, `Moved`, `Replaced`

2. **Backtick all code references** — class names, methods, properties, constants, config keys, Twig variables, CLI commands. Use fully qualified class names (no leading backslash).

3. **One entry per line** — don't wrap. Each `- ` entry is a single line regardless of length.

4. **End entries with a period.**

5. **Link issues/PRs** at the end when applicable, prefer PRs when there is one:
   - PRs: `([#12345](https://github.com/craftcms/cms/pull/12345))`
   - Issues: `([#12345](https://github.com/craftcms/cms/issues/12345))`
   - Security: `(GHSA-xxxx-xxxx-xxxx)`
   - External repos: `([craftcms/commerce#4006](https://github.com/craftcms/commerce/issues/4006))`

6. **Security fixes** include severity level: `` Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) RCE vulnerability. (GHSA-xxxx-xxxx-xxxx) ``

7. **Keyboard shortcuts** use `<kbd>` tags: `<kbd>Return</kbd>`.

## Entry Templates

For the full set of templates and examples, see `references/entry-templates.md`.

## Workflow

1. Identify what changed (from conversation context, git diff, or user description).
   Leave out fixes for behavior that was never released (see [Skip Fixes for Unreleased Changes](#skip-fixes-for-unreleased-changes)).
2. Determine the target files and each one's section/insertion point. That's `CHANGELOG.md`, plus `CHANGELOG-WIP.md` only when it exists and the change passes its test. Read the WIP file's h1, then check whether the thing being changed existed in that previous version. If it's new in this release, leave `CHANGELOG-WIP.md` alone unless its existing entry is now inaccurate.
3. Write entries following the format and writing rules above.
4. Insert entries into the section, positioned per the Entry Ordering rules above.

Done when: each user-facing change in the diff has exactly one entry in `CHANGELOG.md`, except fixes for behavior that was never released, which have none, and, when `CHANGELOG-WIP.md` exists, each change that someone upgrading from the version before the one in its h1 would notice is covered there too, with no WIP entries added for changes to things that are new in this release, all in the right sections and order, with existing entries unchanged except where they no longer describe the change accurately.
