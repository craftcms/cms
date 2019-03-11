# Running Release Notes for Craft CMS 3.2

### Added
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))
- Added the `unique` element query param, which can be used to prevent duplicate elements when querying elements across multiple sites.

### Changed
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
