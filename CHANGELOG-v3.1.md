# Release Notes for Craft CMS 3.1 (WIP)

### Added
- Elements, field layouts, and sites are now soft-deleted.
- Custom fields can now opt out of being included in elements’ search keywords. ([#2600](https://github.com/craftcms/cms/issues/2600))
- Added the `softDeleteDuration` config setting.
- Added the `gc` console command, which can be used to run garbage collection tasks.
- Added `craft\db\Command::softDelete()`.
- Added `craft\db\Migration::softDelete()`.
- Added `craft\db\SoftDeleteTrait`, which can be used by Active Record classes that wish to support soft deletes. 
- Added `craft\services\Categories::getGroupByUid()`.
- Added `craft\services\Fields::restoreLayoutById()`.
- Added `craft\services\Gc` for handling garbage collection tasks.
- Added `craft\services\Sections::getSectionByUid()`.
- Added `craft\services\Sites::restoreSiteById()`.
- Added the ActiveRecord Soft Delete Extension for Yii2.
