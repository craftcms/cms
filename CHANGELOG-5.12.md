# Release Notes for Craft CMS 5.12 (WIP)

### Development

- The `capitalize`, `lower`, `title`, and `upper` Twig filters now have `language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))

### Extensibility

- Added `craft\i18n\Locale::languageId()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveField()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveOwner()`.
- `craft\helpers\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\helpers\StringHelper::toLowerCase()`, `::toTitleCase()`, and `::toUpperCase()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))

### System

- Fixed a bug where entry and address indexes weren’t showing any results if they had a “Field” condition rule set to “is empty”.
