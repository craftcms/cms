# Release Notes for Craft CMS 6.0 (WIP)

### Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\Str`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.
