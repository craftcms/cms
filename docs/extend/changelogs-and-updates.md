# Changelogs and Updates

When you [publish](plugin-store.md) your plugin in the Plugin Store, you will be able to specify a path to your plugin’s changelog within the repository.

If this is set to a valid changelog path, then each time you release a new update for your plugin, the Plugin Store will re-download your changelog, and use it to display release notes for any available plugin updates on the Utilities → Updates page.

## Setting Up a Changelog

Create a `CHANGELOG.md` file at the root of your plugin’s repo, where you can start documenting release notes for your plugin. Use something like this as a starting point:

```markdown
# Release Notes for <Plugin Name>
```

Within the changelog, releases must be listed in **descending order** (newest-to-oldest). (When displaying available plugin updates, the Plugin Store will stop parsing a plugin’s changelog as soon as it finds a version that is older than or equal to the user’s installed version.)

## Version Headings

Version headings in your changelog must follow this format:

```markdown
## X.Y.Z - YYYY-MM-DD
```

There’s a little wiggle room on that:

- Other text can come before the version number, like the plugin’s name.
- A 4th version number is allowed (e.g. `1.2.3.4`).
- Pre-release versions are allowed (e.g. `1.0.0-alpha.1`).
- The version can start with `v` (e.g. `v1.2.3`).
- The version can be hyperlinked (e.g. `[1.2.3]`).
- Dates can use dots as separators, rather than hyphens (e.g. `2017.01.21`).

Any H2s that don’t follow this format will be ignored, including any content that follows them leading up to the next H2.

## Release Notes

All content that follows a version heading (up to the next H2) will be treated as the release notes for the update.

When writing release notes, we recommend that you follow the guidelines at [keepachangelog.com](https://keepachangelog.com/), but all forms of [GitHub Flavored Markdown](https://guides.github.com/features/mastering-markdown/#GitHub-flavored-markdown) are allowed. The only thing that is *not* allowed is actual HTML code, which will be escaped.

### Tips and Warnings

You can include tips, warnings, and other notes in your release notes using this syntax:

```markdown
> {tip} A helpful tip.

> {warning} A word of warning.

> {note} A serious note.
```

Any updates that contain one of these will be auto-expanded on the Utilities → Updates page.

### Links

If you have any reference-style links in your release notes, you will need to define the URLs *before* the following version heading:

```markdown
## 2.0.1 - 2017-02-01
### Fixed issue [#123]

[#123]: https://github.com/pixelandtonic/foo/issues/123

## 2.0.0 - 2017-01-31
### Added
- New [superFoo] config setting

[superFoo]: https://docs.foo.com/config#superFoo
```

## Critical Updates

If an update contains a fix for a critical security vulnerability or other dangerous bug, you can alert your users about it by adding `[CRITICAL]` to the end of the version heading:

```markdown
## 2.0.1 - 2017-01-21 [CRITICAL]
### Fixed
- Reverted change to `$potus` due to security vulnerabilities
```

When Craft finds out that a critical update is available, it will post a message about it to the top of all Control Panel pages, and give the update special attention on the Utilities → Updates page.
