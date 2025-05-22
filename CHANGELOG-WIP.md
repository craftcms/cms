# Release Notes for Craft CMS 6.0 (WIP)

### Extensibility
- Added `Craft\Cms\Support\Arr`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `Craft\Cms\Support\Arr` should be used instead.
