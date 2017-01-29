# Changelogs and Updates

Craft can parse plugin changelogs to learn about available updates, and include them on the Utilities → Updates page alongside the available CMS updates.

## Setting Up a Changelog

Your changelog should be saved as `CHANGELOG.md` at the root of your plugin’s repo. (That’s not a technical requirement, but putting it there will make it easier to find for people viewing your plugin on GitHub or within their `vendor/` directory.) It should be a Markdown-formatted document that loosely follows the suggestions on [keepachangelog.com].

Within the changelog, releases must be listed in descending order (newest on top). Craft will stop parsing the changelog as soon as it hits a version that is older than or equal to the installed version.

### Version Headings

Version headings in your changelog must follow the general format:

```markdown
## X.Y.Z - YYYY-DD-MM
```

The following deviations are supported as well:

- A 4th version number is allowed (e.g. `1.2.3.4`).
- Pre-release versions are allowed (e.g. `1.0.0-alpha.1`).
- The version can start with `v` (e.g. `v1.2.3`).
- The version can be hyperlinked (e.g. `[1.2.3]`).

If an update should be marked as critical, you can append a `[CRITICAL]` flag to the end of the version heading:

```markdown
## 2.0.1 - 2017-01-21 [CRITICAL]
### Fixed
- Reverted change to `$potus` due to security vulnerabilities
```

Any H2s that don’t follow these rules will be ignored, including any content that follows them leading up to the next H2.

### Release Notes

All content that follows a version heading (up to the next H2) is considered the release notes.

While you should generally follow the release note format suggested by [keepachangelog.com], all forms of [GitHub Flavored Markdown] are allowed. The only thing that is *not* allowed is actual HTML code, which will be escaped.

You can also define **notes** and **tips**, using this syntax:

```markdown
> {note} A word of warning.

> {tip} A helpful tip.
```

If you have any reference-style links in your release notes, you will need to define the URLs *before* the following version heading:

```markdown
## 2.0.1 - 2017-02-01
### Fixed issue [#123]

[#123]: https://github.com/pixelandtonic/foo/issues/123

## 2.0.0 - 2017-01-31
### Added
- New [`superFoo` config setting][superFoo]

[superFoo]: https://docs.foo.com/config#superFoo
```

## Configuring your Plugin

Once your plugin’s changelog is available publicly somewhere (like GitHub), you can configure your plugin with its URL by setting its `$changelogUrl` property.

> {note} The URL must begin with `https://`.

While you’re at it, you may want to set a public download URL as well, for anyone who didn’t install your plugin via Composer. That can be done with the `$downloadUrl` property.

```php
class Plugin extends \craft\base\Plugin
{
    public $changelogUrl = 'https://raw.githubusercontent.com/pixelandtonic/foo/master/CHANGELOG.md';
    public $downloadUrl = 'https://github.com/pixelandtonic/foo/archive/master.zip';

    // ...
}
```

If everything is set up correctly, your plugins’ available updates should start appearing on the Utilities → Updates page.

<img src="assets/plugin-update.png" width="1060" alt="The Utilities → Updates page in Craft’s Control Panel, with an available update for the “Foo” plugin.">


[keepachangelog.com]: http://keepachangelog.com/
[GitHub Flavored Markdown]: https://guides.github.com/features/mastering-markdown/#GitHub-flavored-markdown
