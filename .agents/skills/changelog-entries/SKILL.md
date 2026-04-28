---
name: changelog-entries
description: >
  Write changelog entries for CHANGELOG.md or CHANGELOG-WIP.md. Use when work has been completed
  and changelog entries need to be added, or when the user asks to write, add, or update changelog
  entries, release notes, or document changes. Triggers on requests like "add changelog entry",
  "update the changelog", "write release notes", or after completing a feature/fix that needs documenting.
---

# Changelog Entries

Write entries that match the established format in the target changelog file.

## Determine Target File

1. If the user specifies a file, use that.
2. If both `CHANGELOG.md` and `CHANGELOG-WIP.md` exist, ask the user which to target.
3. If only one exists, use that file.
4. Read the first ~80 lines of the target file to confirm the current format and find the insertion point.

## CHANGELOG.md Format

Versioned release changelog. Entries go under `## Unreleased` or a version header like `## X.Y.Z - YYYY-MM-DD`.

Each entry is a `- ` prefixed line. No blank lines between entries within a section.

```markdown
## Unreleased

- Added `craft\helpers\SomeHelper::someMethod()`.
- Fixed a bug where something wasn't working properly. ([#12345](https://github.com/craftcms/cms/issues/12345))
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

Work-in-progress changelog for the next major version. Entries organized by domain under `##` headers (e.g., `## Fields`, `## Elements`, `## Auth`). Within a domain, entries may be further grouped under `###`/`####` subheaders like `### Added`, `### Deprecations`, `### Events`.

```markdown
## Fields

- Added `CraftCms\Cms\Field\NewField`.
- Deprecated `craft\fields\OldField`. `CraftCms\Cms\Field\NewField` should be used instead.

### Events

- Deprecated `craft\events\OldEvent`. `CraftCms\Cms\New\Events\NewEvent` should be used instead.
```

Place new entries under the most appropriate existing `##` domain section, or create a new one if none fits. Group entries by type: Added first, then Deprecated, then Removed.

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

5. **Link issues/PRs** at the end when applicable:
   - Issues: `([#12345](https://github.com/craftcms/cms/issues/12345))`
   - PRs: `([#12345](https://github.com/craftcms/cms/pull/12345))`
   - Security: `(GHSA-xxxx-xxxx-xxxx)`
   - External repos: `([craftcms/commerce#4006](https://github.com/craftcms/commerce/issues/4006))`

6. **Security fixes** include severity level: `` Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) RCE vulnerability. (GHSA-xxxx-xxxx-xxxx) ``

7. **Keyboard shortcuts** use `<kbd>` tags: `<kbd>Return</kbd>`.

## Entry Templates

For the full set of templates and examples, see `references/entry-templates.md`.

## Workflow

1. Identify what changed (from conversation context, git diff, or user description).
2. Determine the target file and section/insertion point.
3. Write entries following the format rules above.
4. Insert entries in the appropriate location in the file.
