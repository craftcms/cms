# Release Notes for Craft CMS 5.12 (WIP)

### Development

- The `capitalize`, `lower`, `title`, and `upper` Twig filters now have `language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- Added the `project-config/check` command, which checks project config schema compatibility without applying changes.

### Extensibility

- Element fixtures no longer create revisions by default. Set their `createRevisions` property to `true` to retain the previous behavior. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\base\NestedElementTrait::$touchOwnersOnSave`.
- Added `craft\i18n\Locale::languageId()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveField()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveOwner()`.
- Added `craft\test\ElementFixtureTrait`. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\services\Images::getSupportsBmp()`.
- Added `craft\services\Images::getSupportsJxl()`.
- `craft\helpers\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\helpers\StringHelper::toLowerCase()`, `::toTitleCase()`, and `::toUpperCase()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))

### System

- Added support for JPEG XL (`.jxl`) images, when ImageMagick supports it. JPEG XL images can now be uploaded, transformed, and set as a transform’s output format.
- BMP images can now be transformed and edited in the Image Editor.
- Craft can now determine the dimensions of BMP and JPEG XL images on remote volumes without downloading them.
- Removed support for the non-standard `ED256` passkey algorithm. ([#19701](https://github.com/craftcms/cms/pull/19701))
- Updated Axios to 1.20.0. 
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Fixed a bug where entry and address indexes weren’t showing any results if they had a “Field” condition rule set to “is empty”.
- Fixed a bug where saving a deeply-nested element on its own wouldn’t update its owners’ `dateUpdated` timestamps, which could cause new revisions to reuse stale nested content. ([#19594](https://github.com/craftcms/cms/issues/19594))
