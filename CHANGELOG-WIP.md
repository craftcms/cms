# Release notes for Craft 4.2.0 (WIP)

### Added
- Added the “Notification Duration” user accessibility preference. ([#11612](https://github.com/craftcms/cms/pull/11612))
- The `accessibilityDefaults` config setting now supports a `notificationDuration` key.
- Added `craft\behaviors\SessionBehavior::setSuccess()`.
- Added `craft\events\IndexKeywordsEvent`.
- Added `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS`. ([#11575](https://github.com/craftcms/cms/discussions/11575))

### Changed
- Redesigned user notifications. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Most element notifications now include a link to the element. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Improved condition builder accessibility. ([#11588](https://github.com/craftcms/cms/pull/11588))
- Dashboard widgets no longer show a confirmation dialog when deleted. Their delete notifications include an “Undo” button instead. ([#11573](https://github.com/craftcms/cms/discussions/11573))
- `craft\behaviors\SessionBehavior::setError()` now has a `$settings` argument.
- `craft\behaviors\SessionBehavior::setNotice()` now has a `$settings` argument.
- `craft\web\Controller::asSuccess()` now has a `$notificationSettings` argument.
- `craft\web\Controller::setFailFlash()` now has a `$settings` argument.
- `craft\web\Controller::setSuccessFlash()` now has a `$settings` argument.
