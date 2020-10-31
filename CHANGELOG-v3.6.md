# Running Release Notes for Craft CMS 3.6

## Unreleased

### Added
- Added the `users/list-admins` and `users/set-password` commands. ([#7067](https://github.com/craftcms/cms/issues/7067))
- Added `craft\console\Controller::passwordPrompt()`.

### Changed
- Renamed the `backup` and `restore` commands to `db/backup` and `db/restore`. ([#7023](https://github.com/craftcms/cms/issues/7023))
- Relational fields now include all related elements’ titles as search keywords, including disabled elements. ([#7079](https://github.com/craftcms/cms/issues/7079))
- `craft\services\Composer::install()` no longer has an `$allowlist` argument.
- Craft no longer reports PHP deprecation errors.
- Updated Guzzle to 7.x, for environments running PHP 7.2.5 or later, and where the `config.platform.php` value in `composer.json` is at least `7.2.5`. ([#6997](https://github.com/craftcms/cms/issues/6997))
- Updated Composer to 2.0.4.

### Deprecated
- Deprecated the `backup` and `restore` commands.
- Deprecated `craft\services\Composer::$disablePackagist`.
- Deprecated `craft\web\View::$minifyCss`.
- Deprecated `craft\web\View::$minifyJs`.

### Removed
- Removed Minify and jsmin-php.
- Removed `craft\services\Api::getComposerWhitelist()`.
