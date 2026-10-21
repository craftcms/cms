# Release Notes for Craft CMS 5.12 (WIP)

### Content Management

- Addresses within Addresses fields can now be edited from drafts of their owner elements, by users who can save the drafts but not the canonical owner elements. ([#19881](https://github.com/craftcms/cms/issues/19881))

### Administration

- Custom fields now have “Default Tip” and “Default Warning” settings. ([#19976](https://github.com/craftcms/cms/pull/19976))
- A rate limit is now enforced for `users/login`, `auth/verify-totp`, and `auth/verify-recovery-code` requests.
- Added the `defaultRateLimit` and `defaultRateLimitDuration` config settings, which configure the rate limits for `users/login`, `users/send-password-reset-email`, `auth/verify-totp`, and `auth/verify-recovery-code` requests.

### Development

- The `capitalize`, `lower`, `title`, and `upper` Twig filters now have `language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- Added the `project-config/check` command, which checks project config schema compatibility without applying changes.
- Added the `{% render %}` Twig tag. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `{% render %}` tags and `render()` methods are allowed within the Twig sandbox. ([#19988](https://github.com/craftcms/cms/pull/19988))

### Extensibility

- Addresses now support drafts.
- Element fixtures no longer create revisions by default. Set their `createRevisions` property to `true` to retain the previous behavior. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\base\FieldTrait::$tip`.
- Added `craft\base\FieldTrait::$warning`.
- Added `craft\base\NestedElementTrait::$touchOwnersOnSave`.
- Added `craft\base\Renderable`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveField()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveOwner()`.
- Added `craft\fieldlayoutelements\BaseField::defaultTip()`.
- Added `craft\fieldlayoutelements\BaseField::defaultWarning()`.
- Added `craft\helpers\Template::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\i18n\Locale::languageId()`.
- Added `craft\services\Images::getSupportsBmp()`.
- Added `craft\services\Images::getSupportsJxl()`.
- Added `craft\test\ElementFixtureTrait`. ([#19626](https://github.com/craftcms/cms/pull/19626))
- `Illuminate\Support\Collection` now has a `render()` macro, which replaces `craft\elements\ElementCollection::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `craft\helpers\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\helpers\StringHelper::toLowerCase()`, `::toTitleCase()`, and `::toUpperCase()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- Deprecated `craft\helpers\ElementHelper::renderElements()`. `craft\helpers\Template::render()` should be used instead. ([#19988](https://github.com/craftcms/cms/pull/19988))

### System

- Added support for JPEG XL (`.jxl`) images, when ImageMagick supports it. JPEG XL images can now be uploaded, transformed, and set as a transform’s output format.
- BMP images can now be transformed and edited in the Image Editor.
- MySQL database backups and restores now carry over SSL/TLS settings from the system’s MySQL option files (e.g. `ssl-ca` or `disable-ssl-verify-server-cert`), if the database connection doesn’t have any `PDO::MYSQL_ATTR_SSL_*` attributes set. ([#19366](https://github.com/craftcms/cms/pull/19366))
- MySQL database backups and restores now respect the `PDO::MYSQL_ATTR_SSL_CAPATH` and `PDO::MYSQL_ATTR_SSL_CIPHER` database connection attributes. ([#19366](https://github.com/craftcms/cms/pull/19366))
- Craft can now determine the dimensions of BMP and JPEG XL images on remote volumes without downloading them.
- Removed support for the non-standard `ED256` passkey algorithm. ([#19701](https://github.com/craftcms/cms/pull/19701))
- Updated Axios to 1.20.0. 
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Fixed a bug where entry and address indexes weren’t showing any results if they had a “Field” condition rule set to “is empty”.
- Fixed a bug where saving a deeply-nested element on its own wouldn’t update its owners’ `dateUpdated` timestamps, which could cause new revisions to reuse stale nested content. ([#19594](https://github.com/craftcms/cms/issues/19594))
