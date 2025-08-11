# Release Notes for Craft CMS 6.0 (WIP)

### Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\Str`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.
- Deprecated `craft\enums\Color`. `CraftCms\Cms\Support\Enums\Color` should be used instead.
- Deprecated `craft\enums\AttributeStatus`. `CraftCms\Cms\Element\Enums\AttributeStatus` should be used instead.
- Deprecated `craft\enums\CmsEdition`. `CraftCms\Cms\CmsEdition` should be used instead.
- Deprecated `craft\enums\ElementIndexViewMode`. `CraftCms\Cms\Field\Enums\ElementIndexViewMode` should be used instead.
- Deprecated `craft\enums\LicenseKeyStatus`. `CraftCms\Cms\Support\Enums\LicenseKeyStatus` should be used instead.
- Deprecated `craft\enums\MenuItemType`. `CraftCms\Cms\Element\Enums\MenuItemType` should be used instead.
- Deprecated `craft\enums\PropagationMethod`. `CraftCms\Cms\Element\Enums\PropagationMethod` should be used instead.
- Deprecated `craft\enums\TimePeriod`. `CraftCms\Cms\Support\Enums\TimePeriod` should be used instead.
