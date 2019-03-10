# Running Release Notes for Craft CMS 3.2

### Added
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))

### Changed
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
