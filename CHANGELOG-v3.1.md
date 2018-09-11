# Release Notes for Craft CMS 3.1 (WIP)

### Added
- Added the Project Config, a portable and centralized configuration for system settings. ([#881](https://github.com/craftcms/cms/issues/881)) 
- Elements, field layouts, sites, and site groups are now soft-deleted. ([#867](https://github.com/craftcms/cms/issues/867))
- Entries, categories, and users can now be restored within the Control Panel by searching for `is:trashed` and clicking the “Restore” button.
- Custom fields can now opt out of being included in elements’ search keywords. ([#2600](https://github.com/craftcms/cms/issues/2600))
- Added the `softDeleteDuration` config setting.
- Added the `gc` console command, which can be used to run garbage collection tasks.
- Added the `trashed` element query param, which can be used to query for elements that have been soft-deleted.
- Added `craft\base\Element::EVENT_AFTER_RESTORE`.
- Added `craft\base\Element::EVENT_BEFORE_RESTORE`.
- Added `craft\base\ElementInterface::afterRestore()`.
- Added `craft\base\ElementInterface::beforeRestore()`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_RESTORE`.
- Added `craft\base\Field::EVENT_BEFORE_ELEMENT_RESTORE`.
- Added `craft\base\FieldInterface::afterElementRestore()`.
- Added `craft\base\FieldInterface::beforeElementRestore()`.
- Added `craft\db\Command::restore()`.
- Added `craft\db\Command::softDelete()`.
- Added `craft\db\Migration::restore()`.
- Added `craft\db\Migration::softDelete()`.
- Added `craft\db\SoftDeleteTrait`, which can be used by Active Record classes that wish to support soft deletes.
- Added `craft\elements\actions\Restore`, which can be included in elements’ `defineActions()` methods to opt into element restoration.
- Added `craft\events\ConfigEvent`.
- Added `craft\helpers\Db::idByUid()`.
- Added `craft\helpers\Db::idsByUids()`.
- Added `craft\helpers\Db::uidById()`.
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
- Added the ActiveRecord Soft Delete Extension for Yii2.
- Added the Symfony Yaml Component.

### Changed
- The `defaultWeekStartDay` config setting is now set to `1` (Monday) by default, to conform with the ISO 8601 standard.
- `info` buttons can now also have a `warning` class.
- User permission definitions can now include `info` and/or `warning` keys.
- The old “Administrate users” permission has been renamed to “Moderate users”.
- The old “Change users’ emails” permission has been renamed to “Administrate users”, and now comes with the ability to activate user accounts and reset their passwords. ([#942](https://github.com/craftcms/cms/issues/942))  
- All users now have the ability to delete their own user accounts. ([#3013](https://github.com/craftcms/cms/issues/3013))
- System user permissions now reference things by their UIDs rather than IDs (e.g. `editEntries:<UID>` rather than `editEntries:<ID>`). 

### Deprecated

- Deprecated `craft\services\SystemSettings`. `craft\services\ProjectConfig` should be used instead.
