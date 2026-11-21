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
- Don't worry about revealing the `cpTrigger` on the front end when
  `headlessMode` is enabled.

## Permissions

- Every user with the `accessCp` permission has basic read-only access to all
  elements, as that permission's description notes: through element selector
  modals, chips, cards, and other means. Don't flag or add `view` permission
  checks on control panel endpoints that only show element content to them.
  Saving, deleting, and other changes still need the element's own permissions.

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

Only add it when no other unpushed commits on the branch have significant
changes. GitHub skips CI for a whole push when its head commit carries the
marker, so it would also skip the commits beneath it.

Skip markers can leave required checks pending and do not establish green CI.
Never use them to evade required checks, merged-result verification, or merge
queues. The `merging-pull-requests` skill's safeguards still apply.

## Workspace skills

The Craft workspace skills below cover processes shared with the plugin repos.
Follow them, along with the details for this repo.

### Releasing

Follow the `releasing` skill.

- Release from the branch the changes landed on: `5.x`, or `4.x` for 4.x
  releases.
- The version is `version` in `src/config/app.php`. The Finish commit bumps it.
- New minor versions are developed on a next-minor branch, such as `5.13`, with
  its own `CHANGELOG-WIP.md`. `6.x` is the next major version.
- After a release, merge `5.x` into the next-minor branch, then that branch
  into `6.x`.
- Unreleased security fixes are on `5.x-internal` in craftcms/cms-internal.

### Changelog entries

Follow the `changelog-entries` skill.

### Merging pull requests

When explicitly instructed to merge a PR, follow the `merging-pull-requests`
skill. Translation files are in `src/translations/`.

### Copilot reviews

When waiting for, watching for, or checking a Copilot review, follow the
`watching-copilot-reviews` skill.
