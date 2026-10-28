---
name: changelog-entries
description: >
  Write changelog entries for CHANGELOG.md, and CHANGELOG-WIP.md when it exists. Use when work has been completed
  and changelog entries need to be added, or when the user asks to write, add, or update changelog
  entries, release notes, or document changes. Triggers on requests like "add changelog entry",
  "update the changelog", "write release notes", or after completing a feature/fix that needs documenting.
---

# Changelog Entries

Write entries that match the established format in the target changelog file.

## Determine Target Files

1. If the user specifies a file, use that, otherwise use `CHANGELOG.md`.
2. If a `CHANGELOG-WIP.md` file exists, also update it whenever a change will be relevant to someone upgrading from the previous version (see [CHANGELOG-WIP.md Format](#changelog-wipmd-format)), even if the user only mentions `CHANGELOG.md`.
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

`CHANGELOG-WIP.md` collects the release notes for an upcoming major or minor release while it's in development, such as `# Release Notes for Craft CMS 6.0 (WIP)`. When it exists, it must always be updated alongside `CHANGELOG.md` for any change that will be relevant to someone upgrading from the previous version.

Which version that is depends on the current branch, or the target branch of the pull request:

- **`<new version>.x`** (e.g. `6.x` while 6.0 is in development): the previous **major** version. Anything someone upgrading from Craft 5 would notice or need to act on belongs in it.
- **`<version>.<new minor version>`** (e.g. `5.9`): the previous **minor** version. Anything someone upgrading from 5.8 would notice or need to act on belongs in it.

A change is relevant when it adds something that isn't in the previous version, or changes, deprecates, or removes something users, developers, or plugins rely on in it. Changes that only refine or fix something that's new in this release don't need their own WIP entry, since there's nothing to compare them to in the previous version. Instead, update the existing WIP entry for that feature if it no longer describes it accurately.

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
2. Determine the target files (`CHANGELOG.md`, plus `CHANGELOG-WIP.md` when it exists and the change is relevant to someone upgrading from the previous version) and each one's section/insertion point.
3. Write entries following the format and writing rules above.
4. Insert entries into the section, positioned per the Entry Ordering rules above.

Done when: each user-facing change in the diff has exactly one entry in `CHANGELOG.md`, and, when `CHANGELOG-WIP.md` exists, each change relevant to someone upgrading from the previous version is covered there too, all in the right sections and order, with existing entries unchanged except where they no longer describe the change accurately.
