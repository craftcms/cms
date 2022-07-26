# Release notes for Craft 4.2.0 (WIP)

### Added
- Added the “Notification Duration” user accessibility preference. ([#11612](https://github.com/craftcms/cms/pull/11612))
- The `accessibilityDefaults` config setting now supports a `notificationDuration` key.
- Added `craft\behaviors\SessionBehavior::getSuccess()`.
- Added `craft\behaviors\SessionBehavior::setSuccess()`.
- Added `craft\config\BaseConfig`. ([#11591](https://github.com/craftcms/cms/pull/11591), [#11656](https://github.com/craftcms/cms/pull/11656))
- Added `craft\controllers\UsersController::EVENT_AFTER_FIND_LOGIN_USER`. ([#11645](https://github.com/craftcms/cms/pull/11645))
- Added `craft\controllers\UsersController::EVENT_BEFORE_FIND_LOGIN_USER`. ([#11645](https://github.com/craftcms/cms/pull/11645))
- Added `craft\events\DefineFieldLayoutCustomFieldsEvent`.
- Added `craft\events\FindLoginUserEvent`.
- Added `craft\events\IndexKeywordsEvent`.
- Added `craft\helpers\DateTimeHelper::humanDuration()`.
- Added `craft\models\FieldLayout::EVENT_DEFINE_CUSTOM_FIELDS`. ([#11634](https://github.com/craftcms/cms/discussions/11634))
- Added `craft\services\Config::getLoadingConfigFile()`.
- Added `craft\services\Elements::EVENT_INVALIDATE_CACHES`. ([#11617](https://github.com/craftcms/cms/pull/11617))
- Added `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS`. ([#11575](https://github.com/craftcms/cms/discussions/11575))

### Changed
- Redesigned user notifications. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Most element notifications now include a link to the element. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Improved overall control panel accessibility. ([#11563](https://github.com/craftcms/cms/pull/11563), [#11543](https://github.com/craftcms/cms/pull/11543), [#11688](https://github.com/craftcms/cms/pull/11688), [#11699](https://github.com/craftcms/cms/pull/11699))
- Improved condition builder accessibility. ([#11588](https://github.com/craftcms/cms/pull/11588), [#11643](https://github.com/craftcms/cms/pull/11643))
- Improved Image Editor accessibility. ([#11496](https://github.com/craftcms/cms/pull/11496))
- The “Keep me signed in” checkbox label on the control panel’s login page now includes the remembered session duration, e.g. “Keep me signed in for 2 weeks”. ([#11594](https://github.com/craftcms/cms/discussions/11594))
- Dashboard widgets no longer show a confirmation dialog when deleted. Their delete notifications include an “Undo” button instead. ([#11573](https://github.com/craftcms/cms/discussions/11573))
- Improved the behavior of some console commands for non-interactive shells. ([#11650](https://github.com/craftcms/cms/issues/11650))
- `config/general.php` and `config/db.php` can now return `craft\config\GeneralConfig`/`DbConfig` objects, which can be defined using new fluent setter methods. ([#11591](https://github.com/craftcms/cms/pull/11591), [#11656](https://github.com/craftcms/cms/pull/11656))
- The `|duration` Twig filter can now be used with an integer representing a number of seconds, and its `showSeconds` argument is no longer required. Seconds will be output if the duration is less than one minute by default.
- The `|length` Twig filter now checks if the variable is a query, and if so, returns its count. ([#11625](https://github.com/craftcms/cms/discussions/11625))
- The `|without` Twig filter no longer uses strict value comparisons by default. It has a new `$strict` argument that can be set to `true` to enforce strict comparisons if desired. ([#11695](https://github.com/craftcms/cms/issues/11695))
- `craft\behaviors\SessionBehavior::setError()` now has a `$settings` argument.
- `craft\behaviors\SessionBehavior::setNotice()` now has a `$settings` argument.
- `craft\helpers\ArrayHelper::removeValue()` no longer uses strict value comparisons by default. It has a new `$strict` argument that can be set to `true` to enforce strict comparisons if desired.
- `craft\web\Controller::asSuccess()` now has a `$notificationSettings` argument.
- `craft\web\Controller::setFailFlash()` now has a `$settings` argument.
- `craft\web\Controller::setSuccessFlash()` now has a `$settings` argument.
- `craft\db\Query` now implements the `ArrayAccess` and `IteratorAggregate` interfaces, so queries (including element queries) can be treated as arrays.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::humanDurationFromInterval()`. `humanDuration()` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper::secondsToHumanTimeDuration()`. `humanDuration()` should be used instead.
