# Release Notes for Craft CMS 3.1 (WIP)

### Added
- Added the Project Config, a portable and centralized configuration for system settings. ([#1429](https://github.com/craftcms/cms/issues/1429)) 
- Elements, field layouts, sites, and site groups are now soft-deleted. ([#867](https://github.com/craftcms/cms/issues/867))
- Entries, categories, and users can now be restored within the Control Panel by searching for `is:trashed` and clicking the “Restore” button.
- Some Site settings (Base URL), volume settings (Base URL and File System Path), and email settings (System Email Address, Sender Name, HTML Email Template, Username, Password, and Host Name) can now be set to environment variables using a `$VARIABLE_NAME` syntax. ([#3219](https://github.com/craftcms/cms/issues/3219))
- Control Panel settings that support environment variables now autosuggest environment variable names (and aliases when applicable) while typing.
- Control Panel settings that define a template path now autosuggest existing template files.
- Added cross-domain support for Live Preview. ([#1521](https://github.com/craftcms/cms/issues/1521))
- Custom fields can now opt out of being included in elements’ search keywords. ([#2600](https://github.com/craftcms/cms/issues/2600))
- Added the `allowAdminChanges` config setting.
- Added the `softDeleteDuration` config setting.
- Added the `useProjectConfigFile` config setting.
- Added the `gc` console command, which can be used to run garbage collection tasks.
- Added the `trashed` element query param, which can be used to query for elements that have been soft-deleted.
- Added the `expression()` Twig function, for creating new `yii\db\Expression` objects in templates. ([#3289](https://github.com/craftcms/cms/pull/3289))
- Added the `parseEnv()` Twig function.
- Added the `plugin()` Twig function.
- Added the `_includes/forms/autosuggest.html` include template for the Control Panel. 
- Added `Craft::parseEnv()`.
- Added `craft\base\ApplicationTrait::getIsLive()`.
- Added `craft\base\Element::EVENT_AFTER_RESTORE`.
- Added `craft\base\Element::EVENT_BEFORE_RESTORE`.
- Added `craft\base\ElementInterface::afterRestore()`.
- Added `craft\base\ElementInterface::beforeRestore()`.
- Added `craft\base\Field::createFieldConfig()`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_RESTORE`.
- Added `craft\base\Field::EVENT_BEFORE_ELEMENT_RESTORE`.
- Added `craft\base\FieldInterface::afterElementRestore()`.
- Added `craft\base\FieldInterface::beforeElementRestore()`.
- Added `craft\behaviors\EnvAttributeParserBehavior`.
- Added `craft\controllers\LivePreviewController`.
- Added `craft\db\Command::restore()`.
- Added `craft\db\Command::softDelete()`.
- Added `craft\db\Migration::restore()`.
- Added `craft\db\Migration::softDelete()`.
- Added `craft\db\SoftDeleteTrait`, which can be used by Active Record classes that wish to support soft deletes.
- Added `craft\elements\actions\Restore`, which can be included in elements’ `defineActions()` methods to opt into element restoration.
- Added `craft\events\ConfigEvent`.
- Added `craft\events\DeleteElementEvent`, which provides a `$hardDelete` property that can be set to `true` to force an element to be immediately hard-deleted. ([#3403](https://github.com/craftcms/cms/pull/3403))
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
- Added `craft\models\Site::getBaseUrl()`.
- Added `craft\services\Categories::getGroupByUid()`.
- Added `craft\services\Elements::restoreElement()`.
- Added `craft\services\Elements::EVENT_AFTER_RESTORE_ELEMENT`.
- Added `craft\services\Elements::EVENT_BEFORE_RESTORE_ELEMENT`.
- Added `craft\services\Fields::restoreLayoutById()`.
- Added `craft\services\Gc` for handling garbage collection tasks.
- Added `craft\services\ProjectConfig`.
- Added `craft\services\Routes::deleteRouteByUid()`
- Added `craft\services\Sections::getSectionByUid()`.
- Added `craft\services\Sites::restoreSiteById()`.
- Added `craft\web\Controller::requireCpRequest()`.
- Added `craft\web\Controller::requireSiteRequest()`.
- Added `craft\web\twig\variables\Cp::getEnvSuggestions()`.
- Added `craft\web\twig\variables\Cp::getTemplateSuggestions()`.
- Added the ActiveRecord Soft Delete Extension for Yii2.
- Added the Symfony Yaml Component.
- The bundled Vue asset bundle now includes Vue-autosuggest.

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
- `craft\services\Routes::saveRoute()` now expects site and route UIDs instead of IDs.
- `craft\services\Routes::updateRouteOrder()` now expects route UIDs instead of IDs.

### Removed
- Removed `craft\services\Routes::deleteRouteById()`

### Deprecated
- Deprecated `craft\base\ApplicationTrait::getIsSystemOn()`. `getIsLive()` should be used instead.
- Deprecated `craft\models\Info::getEdition()`. `Craft::$app->getEdition()` should be used instead.
- Deprecated `craft\models\Info::getName()`. `Craft::$app->projectConfig->get('system.name')` should be used instead.
- Deprecated `craft\models\Info::getOn()`. `Craft::$app->getIsLive()` should be used instead.
- Deprecated `craft\models\Info::getTimezone()`. `Craft::$app->getTimeZone()` should be used instead.
- Deprecated `craft\services\Routes::getDbRoutes()`. `craft\services\Routes::getProjectConfigRoutes()` should be used instead.
- Deprecated `craft\services\SystemSettings`. `craft\services\ProjectConfig` should be used instead.
- Deprecated `craft\validators\UrlValidator::$allowAlias`. `craft\behaviors\EnvAttributeParserBehavior` should be used instead.

### Security
- It’s no longer possible to spoof Live Preview requests.
