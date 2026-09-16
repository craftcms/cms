---
name: changelog-entries
description: >
  Write changelog entries for CHANGELOG.md. Use when work has been completed
  and changelog entries need to be added, or when the user asks to write, add, or update changelog
  entries, release notes, or document changes. Triggers on requests like "add changelog entry",
  "update the changelog", "write release notes", or after completing a feature/fix that needs documenting.
---

# Changelog Entries

Write entries that match the established format in the target changelog file.

## Determine Target File

1. If the user specifies a file, use that, otherwise use `CHANGELOG.md`.
2. Read the first ~80 lines of the target file to confirm the current format and find the insertion point.

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
2. Determine the target file and section/insertion point.
3. Write entries following the format and writing rules above.
4. Insert entries into the section, positioned per the Entry Ordering rules above.
