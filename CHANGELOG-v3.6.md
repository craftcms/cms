# Running Release Notes for Craft CMS 3.6

## Unreleased

### Changed
- Renamed the `backup` and `restore` commands to `db/backup` and `db/restore`. ([#7023](https://github.com/craftcms/cms/issues/7023))
- `craft\services\Composer::install()` no longer has an `$allowlist` argument.
- Updated Guzzle to 7.x, for environments running PHP 7.2.5 or later, and where the `config.platform.php` value in `composer.json` is at least `7.2.5`. ([#6997](https://github.com/craftcms/cms/issues/6997))
- Updated Composer to 2.0.2.

### Deprecated
- Deprecated the `backup` and `restore` commands.
- Deprecated `craft\web\View::$minifyCss`.
- Deprecated `craft\web\View::$minifyJs`.

### Removed
- Removed Minify and jsmin-php.
- Removed `craft\services\Api::getComposerWhitelist()`.
