# Release Notes for Craft CMS 3.1 (WIP)

### Added
- Added the Project Config, a portable and centralized configuration for system settings. ([#1429](https://github.com/craftcms/cms/issues/1429)) 
- Elements, field layouts, sites, and site groups are now soft-deleted. ([#867](https://github.com/craftcms/cms/issues/867))
- Entries, categories, and users can now be restored within the Control Panel by searching for `is:trashed` and clicking the “Restore” button.
- Added cross-domain support for Live Preview. ([#1521](https://github.com/craftcms/cms/issues/1521))
- Custom fields can now opt out of being included in elements’ search keywords. ([#2600](https://github.com/craftcms/cms/issues/2600))
- Added the `disableAdminFunctions` config setting.
- Added the `softDeleteDuration` config setting.
- Added the `useProjectConfigFile` config setting.
- Added the `gc` console command, which can be used to run garbage collection tasks.
- Added the `trashed` element query param, which can be used to query for elements that have been soft-deleted.
- Added the `expression()` Twig function, for creating new `yii\db\Expression` objects in templates. ([#3289](https://github.com/craftcms/cms/pull/3289))
- Added `craft\base\ApplicationTrait::getIsLive()`.
- Added `craft\base\Element::EVENT_AFTER_RESTORE`.
- Added `craft\base\Element::EVENT_BEFORE_RESTORE`.
- Added `craft\base\ElementInterface::afterRestore()`.
- Added `craft\base\ElementInterface::beforeRestore()`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_RESTORE`.
- Added `craft\base\Field::EVENT_BEFORE_ELEMENT_RESTORE`.
- Added `craft\base\FieldInterface::afterElementRestore()`.
- Added `craft\base\FieldInterface::beforeElementRestore()`.
- Added `craft\controllers\LivePreviewController`.
- Added `craft\db\Command::restore()`.
- Added `craft\db\Command::softDelete()`.
- Added `craft\db\Migration::restore()`.
- Added `craft\db\Migration::softDelete()`.
- Added `craft\db\SoftDeleteTrait`, which can be used by Active Record classes that wish to support soft deletes.
- Added `craft\elements\actions\Restore`, which can be included in elements’ `defineActions()` methods to opt into element restoration.
- Added `craft\events\ConfigEvent`.
- Added `craft\helpers\App::editionHandle()`.
- Added `craft\helpers\App::editionIdByHandle()`.
- Added `craft\helpers\App::mailSettings()`.
- Added `craft\helpers\Db::idByUid()`.
- Added `craft\helpers\Db::idsByUids()`.
- Added `craft\helpers\Db::uidById()`.
- Added `craft\helpers\Db::uidsByIds()`.
- Added `craft\helpers\ProjectConfig`.
- Added `craft\models\FieldLayout::createFromConfig()`.
- Added `craft\models\FieldLayout::getConfig()`.
- Added `craft\services\Categories::getGroupByUid()`.
- Added `craft\services\Elements::restoreElement()`.
- Added `craft\services\Elements::EVENT_AFTER_RESTORE_ELEMENT`.
- Added `craft\services\Elements::EVENT_BEFORE_RESTORE_ELEMENT`.
- Added `craft\services\Fields::restoreLayoutById()`.
- Added `craft\services\Gc` for handling garbage collection tasks.
- Added `craft\services\ProjectConfig`.
- Added `craft\services\Sections::getSectionByUid()`.
- Added `craft\services\Sites::restoreSiteById()`.
- Added `craft\web\Controller::requireCpRequest()`.
- Added `craft\web\Controller::requireSiteRequest()`.
- Added the ActiveRecord Soft Delete Extension for Yii2.
- Added the Symfony Yaml Component.

### Changed
- The `defaultWeekStartDay` config setting is now set to `1` (Monday) by default, to conform with the ISO 8601 standard.
- Renamed the `isSystemOn` config setting to `isSystemLive`.
- `info` buttons can now also have a `warning` class.
- User permission definitions can now include `info` and/or `warning` keys.
- The old “Administrate users” permission has been renamed to “Moderate users”.
- The old “Change users’ emails” permission has been renamed to “Administrate users”, and now comes with the ability to activate user accounts and reset their passwords. ([#942](https://github.com/craftcms/cms/issues/942))  
- All users now have the ability to delete their own user accounts. ([#3013](https://github.com/craftcms/cms/issues/3013))
- System user permissions now reference things by their UIDs rather than IDs (e.g. `editEntries:<UID>` rather than `editEntries:<ID>`).
- Animated gif thumbnails are no longer animated. ([#3110](https://github.com/craftcms/cms/issues/3110))
- Token params can now live in either the query string or the POST request body.
- Element types that support Live Preview must now hash the `previewAction` value for `Craft.LivePreview`.
- Live Preview now loads each new preview into its own `<iframe>` element. ([#3366](https://github.com/craftcms/cms/issues/3366))

### Deprecated
- Deprecated `craft\base\ApplicationTrait::getIsSystemOn()`. `getIsLive()` should be used instead.
- Deprecated `craft\models\Info::getEdition()`. `Craft::$app->getEdition()` should be used instead.
- Deprecated `craft\models\Info::getName()`. `Craft::$app->projectConfig->get('system.name')` should be used instead.
- Deprecated `craft\models\Info::getOn()`. `Craft::$app->getIsLive()` should be used instead.
- Deprecated `craft\models\Info::getTimezone()`. `Craft::$app->getTimeZone()` should be used instead.
- Deprecated `craft\services\SystemSettings`. `craft\services\ProjectConfig` should be used instead.

### Security
- It’s no longer possible to spoof Live Preview requests.
