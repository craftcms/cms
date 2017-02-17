Craft CMS 3.0 Working Changelog
===============================

## Unreleased

### Added
- Added the `withStructure` param to element queries (defaults to `true` for entry and category queries).
- Added `craft\base\PluginInterface::setSettings()`.
- Added `craft\elements\db\ElementQueryInterface::withStructure()`.
- Added `craft\helpers\App::humanizeClass()`.
- Added `craft\helpers\FileHelper::lastModifiedTime()`.
- Added `craft\models\FieldLayout::getFieldByHandle()`.
- Added `craft\services\Tasks::getTaskInfo()`.
- Added `craft\web\Request::accepts()`.
- Added the `$maybeAutoRun` argument to `craft\services\Tasks::queueTask()`.
- Added Craft’s required PHP extensions to `composer.json`.

### Changed
- The Resave Elements task now shows a more humanized version of the element type’s class name in its default descriptions.
- Elements no longer get a default title on save, unless they’re being saved without validation.
- `craft\fields\BaseRelationField::$allowMultipleSources` is now public.
- `craft\fields\BaseRelationField::$allowLimit` is now public.
- Renamed `craft\fields\BaseRelationField::sourceOptions()` to `getSourceOptions()`, and now it’s public.
- Renamed `craft\fields\BaseRelationField::targetSiteFieldHtml()` to `getTargetSiteFieldHtml()`, and now it’s public.
- Renamed `craft\fields\BaseRelationField::viewModeFieldHtml()` to `getViewModeFieldHtml()`, and now it’s public.
- It’s now possible for field types that extend `craft\fields\BaseRelationField` to override the settings template by setting the `$settingsTemplate` property.
- Updated Garnish to 0.1.13.

### Removed
- Removed `craft\base\Element::resolveStructureId()`.
- Removed `craft\base\ElementInterface::getStructureId()`.
- Removed `craft\base\ElementInterface::setStructureId()`.

### Fixed
- #1361: Fixed a bug where a plugin’s `init()` method couldn’t access its own settings values.
- Fixed a PHP type error if you tried to save a Number field’s setting with “Min Value” set to nothing.
- Fixed a bug where it was not possible to rename a file with the Asset "Rename File" action.
- #1367: Fixed a PHP error that occurred when uploading a user photo.
- #1365: Fixed a bug where element titles were not translatable.
- #1366: Fixed a PHP error that occurred on the Settings → General page if the `timezone` config setting was set.
- Fixed a bug where some Control Panel message strings were getting translated with the `site` translation category rather than `app`.
- Fixed a bug where pagination URLs would define page numbers using a `pg` query string param if the `pageTrigger` config setting was set to `'?p'` and the `pathParam` config setting was set to something besides `'p'`.
- Fixed a bug where if a Craft update failed and the attempt to restore the database from a backup failed, the UI would still show that the restore was successful.
- Fixed several migration related bugs that were preventing some Craft 2.6 installs from being able to update to Craft 3.
- Fixed a bug where renaming a folder would remove it’s trailing slash from path.
- #1371: Fixed a bug where asset bundles weren’t always getting re-published when a sub-file(s) had been updated.
- Fixed a bug where SVG images without a viewbox defined would not be scaled correctly.
- Fixed a bug where Craft would generate warning when trying to index images with no content in them.
- #1372: Fixed a bug where the Database Backup utility wouldn’t show an error when the backup failed.
- Fixed a bug where saving preexisting Active Record objects was not updating the `dateUpdated` column automatically.
- #1380: Fixed a bug where required fields on a field layout were not being enforced.
- Fixed a bug where required Plain Text fields were not getting a validation error if left blank.
- Fixed a PHP type error that occurred when calling `craft\base\Element::getPrevSibling()` or `getNextSibling()`.
- #1375: Fixed a bug where Structure-related element methods (e.g. `getParent()`) weren’t working for elements that weren’t queried with the `structureId` param set.

## 3.0.0-beta.3 - 2017-02-07

### Added
- #1338: Added the new “System Name” general setting, which defines the name that should be visible in the global CP sidebar.
- Added `craft\base\ElementInterface::getSearchKeywords()`.
- Added `craft\helpers\Db::areColumnTypesCompatible()`.
- Added `craft\helpers\Db::getSimplifiedColumnType()`.
- Added `craft\services\Security::redactIfSensitive()`.

### Changed
- #2: The “Set status” batch element action now goes through the normal element save process, rather than directly modifying the DB values, ensuring that the elements validate before enabling them.
- The “Set status” batch element action now updates elements’ site statuses in addition to their global statuses, when setting the status to Enabled.
- #1328: Sensitive global values are now redacted from the logs.
- Editable tables now support a `radioMode` checkbox column option, which prevents more than one of the column’s checkboxes from being checked at a time.
- `craft\helpers\Db::getNumericalColumnType()` no longer returns unsigned integer column types for MySQL.
- The “Field Type” setting on Edit Field pages no longer shows field type options where there’s no chance the existing field data will map over.
- When an entry type is updated, Craft now re-saves all entries of that type.
- Added a `string` return type declaration to `craft\base\PreviewableFieldInterface::getTableAttributeHtml()`.
- Updated Craft Server Check to 1.0.8.

### Removed
- Removed the `afterSetStatus` event from `craft\elements\actions\SetStatus`.
- Removed `craft\enums\ColumnType`.
- Removed `craft\helpers\Logging`.
- Removed `craft\models\SiteSettings`.
- Removed `craft\web\assets\jcrop\JcropAsset`.

### Fixed
- Fixed a bug where saving a disabled entry or draft without a post/expiry date would default to the currently-set date on the entry/draft, rather than clearing out the field.
- Fixed some asterisk icons.
- #1334 Fixed a bug where it was impossible to upload user photo, site logo or site icon.
- #8: Fixed a bug where it was possible to select multiple default options for Dropdown and Radio Buttons fields.
- #9: Fixed a bug where the “Globals” Control Panel nav item would link to a 404 right after deleting the first global set in Settings → Globals. 
- #1341: Fixed a bug that occurred when generating transforms for images with focal points/
- #1342: Fixed a bug where the utility status was overlapping the submit button in Utilities.
- Fixed a bug where `craft\helpers\Db::getNumericalColumnType()` could return a column type that wouldn’t actually fix the `$max` argument in PostgreSQL.
- #15: Fixed a bug where entry URIs weren’t getting updated after an entry type was updated when the URI format referenced entry type properties.
- #1332: Fixed a bug that broke site administration.
- Fixed a PHP error that occurred when saving an entry with a Matrix field on a multi-site install, in some cases.
- #1332: Fixed a PHP error that occurred when saving an element with a Date/Time field.
- #1354: Fixed a Twig syntax error when editing an element with a Color field.
- Fixed a bug where fields that implemented `craft\base\PreviewableFieldInterface` were not showing up as options on element indexes.
- Fixed task re-running.
- Fixed a bug with transforming SVG files.
- Fixed a bug with transforming images on external sources.
- #1344: `config/app.php` can now be a multi-environment config.
- #1345: Fixed a PHP error that occurred when creating a new tag.
- #1360: Fixed a bug where relations would be dropped when running the Resave Elements task.
- #1346: Fixed a PHP error that occurred when executing an element query with the `relatedTo` param set to an element.
- #1349: Fixed a JavaScript error that was preventing Redactor from loading for Rich Text fields, for users with a non-English preferred language.
- #1351: Fixed a PHP type error that would occur when calling `craft\services\Globals::getSetByHandle()`.
- #1350: Fixed a bug where Plain Text fields weren’t enforcing their Max Length setting, and Number fields weren’t enfoncing their Min and Max Value settings.
- #1355: Fixed a 404 error that would occur when switching sites when editing a global set.
- #1359: Fixed a bug that broke reference tags for Global Sets, Matrix Blocks and Tags.
- #1358: Fixed a Twig parse error that occurred when using the deprecated `{% includecss %}` or `{% includejs %}` tags as tag pairs.
- Fixed a bug where Craft was only logging warnings and errors when Dev Mode was enabled.
- #1356: Fixed the “x” button’s icon that appears in search inputs, used to clear the search input.
- Fixed a bug where you would get a validation error if you tried to purchase Craft with a 100%-off coupon code.
- #1347: Fixed a migration error that was preventing Craft 2.6 installs from being able to update to Craft 3. 

## 3.0.0-beta.2 - 2017-02-02

### Changed
- Craft now logs `craft\db\QueryAbortedException`s.
- Element queries will now throw `craft\db\QueryAbortedException`s if any structure params are set, but `structureId` is not set.
- `craft\services\Categories::fillGapsInCategoryIds()` now has a required `$structureId` argument.
- #1331: Added `type` and `value` to the list of reserved field handles.
- Console requests now get the CP template mode by default.
- #1335: Site requests now resolve plugin template paths as if they were CP requests when rendering with the CP template mode.
- Updated Yii to 2.0.11.1.

### Removed
- Removed support for Memcache (without a d), as it is not compatible with PHP 7. (ostark)

### Fixed
- Fixed a bug where `craft\feeds\Feeds::getFeedItems()` was returning `null` when the results were not already cached, resulting in an “unknown error” on the Dashboard.
- Fixed an InvalidConfigException that would get thrown when attempting to edit an entry version with an author that had been deleted.
- Fixed a bug where swapping between entries in a section enabled for multiple sites would cause a PHP type error. (carlcs)
- Fixed a bug where the “Save as a draft” entry Save menu option would take you to a 404.
- Fixed a bug where the “Publish draft” entry draft Save menu option would take you to a 404.
- Fixed a bug where the “Delete draft” entry draft Save menu options would take you to a 404.
- Fixed a bug where the “Delete” category button would take you to a 404.
- Fixed a bug where saving a user with no permissions would result in a PHP type error.
- Fixed a bug where removing a user’s admin permissions using PostgreSQL would result in a SQL error.
- #1037: Fixed a bug where the “Revert entry to this version” button on entry version pages would result in an “No element exists with the ID 'X'” exception.
- #1311: Fixed a bug where creating a new user would cause a PHP type error.
- #1314: Fixed a bug where `src/config/defaults/general.php` was listing `'redis'` as a possible `cacheMethod` setting value, but Yii 2 does not have native support for Redis.
- Fixed a bug where `craft\db\QueryAbortedException`s were not getting caught when calling `craft\db\Query::scalar()` or `column()`.
- #1321: Fixed a bug where expanding a collapsed Structure entry or category on an index page would come up empty.
- #1316: Fixed some `TypeError`s in controller action responses.
- #1313: Fixed a PHP error that occurred when using the `{% nav %}` tag, or when selecting categories in a Categories field.
- Fixed a bug where deleting all the selections in a relation field would result in no changes being made to the field on save.
- #1322: Fixed a PHP error that occurred when editing a Rich Text field with the “Available Transforms” setting set to `*`.
- #1312: Fixed a PHP error that occurred when editing an image using GD.
- #1323: Fixed a PHP error that occurred when generating image transforms.
- Fixed a bug where Assets fields’ “Sources” settings weren’t working.
- #1325: Fixed a bug where disabled entries and categories weren’t showing up in their Control Panel indexes.
- Fixed a bug where creating a Number field type on PostgreSQL would result a SQL error.
- #1326: Fixed a bug where calling `craft\services\Sections::getEntryTypesByHandle()` would cause a PHP type error. (my2ter)
- Fixed a bug where plugin updates could be displayed with the wrong date if the system time zone was behind UTC.

## 3.0.0-beta.1 - 2017-01-29

### Added
- Ported all recent changes from Craft 2, including chart-related things added in Craft 2.6.
- Craft 3 now requires PHP 7.0.0 or later.
- Added an image editor to the Assets index page, with support for cropping, rotating, and flipping images, as well as setting focal points on them, which influences where images should be cropped for image transforms.
- Craft can now be installed via Composer: `composer require craftcms/craft`.
- Craft now supports installing plugins via Composer, with the help [Craft CMS Composer Installer](https://github.com/craftcms/plugin-installer).
- Craft now checks for plugin info in a composer.json file, rather than plugin.json, for plugins that were manually installed in `plugins/`. (See the [Craft CMS Composer Installer](https://github.com/craftcms/plugin-installer) readme for details on how the info should be formatted.)
- Plugin icons now must be stored at the root of the plugin’s source directory.
- Plugin IDs are now `kebab-case` versions of their handles.
- Craft now automatically loads the `vendor/autoload.php` file (if it exists) for plugins that were manually installed.
- Added the `bootstrap/` folder alongside the `src/` folder, with new web.php and console.php bootstrap files.
- Added PostgreSQL support, which can be enabled by setting the `driver` setting in `config/db.php` to `'pgsql'`.
- Added the `update/run-pending-migrations` controller action, which can be used as a post-deploy hook for deployment services like DeployBot, to get Craft to automatically run any pending migrations, minimizing site downtime.
- Added the `backupCommand` config setting, which can be used to override the command Craft executes when creating a database backup.
- Added the `restoreCommand` config setting, which can be used to override the command Craft executes when restoring a database backup.
- Added the `dsn` DB config setting, which can be used to manually specify the DSN string, ignoring most other DB config settings.
- Added the `schema` DB config setting, which can be used to assign the default schema used when connecting to a PostgreSQL database.
- Added support for setting Volume config settings in `config/volumes.php`. The file should return an array with keys that match volume handles, and values that are config arrays for the volumes.
- It is now possible to override the default Guzzle settings from `config/guzzle.php`.
- Added the `view` global Twig variable, which is a reference to the View class that is rendering the template.
- Added `craft.matrixBlocks()`, which can be used to query for Matrix blocks.
- Added the `SORT_ASC` and `SORT_DESC` global Twig variables, which can be used to define query sorting in element queries.
- Added the `POS_HEAD`, `POS_BEGIN`, `POS_END`, `POS_READY`, and `POS_LOAD` global Twig variables, which can be used to define the placement of registered scripts.
- Added the `className()` global Twig function, which returns the class name of a given object.
- Added the `|atom` and `|rss` Twig filters, for formatting dates in Atom and RSS date formats, respectively.
- Added the `|column` Twig filter, for capturing the key/property values of a series of arrays/objects.
- Added the `|index` Twig filter, for indexing an array of arrays/objects by one of their keys/values.
- Added the “Utilities” section to the Control Panel, replacing the Tools area of the Settings page.
- Added the Utility API, which enables plugins to provide custom utilities.
- Added the JavaScript method `BaseElementIndex::refreshSources()`.
- Added method paramater and return types everywhere possible.
- Added a new `@lib` Yii alias, pointed to `vendor/craftcms/cms/lib/`.
- Added `Craft::createGuzzleClient()`, which creates a Guzzle client instance with any custom config settings merged in with the site default settings.
- Added `craft\base\LocalVolumeInterface`.
- Added `craft\base\Utility`.
- Added `craft\base\UtilityInterface`.
- Added `craft\controllers\UtilitiesController`.
- Added `craft\db\pgsql\QueryBuilder`.
- Added `craft\db\pgsql\Schema`.
- Added `craft\db\TableSchema`.
- Added `craft\elements\actions\EditImage`.
- Added `craft\errors\InvalidPluginException`.
- Added `craft\errors\ShellCommandException`.
- Added `craft\events\RegisterAssetFileKindsEvent`.
- Added `craft\events\RegisterCacheOptionsEvent`.
- Added `craft\events\RegisterComponentTypesEvent`.
- Added `craft\events\RegisterCpAlertsEvent`.
- Added `craft\events\RegisterCpNavItemsEvent`.
- Added `craft\events\RegisterElementDefaultTableAttributesEvent`.
- Added `craft\events\RegisterElementHtmlAttributesEvent`.
- Added `craft\events\RegisterElementSearchableAttributesEvent`.
- Added `craft\events\RegisterElementSortOptionsEvent`.
- Added `craft\events\RegisterElementSourcesEvent`.
- Added `craft\events\RegisterElementTableAttributesEvent`.
- Added `craft\events\RegisterEmailMessagesEvent`.
- Added `craft\events\RegisterRichTextLinkOptionsEvent`.
- Added `craft\events\RegisterUrlRulesEvent`.
- Added `craft\events\RegisterUserActionsEvent`.
- Added `craft\events\RegisterUserPermissionsEvent`.
- Added `craft\events\ResolveResourcePathEvent`.
- Added `craft\events\SetAssetFilenameEvent`.
- Added `craft\events\SetElementRouteEvent`.
- Added `craft\events\SetElementTableAttributeHtmlEvent`.
- Added `craft\helpers\FileHelper`.
- Added `craft\helpers\MailerHelper`.
- Added `craft\services\Utilities`.
- Added `craft\utilities\AssetIndexes`.
- Added `craft\utilities\ClearCaches`.
- Added `craft\utilities\DbBackup`.
- Added `craft\utilities\DeprecationErrors`.
- Added `craft\utilities\FindAndReplace`.
- Added `craft\utilities\PhpInfo`.
- Added `craft\utilities\SearchIndexes`.
- Added `craft\utilities\SystemReport`.
- Added `craft\utilities\Updates`.
- Added `craft\validators\ArrayValidator`.
- Added `craft\validators\AssetFilenameValidator`.
- Added `craft\validators\UsernameValidator`.
- Added `craft\validators\UserPasswordValidator`.
- Added `craft\web\AssetBundle`.
- Added `craft\web\assets\assetindexes\AssetIndexesAsset`.
- Added `craft\web\assets\clearcaches\ClearCachesAsset`.
- Added `craft\web\assets\colorpicker\ColorpickerAsset`.
- Added `craft\web\assets\craftsupport\CraftSupportAsset`.
- Added `craft\web\assets\dashboard\DashboardAsset`.
- Added `craft\web\assets\datepickeri18n\DatepickerI18nAsset`.
- Added `craft\web\assets\dbbackup\DbBackupAsset`.
- Added `craft\web\assets\deprecationerrors\DeprecationErrorsAsset`.
- Added `craft\web\assets\editcategory\EditCategoryAsset`.
- Added `craft\web\assets\editentry\EditEntryAsset`.
- Added `craft\web\assets\edittransform\EditTransformAsset`.
- Added `craft\web\assets\edituser\EditUserAsset`.
- Added `craft\web\assets\emailmessages\EmailMessagesAsset`.
- Added `craft\web\assets\fabric\FabricAsset`.
- Added `craft\web\assets\feed\FeedAsset`.
- Added `craft\web\assets\fields\FieldsAsset`.
- Added `craft\web\assets\fileupload\FileUploadAsset`.
- Added `craft\web\assets\findreplace\FindReplaceAsset`.
- Added `craft\web\assets\generalsettings\GeneralSettingsAsset`.
- Added `craft\web\assets\imageeditor\ImageEditorAsset`.
- Added `craft\web\assets\installer\InstallerAsset`.
- Added `craft\web\assets\jcrop\JcropAsset`.
- Added `craft\web\assets\jqueryui\JqueryUiAsset`.
- Added `craft\web\assets\login\LoginAsset`.
- Added `craft\web\assets\matrix\MatrixAsset`.
- Added `craft\web\assets\matrixsettings\MatrixSettingsAsset`.
- Added `craft\web\assets\newusers\NewUsersAsset`.
- Added `craft\web\assets\plugins\PluginsAsset`.
- Added `craft\web\assets\positionselect\PositionSelectAsset`.
- Added `craft\web\assets\quickpost\QuickPostAsset`.
- Added `craft\web\assets\qunit\QunitAsset`.
- Added `craft\web\assets\recententries\RecentEntriesAsset`.
- Added `craft\web\assets\redactor\RedactorAsset`.
- Added `craft\web\assets\richtext\RichTextAsset`.
- Added `craft\web\assets\routes\RoutesAsset`.
- Added `craft\web\assets\searchindexes\SearchIndexesAsset`.
- Added `craft\web\assets\sites\SitesAsset`.
- Added `craft\web\assets\tablesettings\TableSettingsAsset`.
- Added `craft\web\assets\tests\TestsAsset`.
- Added `craft\web\assets\updater\UpdaterAsset`.
- Added `craft\web\assets\updates\UpdatesAsset`.
- Added `craft\web\assets\updateswidget\UpdatesWidgetAsset`.
- Added `craft\web\assets\userpermissions\UserPermissionsAsset`.
- Added `craft\web\assets\utilities\UtilitiesAsset`.
- Added `craft\web\assets\whatsnew\WhatsNewAsset`.
- Added `craft\web\assets\xregexp\XregexpAsset`.
- Added `craft\base\ApplicationTrait::$env`, which stores the current environment ID, which is set to `$_SERVER['SERVER_NAME']` by default and can be overridden with the `CRAFT_ENVIRONMENT` PHP constant.
- Added `craft\base\Element::$validateCustomFields`, which can be set to true or false to explicitly require/prevent custom field validation.
- Added `craft\base\Element::afterDelete()`, which is called after an element is deleted.
- Added `craft\base\Element::afterMoveInStructure()`, which is called after an element is moved within a structure.
- Added `craft\base\Element::defineDefaultTableAttributes()`.
- Added `craft\base\Element::beforeDelete()`, which is called before the element is deleted.
- Added `craft\base\Element::defineActions()`.
- Added `craft\base\Element::defineSearchableAttributes()`.
- Added `craft\base\Element::defineSortOptions()`.
- Added `craft\base\Element::defineSources()`.
- Added `craft\base\Element::defineTableAttributes()`.
- Added `craft\base\Element::getHtmlAttributes()`, which gives elements a chance to define any HTML attributes that should be included when rendering an element node for the Control Panel.
- Added `craft\base\Element::getSerializedFieldValues()`.
- Added `craft\base\Element::htmlAttributes()`.
- Added `craft\base\Element::route()`.
- Added `craft\base\Element::tableAttributeHtml()`.
- Added `craft\base\ElementInterface::refHandle()`.
- Added `craft\base\Field::afterElementDelete()`, which is called by an element after it is deleted.
- Added `craft\base\Field::beforeElementDelete()`, which is called by an element before it is deleted.
- Added `craft\base\Field::getElementValidationRules()`, which field types can override to return their element-level validation rules.
- Added `craft\base\MissingComponentTrait::createFallback()`.
- Added `craft\base\Plugin::$changelogUrl`, which replaces `$releaseFeedUrl` and should point to a Markdown-formatted changelog.
- Added `craft\base\Plugin::$downloadUrl`, which should point to the plugin’s download URL.
- Added `craft\base\Plugin::$hasCpSection`, which replaces the static `hasCpSection()` method.
- Added `craft\controllers\AssetsController::actionImageEditor()`.
- Added `craft\controllers\AssetsController::actionEditImage()`.
- Added `craft\controllers\AssetsController::actionSaveImage()`.
- Added `craft\db\Connection::backupTo()`.
- Added `craft\db\mysql\Schema::findIndexes()`.
- Added `craft\elements\Asset::$focalPoint`.
- Added `craft\elements\Asset::$keepFileOnDelete`, which can be set to true if the corresponding file should not be deleted when deleting the asset.
- Added `craft\elements\Asset::$newFilename`, which can be set before saving an asset to rename its file.
- Added `craft\helpers\App::craftDownloadUrl()`.
- Added `craft\helpers\App::isComposerInstall()`.
- Added `craft\helpers\App::majorMinorVersion()`.
- Added `craft\helpers\ArrayHelper::rename()`.
- Added `craft\helpers\Assets::editorImagePath()`.
- Added `craft\helpers\Db::isTypeSupported()`.
- Added `craft\helpers\Update::getBasePath()`.
- Added `craft\helpers\Update::parseManifestLine()`.
- Added `craft\helpers\Assets::getFileKindByExtension()`.
- Added `craft\helpers\Assets::getFileKindLabel()`.
- Added `craft\helpers\Assets::getFileKinds()`.
- Added `craft\image\Raster::flipHorizontally()`.
- Added `craft\image\Raster::flipVertically()`.
- Added `craft\services\Config::getAllowedFileExtensions()`.
- Added `craft\services\Config::getDbPort()`.
- Added `craft\services\Config::getUseWriteFileLock()`.
- Added `craft\services\Config::isExtensionAllowed()`.
- Added `craft\services\Elements::deleteElement()`.
- Added `craft\services\Elements::getElementTypesByIds()`.
- Added `craft\services\Images::getCanUseImagick()`.
- Added `craft\services\Images::getImageMagickApiVersion()`.
- Added `craft\services\Path::getImageEditorSourcesPath()`.
- Added `craft\services\Plugins::getPluginByModuleId()`.
- Added `craft\services\Plugins::getPluginByPackageName()`.
- Added `craft\services\Plugins::isComposerInstall()`.
- Added `craft\web\AssetManager::getPublishedPath()`.
- Added `craft\web\AssetManager::getPublishedUrl()`.
- Added `craft\web\Session::addAssetBundleFlash()`.
- Added `craft\web\Session::getAssetBundleFlashes()`.
- Added `craft\web\UploadedFile::saveAsTempFile()`.
- Added the `beforeDelete`, `afterDelete`, `beforeMoveInStructure`, and `afterMoveInStructure`,  events to `craft\base\Element`.
- Added the `beforeElementSave`, `afterElementSave`, `beforeElementDelete`, and `afterElementDelete` events to `craft\base\Field`.
- Added the `beforeRestoreBackup` and `afterRestoreBackup` events to `craft\db\Connection`.
- Added the `registerActions` event to `craft\base\Element`.
- Added the `registerAlerts` event to `craft\helpers\Cp`.
- Added the `registerFileKinds` event to `craft\helpers\Assets`.
- Added the `registerCacheOptions` event to `craft\tools\ClearCaches`.
- Added the `registerCpNavItems` event to `craft\web\twig\variables\Cp`.
- Added the `registerCpUrlRules` and `registerSiteUrlRules` events to `craft\web\UrlManager`.
- Added the `registerDefaultTableAttributes` event to `craft\base\Element`.
- Added the `registerElementTypes` event to `craft\services\Elements`.
- Added the `registerFieldTypes` event to `craft\services\Fields`.
- Added the `registerHtmlAttributes` event to `craft\base\Element`.
- Added the `registerLinkOptions` event to `craft\fields\RichText`.
- Added the `registerMailerTransportTypes` event to `craft\helpers\MailerHelper`.
- Added the `registerMessages` event to `craft\services\EmailMessages`.
- Added the `registerPermissions` event to `craft\services\UserPermissions`.
- Added the `registerSearchableAttributes` event to `craft\base\Element`.
- Added the `registerSortOptions` event to `craft\base\Element`.
- Added the `registerSources` event to `craft\base\Element`.
- Added the `registerTableAttributes` event to `craft\base\Element`.
- Added the `registerUserActions` event to `craft\controllers\UsersController`.
- Added the `registerVolumeTypes` event to `craft\services\Volumes`.
- Added the `registerWidgetTypes` event to `craft\services\Dashboard`.
- Added the `resolveResourcePath` event to `craft\services\Resources`.
- Added the `setFilename` event to `craft\helpers\Assets`.
- Added the `setRoute` event to `craft\base\Element`.
- Added the `setTableAttributeHtml` event to `craft\base\Element`.
- Added support for a `.readable` CSS class for views that are primarily textual content.
- Added a “Size” setting to Number fields.
- Added `d3FormatLocaleDefinition`, `d3TimeFormatLocaleDefinition`, `d3Formats` global JS variables.
- Added the `xAxis.showAxis`, `xAxis.formatter` and `yAxis.formatter` settings to the Area chart.
- Added `Craft.charts.BaseChart.setSettings()`.
- Added `Craft.charts.utils.getNumberFormatter()`.
- Added `Craft.charts.utils.getTimeFormatter()`.
- Added php-shellcommand.
- Added the ZendFeed library.
- Added the fabric.js JavaScript library.
- Added the d3-format JavaScript library.
- Added the d3-time-format JavaScriptlibrary.

### Changed
- The bootstrap script now assumes that the `vendor/` folder is 3 levels up from the `bootstrap/` directory by default (e.g. `vendor/craftcms/cms/bootstrap/`). If that is not the case (most likely because Craft had been symlinked into place), the `CRAFT_VENDOR_PATH` PHP constant can be used to correct that.
- The default `port` DB config value is now either `3306` (if MySQL) or `5432` (if PostgreSQL).
- The default `tablePrefix` DB config value is now empty.
- Renamed the `defaultFilePermissions` config setting to `defaultFileMode`, and it is now `null` by default.
- Renamed the `defaultFolderPermissions` config setting to `defaultDirMode`.
- Renamed the `useWriteFileLock` config setting to `useFileLocks`.
- Renamed the `backupDbOnUpdate` config setting to `backupOnUpdate`. Note that performance should no longer be a major factor when setting this to false, since the backup is no longer generated by PHP.
- Renamed the `restoreDbOnUpdateFailure` config setting to `restoreOnUpdateFailure`.
- File-based data caching now respects the `defaultDirMode` config setting.
- Redactor config files must now be valid JSON.
- When a category is deleted, its nested categories are no longer deleted with it.
- Craft Personal and Client editions are now allowed to have custom Volume types (e.g. Amazon S3).
- Renamed the “Get Help” widget to “Craft Support”.
- When editing a field whose type class cannot be found, Craft will now select Plain Text as a fallback and display a validation error on the Field Type setting.
- When editing a volume whose type class cannot be found, Craft will now select Local as a fallback and display a validation error on the Volume Type setting.
- When editing email settings and the previously-selected transport type class cannot be found, Craft will now select PHP Mail as a fallback and display a validation error on the Transport Type setting.
- The Feed widget is now limited to show 5 articles by default.
- Element queries’ `status` params must now always be set to valid statuses, or the query won’t return any results.
- Craft now relies on command line tools to create database backups (`mysqldump` and `pg_dump`).
- Test emails now mask the values for any Mailer transport type settings that include “key” or “password” in their setting name.
- The Control Panel page header is now fixed when scrolling down.
- Translatable fields are no longer marked as translatable when editing an element type that isn’t localizable (e.g. user accounts).
- Custom email messages are now stored on o per-language basis rather than per-site basis.
- Element indexes now remember which sources were expanded across multiple requests.
- Element indexes now remember if a nested source was selected across multiple requests.
- Plugin schema versions now default to `'1.0.0'`, and plugins absolutely must increment their schema version if they want any pending migrations to be noticed.
- Resource requests no longer serve files within Craft’s or plugins’ `resources/` folders.
- Renamed the `{% registercss %}` Twig tag to `{% css %}`.
- Renamed the `{% registerjs %}` Twig tag to `{% js %}`.
- `craft\base\Plugin` no longer automatically registers field types in the plugin’s `fields/` subfolder.
- `craft\base\Plugin` no longer automatically registers widget types in the plugin’s `widgets/` subfolder.
- `craft\base\Plugin` no longer automatically registers volume types in the plugin’s `volumes/` subfolder.
- `craft\elements\User` now supports a `password` validation scenario, which only validates the `$newPassword` property.
- `craft\elements\User` now supports a `registration` validation scenario, which only validates the `$username`, `$email`, and `$newPassword` properties.
- It is no longer possible to change a user’s locked/suspended/pending/archived status when saving the User element normally.
- `craft\elements\db\MatrixBlockQuery::owner()` and `ownerSiteId()` now set the `$siteId` property when appropriate.
- The source keys that are passed into element methods’ `$source` arguments now reflect the full path to the source, if it is a nested source (e.g. `folder:1/folder:2`).
- The `Craft.publishableSections` Javascript array now includes info about each section’s entry types.
- `craft\db\Connection::backup()` now throws an exception if something goes wrong, rather than returning `true` or `false`. If no exception is thrown, it worked.
- `craft\db\mysql\Schema::getTableNames()` no longer only returns the table names with the right table prefix.
- `craft\services\Elements::deleteElementById()` no longer accepts an array of element IDs.
- `craft\base\Element::afterSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Element::beforeSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Field::afterElementSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Field::beforeElementSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\SavableComponent::afterSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\SavableComponent::beforeSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\db\Connection::columnExists()`’s `$table` argument can now be a \`craft\yii\db\TableSchema` object.
- `craft\elements\Asset::getFolder()` now throws a `yii\base\InvalidConfigException` if its `$folderId` property is set to an invalid folder ID.
- `craft\elements\Asset::getVolume()` now throws a `yii\base\InvalidConfigException` if its `$volumeId` property is set to an invalid volume ID.
- `craft\elements\Tag::getGroup()` now throws a `yii\base\InvalidConfigException` if its `$groupId` property is set to an invalid tag group ID.
- `craft\elements\User::getAuthor()` now throws a `yii\base\InvalidConfigException` if its `$authorId` property is set to an invalid user ID.
- `craft\elements\User::getPhoto()` now throws a `yii\base\InvalidConfigException` if its `$photoId` property is set to an invalid asset ID.
- `craft\models\FieldLayoutTab::getLayout()` now throws a `yii\base\InvalidConfigException` if its `$layoutId` property is set to an invalid field layout ID.
- `craft\services\Elements::deleteElementById()` now has `$elementType` and `$siteId` arguments.
- `craft\services\Element::getElementTypeById()` no longer accepts an array of element IDs. Use `getElementTypesByIds()` instead.
- `craft\services\Path::getAppPath()` now throws an exception if it is called within a Composer install, as there is no “app path”.
- The `beforeElementSave` and `afterElementSave` events triggered by `craft\base\Element` now have `$isNew` properties, which indicate whether the element is brand new.
- The `beforeSave` and `afterSave` events triggered by `craft\base\Element` now have `$isNew` properties, which indicate whether the element is brand new.
- The `beforeSave` and `afterSave` events triggered by `craft\base\SavableComponent` now have `$isNew` properties, which indicate whether the component is brand new.
- Renamed literally every Craft class’ namespace from `craft\app\*` to `craft\*`.
- Renamed `craft\base\Savable` to `Serializable`, and its `getSavableValue()` method was renamed to `serialize()`.
- Renamed `craft\et\Et` to `EtTransport`.
- Renamed `craft\events\DbBackupEvent` to `BackupEvent`.
- Renamed `craft\events\EntryEvent` to `VersionEvent`.
- Renamed `craft\events\Event` to `CancelableEvent`.
- Renamed `craft\helpers\Url` to `UrlHelper`.
- Renamed `craft\services\Feeds` to `craft\feeds\Feeds`.
- Renamed `craft\mail\transportadaptors\BaseTransportAdaptor` to `craft\mail\transportadapters\BaseTransportAdapter`.
- Renamed `craft\mail\transportadaptors\Gmail` to `craft\mail\transportadapters\Gmail`.
- Renamed `craft\mail\transportadaptors\Php` to `craft\mail\transportadapters\Php`.
- Renamed `craft\mail\transportadaptors\Sendmail` to `craft\mail\transportadapters\Sendmail`.
- Renamed `craft\mail\transportadaptors\Smtp` to `craft\mail\transportadapters\Smtp`.
- Renamed `craft\mail\transportadaptors\TransportAdaptorInterface` to `craft\mail\transportadapters\TransportAdapterInterface`.
- Renamed `craft\models\AppNewRelease` to `AppUpdateRelease`.
- Renamed `craft\models\PluginNewRelease` to `UpdateRelease`.
- Renamed `Craft::getCookieConfig()` to `cookieConfig()`.
- Renamed `craft\base\Element::defineAvailableTableAttributes()` to `tableAttributes()`.
- Renamed `craft\base\Element::defineSearchableAttributes()` to `searchableAttributes()`.
- Renamed `craft\base\Element::defineSortableAttributes()` to `sortOptions()`.
- Renamed `craft\base\Element::getAvailableActions()` to `actions()`, and the method must return an array now.
- Renamed `craft\base\Element::getContentPostLocation()` to `getFieldParamNamespace()`.
- Renamed `craft\base\Element::getDefaultTableAttributes()` to `defaultTableAttributes()`.
- Renamed `craft\base\Element::getEagerLoadingMap()` to `eagerLoadingMap()`.
- Renamed `craft\base\Element::getFieldByHandle()` to `fieldByHandle()`.
- Renamed `craft\base\Element::getFields()` to `fieldLayoutFields()`.
- Renamed `craft\base\Element::getIndexHtml()` to `indexHtml()`.
- Renamed `craft\base\Element::getSources()` to `sources()`.
- Renamed `craft\base\Element::getStatuses()` to `statuses()`, and the method must return an array now.
- Renamed `craft\base\Element::setContentPostLocation()` to `setFieldParamNamespace()`.
- Renamed `craft\base\Element::setFieldValuesFromPost()` to `setFieldValuesFromRequest()`, and the method no longer accepts an array of field values. Only call this method as a shortcut for `setFieldParamNamespace()` and `setFieldValues()`, passing in the param namespace the field values should be extracted from on the request body.
- Renamed `craft\base\Field::getContentPostLocation()` to `requestParamName()`.
- Renamed `craft\base\Field::prepareValue()` to `normalizeValue()`.
- Renamed `craft\base\Field::prepareValueForDb()` to `serializeValue()`.
- Renamed `craft\base\Plugin::getSettingsHtml()` to `settingsHtml()`.
- Renamed `craft\base\PluginInterface::getVariableDefinition()` to `defineTemplateComponent()`.
- Renamed `craft\base\Task::getDefaultDescription()` to `defaultDescription()`.
- Renamed `craft\base\Volume::getAdapter()` to `adapter()`.
- Renamed `craft\base\Volume::getFilesystem()` to `filesystem()`.
- Renamed `craft\base\Volume::getVisibilitySetting()` to `visibility()`.
- Renamed `craft\base\WidgetInterface::getMaxColspan()` to `maxColspan()` (now static).
- Renamed `craft\base\WidgetInterface::getIconPath()` to `iconPath()` (now static).
- Renamed `craft\controllers\BaseElementsController::getContext()` to `context()`.
- Renamed `craft\controllers\BaseElementsController::getElementType()` to `elementType()`.
- Renamed `craft\db\Command::insertOrUpdate()` to `upsert()`.
- Renamed `craft\db\Migration::insertOrUpdate()` to `upsert()`.
- Renamed `craft\db\mysql\QueryBuilder::insertOrUpdate()` to `upsert()`.
- Renamed `craft\elements\User::getAuthData()` to `authData()`
- Renamed `craft\fields\BaseOptionsField::getDefaultValue()` to `defaultValue()`.
- Renamed `craft\fields\BaseOptionsField::getOptionLabel()` to `optionLabel()`.
- Renamed `craft\fields\BaseOptionsField::getOptionsSettingsLabel()` to `optionsSettingLabel()`.
- Renamed `craft\fields\BaseOptionsField::getTranslatedOptions()` to `translatedOptions()`.
- Renamed `craft\fields\BaseRelationField::getAvailableSources()` to `availableSources()`.
- Renamed `craft\fields\BaseRelationField::getInputSelectionCriteria()` to `inputSelectionCriteria()`.
- Renamed `craft\fields\BaseRelationField::getInputSources()` to `inputSources()`.
- Renamed `craft\fields\BaseRelationField::getInputTemplateVariables()` to `inputTemplateVariables()`.
- Renamed `craft\fields\BaseRelationField::getSourceOptions()` to `sourceOptions()`.
- Renamed `craft\fields\BaseRelationField::getSupportedViewModes()` to `supportedViewModes()`, and the method must return an array now.
- Renamed `craft\fields\BaseRelationField::getTargetSiteFieldHtml()` to `targetSiteFieldHtml()`.
- Renamed `craft\fields\BaseRelationField::getTargetSiteId()` to `targetSiteId()`.
- Renamed `craft\fields\BaseRelationField::getViewMode()` to `viewMode()`.
- Renamed `craft\fields\BaseRelationField::getViewModeFieldHtml()` to `viewModeFieldHtml()`.
- Renamed `craft\helpers\App::getEditionName()` to `editionName()`.
- Renamed `craft\helpers\App::getEditions()` to `editions()`.
- Renamed `craft\helpers\App::getMajorVersion()` to `majorVersion()`.
- Renamed `craft\helpers\App::getPhpConfigValueAsBool()` to `phpConfigValueAsBool()`.
- Renamed `craft\helpers\App::getPhpConfigValueInBytes()` to `phpConfigValueInBytes()`.
- Renamed `craft\helpers\ArrayHelper::getFirstKey()` to `firstKey()`.
- Renamed `craft\helpers\Assets::getFileTransferList()` to `fileTransferList()`.
- Renamed `craft\helpers\Assets::getPeriodList()` to `periodList()`.
- Renamed `craft\helpers\Assets::getTempFilePath()` to `tempFilePath()`.
- Renamed `craft\helpers\Assets::getUrlAppendix()` to `urlAppendix()`.
- Renamed `craft\helpers\ChartHelper::getCurrencyFormat()` to `currencyFormat()`.
- Renamed `craft\helpers\ChartHelper::getDateRanges()` =>dateRanges()
- Renamed `craft\helpers\ChartHelper::getDecimalFormat()` =>decimalFormat()
- Renamed `craft\helpers\ChartHelper::getFormats()` to `formats()`.
- Renamed `craft\helpers\ChartHelper::getPercentFormat()` to `percentFormat()`.
- Renamed `craft\helpers\ChartHelper::getShortDateFormats()` to `shortDateFormats()`.
- Renamed `craft\helpers\Cp::getAlerts()` to `alerts()`.
- Renamed `craft\helpers\ElementHelper::getEditableSiteIdsForElement()` to `editableSiteIdsForElement()`.
- Renamed `craft\helpers\ElementHelper::getSupportedSitesForElement()` to `supportedSitesForElement()`.
- Renamed `craft\helpers\Image::getImageSize()` to `imageSize()`.
- Renamed `craft\helpers\Image::getPngImageInfo()` to `pngImageInfo()`.
- Renamed `craft\helpers\Image::getWebSafeFormats()` to `webSafeFormats()`.
- Renamed `craft\helpers\Localization::getLocaleData()` to `localeData()`.
- Renamed `craft\helpers\MailerHelper::getAllMailerTransportTypes()` to `allMailerTransportTypes()`.
- Renamed `craft\helpers\Search::getMinWordLength()` to `minWordLength()`.
- Renamed `craft\helpers\Search::getStopWords()` to `stopWords()`.
- Renamed `craft\helpers\StringHelper::getAsciiCharMap()` to `asciiCharMap()`.
- Renamed `craft\helpers\StringHelper::getCharAt()` to `charAt()`.
- Renamed `craft\helpers\StringHelper::getEncoding()` to `encoding()`.
- Renamed `craft\helpers\StringHelper::uppercaseFirst()` to `upperCaseFirst()`.
- Renamed `craft\helpers\Template::getRaw()` to `raw()`.
- Renamed `craft\helpers\UrlHelper::getActionUrl()` to `actionUrl()`.
- Renamed `craft\helpers\UrlHelper::getCpUrl()` to `cpUrl()`.
- Renamed `craft\helpers\UrlHelper::getResourceUrl()` to `resourceUrl()`.
- Renamed `craft\helpers\UrlHelper::getSiteUrl()` to `siteUrl()`.
- Renamed `craft\helpers\UrlHelper::getUrl()` to `url()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithParams()` to `urlWithParams()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithProtocol()` to `urlWithProtocol()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithToken()` to `urlWithToken()`.
- Renamed `craft\mail\transportadapters\TransportAdapterInterface::getTransportConfig()` to `defineTransport()`, and it is now called at runtime when configuring the Mailer app component, rather than only when email settings are saved.
- Renamed `craft\models\AssetTransform::getTransformModes()` to `modes()`.
- Renamed `craft\services\Assets::renameAsset()` to `renameFile()`, and replaced its `$newFilename` argument with `$runValidation`.
- Renamed `craft\services\Config::omitScriptNameInUrls()` to `getOmitScriptNameInUrls()`.
- Renamed `craft\services\Config::usePathInfo()` to `getUsePathInfo()`.
- Renamed `craft\services\Resources::getResourcePath()` to `resolveResourcePath()`.
- Renamed `craft\volumes\AwsS3::getClient()` to `client()`.
- Renamed `craft\volumes\AwsS3::getStorageClasses()` to `storageClasses()`.
- Renamed `craft\volumes\GoogleCloud::getClient()` to `client()`.
- Renamed `craft\volumes\Rackspace::getClient()` to `client()`.
- Renamed `craft\web\assets\AppAsset` to `craft\web\assets\cp\CpAsset`.
- Renamed `craft\web\assets\D3Asset` to `craft\web\assets\d3\D3Asset`.
- Renamed `craft\web\assets\ElementResizeDetectorAsset` to `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.
- Renamed `craft\web\assets\GarnishAsset` to `craft\web\assets\garnish\GarnishAsset`.
- Renamed `craft\web\assets\JqueryPaymentAsset` to `craft\web\assets\jquerypayment\JqueryPaymentAsset`.
- Renamed `craft\web\assets\JqueryTouchEventsAsset` to `craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset`.
- Renamed `craft\web\assets\PicturefillAsset` to `craft\web\assets\picturefill\PicturefillAsset`.
- Renamed `craft\web\assets\SelectizeAsset` to `craft\web\assets\selectize\SelectizeAsset`.
- Renamed `craft\web\assets\TimepickerAsset` to `craft\web\assets\timepicker\TimepickerAsset`.
- Renamed `craft\web\assets\VelocityAsset` to `craft\web\assets\velocity\VelocityAsset`.
- Renamed `craft\web\twig\variables\CraftVariable::getLocale()` back to `locale()`.
- Moved `craft\volumes\VolumeInterface::getRootPath()` to `craft\volumes\LocalVolumeInterface::getRootPath()`.
- `craft\base\Element::getEditorHtml()` is no longer static, and no longer has an `$element` argument.
- `craft\base\Element::getElementRoute()` is no longer static, no longer has an `$element` argument, and has been renamed to `getRoute()`.
- `craft\base\Element::getElementQueryStatusCondition()` has been moved to `craft\elements\db\ElementQuery::statusCondition()`, and no longer has a `$query` argument.
- `craft\base\Element::getFieldsForElementQuery()` has been moved to `craft\elements\db\ElementQuery::customFields()`, and no longer has a `$query` argument.
- `craft\base\Element::getTableAttributeHtml()` is no longer static, and no longer has an `$element` argument.
- `craft\base\Element::onAfterMoveElementInStructure()` is no longer static, no longer has an `$element` argument, and has been renamed to `afterMoveInStructure()`.
- `craft\services\AssetIndexer::getIndexEntry()` now returns `null` if the index doesn’t exist, instead of `false`.
- `craft\services\Updates::getUnwritableFolders()` now returns folder paths without trailing slashes.
- `craft\web\Session::addJsFlash()` now has `$positon` and `$key` arguments.
- `craft\web\Session::getJsFlashes()` now returns an array of nested arrays, each defining the JS code, the position, and the key.
- `craft\web\View::getTwig()` no longer has `$loaderClass` or `$options` arguments.
- Renamed `craft.getAssets()` back to `craft.assets()`.
- Renamed `craft.getCategories()` back to `craft.categories()`.
- Renamed `craft.getEntries()` back to `craft.entries()`.
- Renamed `craft.getTags()` back to `craft.tags()`.
- Renamed `craft.getUsers()` back to `craft.users()`.
- Moved `numberFormat`, `percentFormat` and `currencyFormat` from `ChartHelper` to the `Craft.charts.BaseChart` default settings.
- Improved `Craft.charts.BaseChart` and `Craft.charts.Area` settings.
- Renamed `xAxisGridlines` setting to `xAxis.gridlines` and `yAxisGridlines` setting to `yAxis.gridlines` for the Area chart.
- Renamed `axis.y.show` setting to `yAxis.showAxis` for the Area chart.
- Renamed the `enablePlots` Area chart setting to `plots`.
- Renamed the `enableTips` Area chart setting to `tips`.
- All Craft and library dependencies that make remote calls use Craft's centralized Guzzle instance.
- Updated Yii to 2.0.10.
- Updated Yii 2 Debug Extension to 2.0.7.
- Updated Yii 2 SwiftMailer to 2.0.6.
- Updated Twig to 2.1.0.
- Updated Guzzle to 6.2.2.
- Updated D3 to 4.5.0.0.
- Updated Imagine to the new `pixelandtonic/imagine` fork at 0.6.3.2.
- Updated Garnish to 0.1.12.
- Updated Velocity to 1.4.2.
- Updated element-resize-detector.js to 1.1.10.
- Updated flysystem to 1.0.34.
- Updated qUnit to 2.1.1.
- Updated Redactor to 1.4.
- Craft no longer requires the mcrypt PHP extension.
- Improved the way the height of sidebars is calculated for panes with no tabs
- Moved Utilities nav item to keep Settings as the last item

### Deprecated
- The `getTranslations()` global Twig function has been deprecated. Use `craft.app.view.getTranslations()` instead.
- `craft\web\View::registerHiResCss()` has been deprecated. Use `registerCss()` instead, and type your own media selector.

### Removed
- Removed support for the `CRAFT_FRAMEWORK_PATH` PHP constant in the bootstrap script. It is now expected Yii is located alongside Craft and other dependencies in the `vendor/` folder.
- Removed support for the `environmentVariables` config setting. Use the `siteUrl` config setting in `config/general.php` to set the site URL, and override volume settings with `config/volumes.php`.
- Removed support for Yii 1-style controller action paths (e.g. `entries/saveEntry`), which were previously deprecated. Use the Yii 2 style instead (e.g. `entries/save-entry`).
- Removed the deprecated `activateAccountFailurePath` config setting.
- Removed the `appId` config setting.
- Removed the `collation` DB config setting.
- Removed the `initSQLs` DB config setting.
- Removed the `{% registerassetbundle %}` Twig tag. Use `{% do view.registerAssetBundle("class\\name") %}` instead.
- Removed the `{% registercssfile %}` Twig tag. Use `{% do view.registerCssFile("/url/to/file.css") %}` instead.
- Removed the `{% registercssresource %}` and `{% includecssresource %}` Twig tags.
- Removed the `{% registerhirescss %}` Twig tag. Use `{% css %}` instead, and type your own media selector.
- Removed the `{% registerjsfile %}` Twig tag. Use `{% do view.registerJsFile("/url/to/file.js") %}` instead.
- Removed the `{% registerjsresource %}` and `{% includejsresource %}` Twig tags.
- Removed the `{% endpaginate %}` Twig tag as it’s unnecessary.
- Removed the `childOf`, `childField`, `parentOf`, and `parentField` element query params. Use `relatedTo` instead.
- Removed the `depth` element query param. Use `level` instead.
- Removed the `name` tag query param. Use `title` instead.
- Removed support for passing `"name"` into the `orderBy` tag query param. Pass `"title"` instead.
- Removed the PEL library.
- Removed the PclZip library.
- Removed the SimplePie library.
- Removed support for EXIF data removal and automatic image rotating for servers without ImageMagick installed.
- Removed the automatic creation of `@craft/plugins/HANDLE` aliases for installed plugins.
- Removed `craft\base\Tool`.
- Removed `craft\base\ToolInterface`.
- Removed `craft\cache\FileCache`.
- Removed `craft\cache\adapters\GuzzleCacheAdapter`.
- Removed `craft\controllers\ToolsController`.
- Removed `craft\dates\DateTime`.
- Removed `craft\dates\DateInterval`.
- Removed `craft\db\DbBackup`.
- Removed `craft\enums\BaseEnum`.
- Removed `craft\errors\DbBackupException`.
- Removed `craft\errors\ErrorException`.
- Removed `craft\errors\InvalidateCacheException`.
- Removed `craft\events\CategoryEvent`.
- Removed `craft\events\BackupFailureEvent`.
- Removed `craft\events\DeleteUserEvent`.
- Removed `craft\events\EntryDeleteEvent`.
- Removed `craft\events\RestoreFailureEvent`.
- Removed `craft\events\UserEvent`.
- Removed `craft\helpers\Io`.
- Removed `craft\log\EmailTarget`.
- Removed `craft\models\AccountSettings`
- Removed `craft\models\Username`.
- Removed `craft\io\BaseIO`.
- Removed `craft\io\File`.
- Removed `craft\io\Folder`.
- Removed `craft\io\PclZip`.
- Removed `craft\io\Zip`.
- Removed `craft\io\ZipArchive`.
- Removed `craft\io\ZipInterface`.
- Removed `craft\models\LogEntry`.
- Removed `craft\models\Password`.
- Removed `craft\web\twig\StringTemplate`.
- Removed `craft\base\Component::getType()`. It was only really there for objects that implement `craft\base\MissingComponentInterface`, and now they have an `$expectedType` property.
- Removed `craft\base\ComponentInterface::classHandle()`.
- Removed `craft\base\Element::getContentFromPost()`.
- Removed `craft\base\Element::getSourceByKey()`.
- Removed `craft\base\Element::saveElement()`.
- Removed `craft\base\Element::setRawPostValueForField()`.
- Removed `craft\base\Field::getElementValue()`.
- Removed `craft\base\Field::setElementValue()`.
- Removed `craft\base\FieldInterface::validateValue()`. Fields should start implementing `getElementValidationRules()` if they want to customize how their values get validated.
- Removed `craft\base\Model::create()`.
- Removed `craft\base\Model::getAllErrors()`.
- Removed `craft\base\Model::populateModel()`.
- Removed `craft\base\Plugin::$releaseFeedUrl`. Plugins that wish to have update notifications should now set `$changelogUrl`.
- Removed `craft\base\Plugin::getClassesInSubpath()`.
- Removed `craft\base\Plugin::getFieldTypes()`.
- Removed `craft\base\Plugin::getVolumeTypes()`.
- Removed `craft\base\Plugin::getWidgetTypes()`.
- Removed `craft\base\PluginInterface::hasCpSection()`. Plugins that have a CP section should set the `$hasCpSection` property.
- Removed `craft\base\VolumeInterface::isLocal()`. Local volumes should implement `craft\base\LocalVolumeInterface` instead.
- Removed `craft\db\Command::addColumnAfter()`.
- Removed `craft\db\Command::addColumnBefore()`.
- Removed `craft\db\Command::addColumnFirst()`.
- Removed `craft\db\Migration::addColumnAfter()`.
- Removed `craft\db\Migration::addColumnBefore()`.
- Removed `craft\db\Migration::addColumnFirst()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnAfter()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnBefore()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnFirst()`.
- Removed `craft\db\Query::scalar()`.
- Removed `craft\db\Query::column()`.
- Removed `craft\elements\db\ElementQuery::configure()`.
- Removed `craft\elements\Tag::getName()`. Use the `title` property instead.
- Removed `craft\helpers\ArrayHelper::getFirstValue()`.
- Removed `craft\helpers\Json::removeComments()`.
- Removed `craft\helpers\MigrationHelper::makeElemental()`.
- Removed `craft\helpers\MigrationHelper::refresh()`.
- Removed `craft\helpers\MigrationHelper::restoreAllForeignKeysOnTable()`.
- Removed `craft\helpers\MigrationHelper::restoreAllIndexesOnTable()`.
- Removed `craft\helpers\MigrationHelper::restoreAllUniqueIndexesOnTable()`.
- Removed `craft\helpers\Update::cleanManifestFolderLine()`.
- Removed `craft\helpers\Update::isManifestLineAFolder()`.
- Removed `craft\services\Assets::deleteAssetsByIds()`.
- Removed `craft\services\Assets::deleteCategory()`.
- Removed `craft\services\Assets::deleteCategoryById()`.
- Removed `craft\services\Assets::findAsset()`.
- Removed `craft\services\Assets::findAssets()`.
- Removed `craft\services\Assets::getFilesByVolumeId()`.
- Removed `craft\services\Categories::saveCategory()`.
- Removed `craft\services\Content::validateContent()`.
- Removed `craft\services\Entries::deleteEntry()`.
- Removed `craft\services\Entries::deleteEntryById()`.
- Removed `craft\services\Entries::saveEntry()`.
- Removed `craft\services\Globals::deleteSetById()`.
- Removed `craft\services\Globals::saveContent()`.
- Removed `craft\services\Images::getIsImagickAtLeast()`.
- Removed `craft\services\Matrix::deleteBlockById()`.
- Removed `craft\services\Matrix::saveBlock()`.
- Removed `craft\services\Matrix::validateBlock()`.
- Removed `craft\services\Path::getMigrationsPath()`.
- Removed `craft\services\Path::getResourcesPath()`.
- Removed `craft\services\Plugins::call()`.
- Removed `craft\services\Plugins::callFirst()`.
- Removed `craft\services\SystemSettings::getCategoryTimeUpdated()`.
- Removed `craft\services\Tags::saveTag()`.
- Removed `craft\services\Updates::flushUpdateInfoFromCache()`.
- Removed `craft\services\Users::changePassword()`.
- Removed `craft\services\Users::deleteUser()`.
- Removed `craft\tools\*`.
- Removed `craft\web\Session::addJsResourceFlash()`.
- Removed `craft\web\Session::getJsResourceFlashes()`.
- Removed `craft\web\twig\variables\CraftVariable::getTimeZone()`.
- Removed `craft\web\twig\variables\Fields::createField()`.
- Removed `craft\web\View::registerCssResource()`.
- Removed `craft\web\View::registerJsResource()`.
- Removed the `$attribute` argument from `craft\base\ApplicationTrait::getInfo()`.
- Removed the `$except` argument from `craft\base\Element::getFieldValues()`.
- Removed the `$indexBy` argument from `craft\elements\User::getGroups()`.
- Removed the `$indexBy` argument from `craft\models\Section::getEntryTypes()`.
- Removed the `$indexBy` argument from `craft\services\AssetTransforms::getAllTransforms()`.
- Removed the `$indexBy` argument from `craft\services\Categories::getGroupSiteSettings()`.
- Removed the `$indexBy` argument from `craft\services\CategoryGroups::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\CategoryGroups::getEditableGroups()`.
- Removed the `$indexBy` argument from `craft\services\Dashboard::getAllWidgets()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getAllFields()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getFieldsByElementType()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getFieldsByGroupId()`.
- Removed the `$indexBy` argument from `craft\services\Globals::getAllSets()`.
- Removed the `$indexBy` argument from `craft\services\Globals::getEditableSets()`.
- Removed the `$indexBy` argument from `craft\services\Matrix::getBlockTypesByFieldId()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getAllSections()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getEditableSections()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getSectionSiteSettings()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getEntryTypesBySectionId()`.
- Removed the `$indexBy` argument from `craft\services\Sites::getAllSites()`.
- Removed the `$indexBy` argument from `craft\services\Sites::getEditableSites()`.
- Removed the `$indexBy` argument from `craft\services\Tags::getAllTagGroups()`.
- Removed the `$indexBy` argument from `craft\services\UserGroups::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\UserGroups::getGroupsByUserId()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getViewableVolumes()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getPublicVolumes()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getAllVolumes()`.
- Removed the `$newName` and `$after` arguments from `craft\db\Command::alterColumn()`.
- Removed the `$newName` and `$after` arguments from `craft\db\Migration::alterColumn()`.
- Removed the `$newName` and `$after` arguments from `craft\db\mysql\QueryBuilder::alterColumn()`.
- Removed the `$runValidation` argument from `craft\services\Content::saveContent()`.
- Removed the `$params` argument from `craft\helpers\Db::parseDateParam()`.
- Removed the `$params` argument from `craft\helpers\Db::parseParam()`.
- Removed the `beforeDeleteAsset`, `afterDeleteAsset`, `beforeSaveAsset` and `afterSaveAsset` events from `craft\services\Assets`.
- Removed the `beforeDeleteCategory`, `afterDeleteCategory`, `beforeSaveCategory` and `afterSaveCategory` events from `craft\services\Categories`.
- Removed the `beforeDeleteEntry`, `afterDeleteEntry`, `beforeSaveEntry` and `afterSaveEntry` events from `craft\services\Entry`.
- Removed the `beforeDeleteGlobalSet`, `beforeDeleteGlobalSet`, `beforeSaveGlobalContent` and `afterSaveGlobalContent` events from `craft\services\Globals`.
- Removed the `beforeDeleteUser`, `afterDeleteUser`, `beforeSaveUser`, `afterSaveUser`, `beforeSetPassword`, and `afterSetPassword` events from `craft\services\Users`.
- Removed the `backupFailure` event from `craft\db\Connection`.
- Removed the `beforeSaveTag` and `afterSaveTag` events from `craft\services\Tags`.
- Removed the `addRichTextLinkOptions` plugin hook. Custom Rich Text field link options should be registered using the `registerLinkOptions` event on `craft\fields\RichText` now.
- Removed the `addTwigExtension` plugin hook. Custom Twig extensions should be added by calling `Craft::$app->view->twig->addExtension()` directly.
- Removed the `addUserAdministrationOptions` plugin hook. Custom actions for the Edit User page should be registered using the `registerUserActions` event on `craft\controllers\UsersController` now.
- Removed the `defineAdditionalAssetTableAttributes`, `defineAdditionalCategoryTableAttributes`, `defineAdditionalEntryTableAttributes`, and `defineAdditionalUserTableAttributes` plugin hooks. Custom table attributes should be registered using the `registerTableAttributes` event on `craft\base\Element` or one of its subclasses now.
- Removed the `defineAssetActions`, `defineCategoryActions`, `defineEntryActions`, and `defineUserActions` plugin hooks. Custom element actions should be registered using the `registerActions` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getAssetTableAttributeHtml`, `getCategoryTableAttributeHtml`, `getEntryTableAttributeHtml`, and `getUserTableAttributeHtml` plugin hooks. Table attribute HTML should be overridden using the `setTableAttributeHtml` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getCpAlerts` plugin hook. Custom Control Panel alerts should be registered using the `registerAlerts` event on `craft\helpers\Cp` now.
- Removed the `getElementRoute` plugin hook. Element routes should be overridden using the `setRoute` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getFieldTypes` plugin hook. Custom field types should be registered using the `registerFieldTypes` event on `craft\services\Fields` now.
- Removed the `getMailTransportAdapters` plugin hook. Custom transport types should be registered using the `registerMailerTransportTypes` event on `craft\helpers\MailerHelper` now.
- Removed the `getResourcePath` plugin hook. Custom resource URIs should be resolved to file paths using the `resolveResourcePath` event on `craft\services\Resources` now.
- Removed the `getTableAttributesForSource` plugin hook.
- Removed the `getVolumeTypes` plugin hook. Custom volume types should be registered using the `registerVolumeTypes` event on `craft\services\Volumes` now.
- Removed the `getWidgetTypes` plugin hook. Custom widget types should be registered using the `registerWidgetTypes` event on `craft\services\Dashboard` now.
- Removed the `modifyAssetFilename` plugin hook. Asset filenames should be overridden using the `setFilename` event on `craft\helpers\Assets` now.
- Removed the `modifyAssetSortableAttributes`, `modifyCategorySortableAttributes`, `modifyEntrySortableAttributes`, and `modifyUserSortableAttributes` plugin hooks. Sortable attribute modifications should be made using the `registerSortableAttributes` event on `craft\base\Element` or one of its subclasses now.
- Removed the `modifyAssetSources`, `modifyCategorySources`, `modifyEntrySources`, and `modifyUserSources` plugin hooks. Element source modifications should be made using the `registerSources` event on `craft\base\Element` or one of its subclasses now.
- Removed the `modifyCpNav` plugin hook. Control Panel nav modifications should be made using the `registerCpNavItems` event on `craft\web\twig\variables\Cp` now.
- Removed the `registerCachePaths` plugin hook. Custom options for the Clear Caches tool (which can be set to callbacks now in addition to file paths) should be registered using the `registerCacheOptions` event on `craft\tools\ClearCaches` now.
- Removed the `registerCpRoutes` and `registerSiteRoutes` plugin hooks. Custom URL rules for the Control Panel and front-end site should be registered using the `registerCpUrlRules` and `registerSiteUrlRules` events on `craft\web\UrlManager` now.
- Removed the `registerEmailMessages` plugin hook. Custom email messages should be registered using the `registerMessages` event on `craft\services\EmailMessages` now.
- Removed the `registerUserPermissions` plugin hook. Custom user permissions should be registered using the `registerPermissions` event on `craft\services\UserPermissions` now.
- Removed the `craft\requirements` folder.  It is now a composer dependency.
- Removed the `Craft.charts.utils.applyShadowFilter()` JavaScript method.
- Removed the `Craft.charts.utils.arrayToDataTable()` JavaScript method.

### Fixed
- Fixed a bug where custom 503 templates weren’t rendering when Craft was in the middle of updating from an earlier version than 3.0.2933.
- Fixed a validation error that occurred when saving a field.
- Fixed a PHP error that occurred when using the `{% header %}` Twig tag.
- Fixed the Generate Pending Transforms task creation that occurs in `craft\services\Assets::getUrlForAsset()`.
- Fixed a PHP error that occurred when viewing entry revisions that were created before updating to Craft 3.
- Fixed a bug where brand new elements were not getting their `$uid` property set on them after getting saved.
- Fixed a bug where user activation emails that were sent immediately after creating the user account were getting an invalid activation URL.
- Fixed a PHP error that occurred when saving a Structure section entry with a new parent.
- Fixed a bug where entry titles were required for validation even if the entry type didn’t opt to show the title field.
- Fixed a bug where the `users/edit-user` controller action wasn’t respecting the passed in User object, if there was one, so validation errors weren’t getting reported.
- Fixed an error that occurred when the Control Panel attempted to start running background tasks if there were no tasks queued up.
- Fixed a bug where Recent Entries widgets would lose their “Locale” (now “Site”) setting values when upgrading from an older version of Craft.
- Fixed a bug where the Dashboard was allowing users to add widgets that were calling themselves unselectable.
- Fixed a bug where sessions table rows weren’t getting deleted after users logged out.
- Fixed a bug where the `fixedOrder` parameter wasn’t being respected for entry queries.
- Fixed a bug where plugin-supplied custom fields weren’t working.
- Fixed a PHP error that occurred when opening an element editor.
- Fixed a PHP error that occurred when re-saving a category group.
- Fixed a PHP error that occurred when using the `{% exit %}` tag with a specific status code.
- Fixed authorization error that occurred when editing an entry in a section that’s not enabled for the current site.
- Fixed a PHP error when using the `{% cache %}` tag.
- Fixed an error that occurred when clicking on an email message to edit it.
- Fixed an error that occurred when `craft\cache\FileCache::setValue()` was called and the destination folder already existed.
- Fixed support for the `testToEmailAddress` config setting.
- Fixed a bug where the `tasks/run-pending-tasks` controller action was requiring an authenticated session.
- Fixed a PHP error that occurred when saving a Recent Entries widget.
- Fixed a bug where Recent Entries widgets’ Site and Limit settings weren’t being validated correctly.
- Fixed a bug where widget settings errors were getting reported twice.
- Fixed a bug where console requests were only working when running the Pro edition.
- Fixed a bug where the Instructions setting within newly-created sub-fields in a Matrix field’s settings were getting marked as required.
- Fixed a bug where custom nested element sources registered by plugins were not getting Admin-defined custom table attributes.
- Fixed a bug where searching by an `"exact phrase"` wasn’t working.
- Fixed a bug where the Backup Database tool wasn’t downloading the DB backup if the “Download backup?” checkbox was checked.
- Fixed a bug where the requirements checker wasn’t taking into account MySQL/PostgreSQL installs running on non-standard ports.
- Fixed a bug where you’d get a fatal PHP error during an update if you didn’t meet one of Craft’s requirements.
- Fixed a bug where you’d get a database error when saving a private Assets Volume.
- Fixed a bug where linking to an entry or category from a Rich Text field wasn’t working.
- Fixed `Plugins::validateConfig()`’s nulls.
- Fixed a bug where JavaScript flashes weren’t getting registered on the subsequent page.
- Fixed a bug where `craft\db\Connection::columnExists()` wasn’t returning `true` if the column existed.

## 3.0.0-alpha.2948 - 2016-09-29

### Added
- Added `craft\i18n\Locale::getNumberPattern()`.
- Added `craft\web\Request::getValidatedBodyParam()`.
- Added `craft\services\Fields::deleteGroup()`.
- Added `craft\services\Fields::deleteLayout()`.
- Added `craft\services\Sections::deleteSection()`.
- Added `craft\services\Sections::deleteEntryType()`.
- Added `craft\services\Sites::deleteSite()`.
- Added `craft\services\Volumes::deleteVolume()`.
- Added `craft\base\SavableComponent::beforeSave()`.
- Added `craft\base\SavableComponent::afterSave()`.
- Added `craft\base\SavableComponent::beforeDelete()`.
- Added `craft\base\SavableComponent::afterDelete()`.
- Added the `beforeSave`, `afterSave`, `beforeDelete`, and `afterDelete` events to `craft\base\SavableComponent`.
- Added the `beforeSaveWidget`, `afterSaveWidget`, `beforeDeleteWidget`, and `afterDeleteWidget` events to `craft\services\Dashboard`.
- Added the `beforeSaveFieldLayout`, `beforeDeleteFieldLayout`, `afterDeleteFieldLayout`, `beforeSaveFieldGroup`, `afterSaveFieldGroup`, `beforeDeleteFieldGroup`, `afterDeleteFieldGroup`, `beforeSaveField`, `afterSaveField`, `beforeDeleteField`, and `afterDeleteField` events to `craft\services\Fields`.
- Added the `beforeLoadPlugins`, `beforeEnablePlugin`, `afterEnablePlugin`, `beforeDisablePlugin`, `afterDisablePlugin`, `beforeInstallPlugin`, `afterInstallPlugin`, `beforeUninstallPlugin`, `afterUninstallPlugin`, `beforeSavePluginSettings`, and `afterSavePluginSettings` events to `craft\services\Plugins`.
- Added the `beforeSaveRoute`, `afterSaveRoute`, `beforeDeleteRoute`, and `afterDeleteRoute` events to `craft\services\Routes`.
- Added the `beforeSearch` and `afterSearch` events to `craft\services\Search`.
- Added the `beforeSaveTask`, `afterSaveTask`, `beforeDeleteTask`, and `afterDeleteTask` events to `craft\services\Tasks`.
- Added the `loginFailure` event to `craft\controllers\UsersController` (replacing the like-named event on `craft\elements\User`).

### Changed
- Updated the Intl fallback data based on ICU 56.1. If you have any additional locale data files in `locales/`, you should [update them](https://github.com/craftcms/locales), too.
- Ported recent changes from Craft 2.
- The `beforeSaveAssetTransform` event on `craft\services\AssetTransforms` no longer supports an `$isValid` property.
- The `beforeDeleteAssetTransform` event on `craft\services\AssetTransforms` no longer supports a `$isValid` property.
- The `beforeSaveCategory` event on `craft\services\Categories` no longer supports an `$isValid` property.
- The `beforeDeleteGroup` event on `craft\services\Categories` no longer supports an `$isValid` property.
- The `beforeDeleteSection` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeSaveEntryType` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeDeleteEntryType` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeReorderSites` event on `craft\services\Sites` no longer supports an `$isValid` property.
- The `beforeMoveElement` event on `craft\services\Structures` no longer supports an `$isValid` property.
- The `beforeSaveTag` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeSaveGroup` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeDeleteGroup` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeSaveUserGroup` event on `craft\services\UserGroups` no longer supports an `$isValid` property.
- The `beforeDeleteUserGroup` event on `craft\services\UserGroups` no longer supports an `$isValid` property.
- The `beforeSaveVolume` event on `craft\services\Volumes` no longer supports an `$isValid` property.
- The `beforeDeleteVolume` event on `craft\services\Volumes` no longer supports an `$isValid` property.
- `craft\services\AssetTransforms::saveTransform()` now accepts a `$runValidation` argument.
- `craft\services\Categories::saveCategory()` now accepts a `$runValidation` argument.
- `craft\services\Entries::saveEntry()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveField()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveGroup()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveLayout()` now accepts a `$runValidation` argument.
- `craft\services\Tags::saveTag()` now accepts a `$runValidation` argument.
- `craft\services\Tags::saveTagGroup()` now accepts a `$runValidation` argument.
- `craft\services\UserGroups::saveGroup()` now accepts a `$runValidation` argument.
- `craft\services\Volumes::saveVolume()` now accepts a `$runValidation` argument.
- `craft\services\Sections::deleteEntryTypeById()` no longer accepts an array of entry type IDs.
- Renamed `craft\validators\DateTime` to `DateTimeValidator`.
- Renamed `craft\validators\Handle` to `HandleValidator`.
- Renamed `craft\validators\SingleSectionUri` to `SingleSectionUriValidator`.
- Renamed `craft\validators\SiteId` to `SiteIdValidator`.
- Renamed `craft\validators\Unique` to `UniqueValidator`.
- Renamed `craft\validators\Uri` to `UriValidator`.
- Renamed `craft\validators\Url` to `UrlValidator`.
- Renamed `craft\validators\UriFormat` to `UriFormatValidator`.
- Deleted the `craft\services\Volumes::VOLUME_INTERFACE` constant.
- Deleted the `craft\services\Tasks::TASK_INTERFACE` constant.
- Deleted the `craft\services\Fields::FIELD_INTERFACE` constant.
- Deleted the `craft\services\Elements::ELEMENT_INTERFACE` constant.
- Deleted the `craft\services\Elements::ACTION_INTERFACE` constant.
- Deleted the `craft\services\Dashboard::WIDGET_INTERFACE` constant.
- Deleted `craft\enums\ConfigCategory`. Its constants have been moved to `craft\services\Config` in the form of `CATEGORY_X`.

### Removed
- Removed the concept of “Safe Mode” from Twig, as it is no longer needed.
- Removed the `beforeInstall`, `afterInstall`, `beforeUpdate`, `afterUpdate`, `beforeUninstall`, and `afterUninstall` events from `craft\base\Plugin`.

### Fixed
- Fixed a bug where Craft couldn’t locate the DB backup file when attempting to restore it after a failed update.
- Fixed bug where element queries weren’t automatically getting ordered in their structure’s order, when applicable.
- Fixed a bug where changes to users’ Preferred Language settings weren’t sticking.
- Fixed a bug where `craft\i18n\I18N::getAppLocales()` and `getAppLocaleIds()` were only returning en-US.
- Fixed a bug where Craft was including a localized `0` character when formatting numbers as JPY, KRW, VND, XAF, XOF, and XPF currencies, if the Intl extension was not installed.
- Fixed a bug where Craft wasn’t displaying the “Can’t run Craft CMS” page correctly if it couldn’t connect to the database.
- Fixed a bug where updating to Craft 3.0.2933 or later could fail if the `backupDbOnUpdate` config setting was enabled.
- Fixed a bug where Matrix fields would lose their blocks when the owner element was saved outside of a normal Save Entry (etc.) POST request, e.g. when creating a new site.
- Fixed a PHP error that occurred when loading a category page on the front-end.
- Fixed a PHP error that occurred when calling the `site()` method on an element query.
- Fixed an infinite recursion bug when calling the `toArray()` method on an element query, or passing an element query object into `craft\helpers\ArrayHelper::toArray()`.
- Fixed a PHP error that occurred when deleting an entry.
- Fixed a bug where Craft wasn’t renaming content table columns when a field’s handle was renamed; instead it was just adding a new column based on the new handle.

## 3.0.0-alpha.2942 - 2016-09-21

### Added
- Added [Content Migrations](https://craftcms.com/news/craft-3-content-migrations) support.
- Added the `|timestamp` filter, for formatting a date as a user-friendly timestamp.
- Added the `|datetime` filter, for formatting a date with a localized date+time format.
- Added the `|time` filter, for formatting a date with a localized time format.
- Added `craft\web\Request::getAcceptsJson()`, which returns whether the HTTP request accepts a JSON response.
- Added `craft\web\Controller::requireAcceptsJson()`, which mandates that the HTTP request accepts a JSON response.
- Added `craft\i18n\Formatter::asTimestamp()`, for formatting a date as a user-friendly timestamp.
- Added `craft\helpers\StringHelper::ensureStartsWith()`, which will check if a string begins with a substring, and prepend it if it doesn’t.
- Added `craft\helpers\StringHelper::ensureEndsWith()`, which will check if a string ends with a substring, and append it if it doesn’t.
- Added the `craft\services\EntryRevisions::beforeRevertEntryToVersion` event.
- Added `craft\helpers\MigrationHelper::doesForeignKeyExist()`.
- Added `craft\helpers\MigrationHelper::doesIndexExist()`.

### Changed
- Improved the error message when a Migrate action was called with an invalid --plugin arg.
- Ported all recent changes from Craft 2.
- The `|date` filter can be passed `'short'`, `'medium'`, `'long'`, and `'full'`, which will format the date with a localized date format.
- The `{% cache %}` tag no longer includes the query string when storing the cache URL.
- `craft\helpers\DateTimeHelper::isToday()`, `isYesterday()`, `isThisYear()`, `isThisWeek()`, `isThisMonth()`, `isWithinLast()`, `isInThePast()`, and `timeAgoInWords()` now support all the same types of `$date` values as `craft\helpers\DateTimeHelper::toDateTime()`.
- `craft\helpers\MigrationHelper::dropForeignKeyIfExists()` and `dropIndexIfExists()` now allow the `$columns` argument to be a string.
- The `craft\services\EntryRevisions::beforeSaveDraft` event’s `$isValid` property is no longer respected. To prevent a draft from saving, set `$isValid = false` on the `craft\models\EntryDraft::beforeValidate` event.
- The `craft\services\EntryRevisions::beforePublishDraft` event’s `$isValid` property is no longer respected. To prevent a draft from publishing, set `$isValid = false` on the `craft\models\EntryDraft::beforeValidate` event.
- The `craft\services\EntryRevisions::beforeDeleteDraft` event’s `$isValid` property is no longer respected.
- The `craft\services\Updates::afterUpdateFail` event has been renamed to `updateFailure`.
- The `craft\mail\Mailer::sendEmailError` event has been renamed to `sendMailFailure`.
- Front-end forms that submit Ajax requests to Craft that expect a JSON response must start passing an `Accept: application/json` header. jQuery’s Ajax methods will do this if the `dataType` option is set to `'json'`.
- Renamed `craft\helpers\DateTimeHelper::wasYesterday()` to `isYesterday()`.
- Renamed `craft\helpers\DateTimeHelper::wasWithinLast()` to `isWithinLast()`.
- Renamed `craft\helpers\DateTimeHelper::wasInThePast()` to `isInThePast()`.
- The `$day` argument of `craft\i18n\Locale::getWeekDayName()` should now be 0-6 instead of 1-7, where `0` represents Sunday.

### Deprecated
- Deprecated the `round()` Twig function. Use the `|round` filter instead.
- Deprecated `craft\craft\app\dates\DateTime::__toString()`. Use `format('Y-m-d')` instead.
- Deprecated `craft\craft\app\dates\DateTime::atom()`. Use `format(DateTime::ATOM)` instead.
- Deprecated `craft\craft\app\dates\DateTime::cookie()`. Use `format(DateTime::COOKIE)` instead.
- Deprecated `craft\craft\app\dates\DateTime::iso8601()`. Use `format(DateTime::ISO8601)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc822()`. Use `format(DateTime::RFC822)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc850()`. Use `format(DateTime::RFC850)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc1036()`. Use `format(DateTime::RFC1036)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc1123()`. Use `format(DateTime::RFC1123)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc2822()`. Use `format(DateTime::RFC2822)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rfc3339()`. Use `format(DateTime::RFC3339)` instead.
- Deprecated `craft\craft\app\dates\DateTime::rss()`. Use `format(DateTime::RSS)` instead.
- Deprecated `craft\craft\app\dates\DateTime::w3c()`. Use `format(DateTime::W3C)` instead.
- Deprecated `craft\craft\app\dates\DateTime::w3cDate()`. Use `format('Y-m-d')` instead.
- Deprecated `craft\craft\app\dates\DateTime::mySqlDateTime()`. Use `format('Y-m-d H:i:s')` instead.
- Deprecated `craft\craft\app\dates\DateTime::localeDate()`. Use `Craft::$app->formatter->asDate($date, 'short')` instead.
- Deprecated `craft\craft\app\dates\DateTime::localeTime()`. Use `Craft::$app->formatter->asTime($date, 'short')` instead.
- Deprecated `craft\craft\app\dates\DateTime::year()`. Use `format('Y')` instead.
- Deprecated `craft\craft\app\dates\DateTime::month()`. Use `format('n')` instead.
- Deprecated `craft\craft\app\dates\DateTime::day()`. Use `format('j')` instead.
- Deprecated `craft\craft\app\dates\DateTime::nice()`. Use `Craft::$app->formatter->asDatetime($date)` instead.
- Deprecated `craft\craft\app\dates\DateTime::uiTimestamp()`. Use `Craft::$app->formatter->asTimestamp($date, 'short')` instead.

### Removed
- Removed the static `$plugin` property on `craft\base\Plugin`.
- Removed `craft\web\Controller::requireAjaxRequest()`. Use `requireAcceptsJson()` instead.
- Removed `craft\helpers\DateTimeHelper::fromString()`. Use `DateTimeHelper::toDateTime($date)->getTimestamp()` instead.
- Removed `craft\helpers\DateTimeHelper::uiTimestamp()`. Use `Craft::$app->formatter->asTimestamp($date)` instead.
- Removed `craft\helpers\DateTimeHelper::timeAgoInWords()`. Use `Craft::$app->formatter->asRelativeTime($date)` instead.
- Removed `craft\helpers\DateTimeHelper::nice()`. Use `Craft::$app->formatter->asDatetime($date)` instead.
- Removed `craft\helpers\DateTimeHelper::niceShort()`.
- Removed the `craft\craft\app\dates\DateTime::W3C_DATE` constant.
- Removed the `craft\craft\app\dates\DateTime::MYSQL_DATETIME` constant.
- Removed the `craft\craft\app\dates\DateTime::UTC constant`.
- Removed the `craft\craft\app\dates\DateTime::DATEFIELD_24HOUR` constant.
- Removed the `craft\craft\app\dates\DateTime::DATEFIELD_12HOUR` constant.

### Fixed
- Fixed PHP error when `Craft::$app->getIsUpdating()` was called in a console request.
- Fixed a bug that occurred when applying a coupon to a Craft Client/Pro purchase.
- Fixed bug where `craft\helpers\StringHelper::startsWith()` and `endsWith()` were returning strings instead of booleans.
- Fixed a bug where the `$isNew` property on entry draft events wasn’t getting set correctly.
- Fixed the default email settings.
- Fixed a PHP error that occurred when installing/updating/uninstalling a plugin that didn’t have a `migrations/` folder.
- Fixed a bug where files that were registered via an asset bundle weren’t getting included when calling `craft\web\View::getHeadHtml()`.
- Fixed a bug where localized datepicker resources weren’t getting included properly.
- Fixed a bug where matrixblocks.ownerSiteId was getting set to NOT NULL for new Craft installs, resulting in MySQL errors when saving non-localized Matrix fields.
- Fixed a PHP error that occurred when saving a category.
- Fixed a JavaScript error in `Craft.compare()` that affected Live Preview.
- Fixed a bug where changes to tags’ titles made from the inline editor within Tags fields would not be reflected in the Tags field, for newly-selected tags.

## 3.0.0-alpha.2939 - 2016-09-15

### Fixed
- Fixed a bug where `craft\helpers\Io::copyFolder()` was not working.
- Fixed an "Entry is missing its type ID" bug when saving a entry without multiple entry types.
- Fixed a PHP error that occurred when saving a Matrix field's settings on a Craft install with only one Site.
- Fixed a couple places where Craft wasn't enforcing the PHP 5.6 requirement.
- Fixed a bug where Craft would attempt to create a 'migrations' folder within plugins' folders when checking if they have any new migrations.
- Fixed a bug where ordering element queries based on custom field values wasn't working if the orderBy param was set via the `orderBy()` method.
- Fixed a MySQL error that occurred when updating Craft when any plugins were installed.
- Fixed a bug where element queries were not always respecting the 'limit' and 'offset' params when the 'search' param was applied.
- Fixed a bug where element queries were non respecting the 'orderBy' param if it was set to "score" and the 'search' param was applied.

## 3.0.0-alpha.2937 - 2016-09-13

### Changed
- It is now possible to override 'assetManager', 'cache', 'db', 'mailer', 'locale', 'formatter', and 'log' application components' configurations from `config/app.php`.
- Renamed `craft\services\Plugins::getPluginInfo()` to `getAllPluginInfo()`.
- `craft\elements\MatrixBlock::getType()` now throws an `yii\base\InvalidConfigException` if the block type cannot be determined, rather than returning `null`.

### Fixed
- Fixed a MySQL error that occurred when updating a Craft install to Craft Dev 3.0.2933, if any user permissions had been assigned.
- Fixed an "Invalid entry type ID" error that occurred when creating a new entry.
- Fixed a bug where fields weren't passing validation, with no visible validation errors, when there was only one Site.
- Fixed a bug where elements' `afterSave()` functions and fields' `afterElementSave()` functions weren't getting called when saving new elements, which prevented Matrix and relationship fields' values from getting saved.
- Fixed a PHP error that occurred when executing `migrate` commands from the CLI.
- Fixed a bug where `config/db.php` was not getting validated before attempting to establish a DB connection.

## 3.0.0-alpha.2933 - 2016-09-12

### Added
- [Multi-site management!](https://craftcms.com/news/craft-3-multi-site)
- Locale permissions have been replaced by Site permissions.
- Sections and category groups can now choose to have URLs on a per-site basis.
- Field types that have a column in the content table now get a "Translation Method" setting (replacing the "This field is translatable" setting), with the options "Not translatable", "Translate for each language", "Translate for each site", and "Custom…".
- Matrix fields now have a "Manage blocks on a per-site basis" setting.
- Relationship fields now have a "Manage relations on a per-site basis" setting.
- Added support for a `CRAFT_SITE` PHP constant, which can be set to the handle of the current site.
- Element queries now support 'site', 'siteId', and 'enabledForSite' criteria params.
- Added `craft\services\Sites`.
- Added `craft\validators\SiteId`.
- Added `Craft::$app->getIsMultiSite()`, replacing `getIsLocalized()`.
- Added `Craft::$app->getSystemUid()`, replacing `getSiteUid()`.
- Added `craft\i18n\I18N::getSiteLanguages()`, replacing `getSiteLocaleIds()`.
- Added `craft\elements\User::getPreferredLanguage()`, replacing `getPreferredLocale()`.
- Added `craft\helpers\ElementHelper::getSupportedSitesForElement()`
- Added `craft\helpers\ElementHelper::getEditableSiteIdsForElement()`, replacing `getEditableLocaleIdsForElement()`
- Added `ElementInterface::getSupportedSites()`, replacing `getLocales()`.
- Added `Craft.isMultiSite` for JavaScript, replacing `Craft.isLocalized`.
- Added `Craft.systemUid` for JavaScript, replacing `Craft.siteUid`.
- Added `Craft.UriFormatGenerator` for JavaScript, replacing `Craft.EntryUrlFormatGenerator`.
- Plugins now have a static `$plugin` property, which gets set to the instance of the plugin, if it has been initialized.

### Changed
- Routes can now be stored on a per-site basis, rather than per-locale.
- Renamed all "URL Format" things to "URI Format", in the Control Panel UI and in the code.
- Structure sections and category groups no longer have Nested URL Format settings. (It's still possible to achieve the same result with a single URI Format setting.)
- Fields that are translatable now have a chat bubble icon, replacing the locale ID badge.
- `craft\elements\Category::getGroup()` now throws an `yii\base\InvalidConfigException` if the category group type cannot be determined, rather than returning `null`.
- Renamed `craft\base\PluginTrait::$releasesFeedUrl` to `$releaseFeedUrl`.
- `craft\mail\Message::setTo()` will now set a 'user' variable on the message, if it was passed a User model.
- `Craft.ui.createSelect()` and `createLightswitch()` now initialize the `Craft.FieldToggle` class on the returned elements.

### Fixed
- Fixed a PHP error that occurred if a Position Select field had a blank first option.
- Fixed a bug where users without Control Panel access weren't getting redirected correctly after account activation.
- Fixed a JavaScript error that occurred when calling `Craft.ui.createCheckboxField()`.

## 3.0.0-alpha.2928 - 2016-08-31

### Added
- Table fields now have a "Lightswitch" column type option.
- Added a `|unique` Twig filter for filtering out duplicate values in an array.
- Added `craft\craft\app\validators\Unique`, which can be used as a drop-in replacement for `craft\yii\validators\UniqueValidator`, but with better support for validating a model's attributes based on an associated record's attributes.
- Added `Craft.ui.createTextarea()`, `createTextareaField()`, `createLightswitch()`, and `createLightswitchField()`.
- Added a `lightswitch` column type option to editable tables.
- Text columns within editable tables can now define placeholder text.
- Editable table cell values can now be specified as an array with `value` and `hasErrors` (optional boolean) keys. Cells where `hasErrors` is set to `true` will get a red border.

### Changed
- Ported all recent changes from Craft 2.
- `craft\elements\Entry::getSection()` and `getType()` now throw an `yii\base\InvalidConfigException` if the section/entry type cannot be determined, rather than returning `null`.

### Fixed
- Fixed bug where `&nbsp;` was getting output for editable table headings that should have been empty.
- Fixed a bug where xregexp-all.js was not getting included properly when the `useCompressedJs` config setting was set to `true` (as it is by default).

## 3.0.0-alpha.2918 - 2016-08-25

### Added
- Added `$flavor` and `$inlineOnly` arguments to the `|markdown` filter, making it possible to choose which Markdown flavor should be used, and whether it should parse paragraphs or treat the whole thing as inline elements.
- Added `Craft::$app->getIsUpdating()`, which returns `true` if Craft is undergoing an application update.
- Added the beforeSaveGlobalSet, afterSaveGlobalSet, beforeDeleteGlobalSet, and afterDeleteGlobalSet events to `craft\services\Globals`.
- Added the beforeSaveUserGroup, afterSaveUserGroup, beforeDeleteUserGroup and afterDeleteUserGroup events to `craft\services\UserGroups`.
- Added the beforeSaveAssetTransform, afterSaveAssetTransform, beforeDeleteAssetTransform, and afterDeleteAssetTransform events to `craft\services\AssetTransforms`.
- Added the beforeDeleteEntryType and afterDeleteEntryType events to `craft\services\Sections`.
- Added the afterInit event to `craft\web\Application` and `craft\console\Application`.
- Added the loginFailure event to `craft\elements\User`.

### Changed
- All before/after-save events now have an `$isNew` property that identifies whether the object of the event is brand new or not.
- The `_includes/forms/field` include template now parses the passed in `warning` variable as an inline Markdown string, if it exists.
- The `_includes/forms/editableTable` include template now supports a `staticRows` variable, which if defined and set to `true` will prevent row CRUD operations in the resulting table.
- The `_includes/forms/editableTable` include template now supports a `heading` column type, which can be used in conjunction with `staticRows` to define row headings.
- The `_includes/forms/editableTable` include template now supports an `info` property on column configs, which will add an info button to the column header which reveas instruction text for the column.
- Renamed some event classes to be more consistent.

### Fixed
- Fixed a bug where it was not possible to replace a file from the Assets page.
- Fixed a bug where asset volumes' Cache Duration settings were not being respected.
- Fixed a bug that affected asset indexing reliability.
- Fixed a PHP error that occurred when rendering a Twig template from a console request.
- Fixed a bug where setting custom field attributes on an element query would prevent the query from executing.
- Fixed a bug where plugin Control Panel nav items were getting the full plugin class name in the URL instead of just handle.

## 3.0.0-alpha.2915 - 2016-08-17

### Added
- User photos are now assets. (Note that the `storage/userphotos/` folder must be manually moved to a publicly accessible location, and the User Photos asset volume’s settings must be updated accordingly, for user photos to work properly.)
- Added `craft\elements\User::getPhoto()`, which returns the user’s photo asset, if they have one.
- Added `craft\helpers\StringHelper::randomStringWithChars()`.
- Added the `beforeSaveContent` event to `craft\services\Content`.
- Added the `beforeSaveDraft` and `beforePublishDraft` events to `craft\services\EntryRevisions`.
- Added the `beforeSaveGroup`, `afterSaveGroup`, `beforeDeleteGroup`, and `afterDeleteGroup` events to `craft\services\Tags`.
- Added the `beforeSaveVolume`, `afterSaveVolume`, `beforeDeleteVolume`, and `afterDeleteVolume` events to `craft\services\Volumes`.
- Added the `redirectInput()` global function, which simplifies the code required to create `redirect` params.

### Changed
- All `redirect` params must now be hashed using the `|hash` filter (e.g. `<input type="hidden" name="redirect" value="{{ 'foo'|hash }}">`). A new `redirectInput()` global function makes this a little easier (e.g. `{{ redirectInput('foo') }}`).
- Ported recent changes from Craft 2.
- The `getCsrfInput()` global function has been renamed to `csrfInput()`. (getCsrfInput() still works but produces a deprecation error.)
- The `|hash` filter no longer requires a `$key` argument.
- Brought back `getId()`, `getName()`, and `getNativeName()` methods to Locale, so Craft 2 templates don’t need to be updated right away, although they will produce a deprecation error.

### Deprecated
- `craft\elements\User::getPhotoUrl()` is now deprecated in favor of `user.getPhoto.getUrl()`.

### Fixed
- Fixed a bug where `craft\helpers\StringHelper::toTitleCase()` was returning all-lowercase strings.
- Fixed a typo where `craft\helpers\Json::encodeIfJson()` should have been named `decodeIfJson()`.
- Fixed a bug where element queries were not respecting the `offset` property.
- Fixed a MySQL error that occurred when querying users with the `can` property.
- Fixed a MySQL error that occurred when executing an element query that had custom query params.
- Fixed a PHP error that occurred when rendering templates with a `{% paginate %}` tag. (Note that you may need to delete your `storage/runtime/compiled_templates/` folder.)
- Fixed a PHP warning that occurred when using `craft\services\Feeds` on a server running PHP 7.
- Fixed a few bugs related to saving elements on localized sites.
- Fixed tag group saving and deleting.
- Fixed a bug where all global sets were listed in the sidebar of the Globals page, rather than just the ones the user had permission to edit.
- Fixed a bug where nested Structure section entries were not showing the parent entry on their edit pages.
- Fixed a bug where nested categories were not showing the parent category on their edit pages.
- Fixed a MySQL error that occurred when saving a nested Structure section entry.
- Fixed a MySQL error that occurred when saving a nested category.
- Fixed a bug where User elements returned by `craft\services\Users::getUserByUsernameOrEmail()` and `getUserByEmail()` were missing their content.
- Fixed a bug where user info like Last Login Date was not getting updated when users logged in.
- Fixed a bug where email messages were not respecting the recipient user’s preferred language.
- Fixed a PHP error that occurred when saving an element with an invalid field, if the field’s value couldn’t be converted to a string.
- Fixed a PHP error that occurred when sharing an entry or category with a tokenized share URL.
- Fixed a bug where the `dateCreated`, `dateUpdated`, and `uid` attributes on new active record objects would not necessarily reflect the values in the database, after saving the record for the first time.
- Fixed a bug that broke cookie-based authentication.
- Fixed a bug where temporary asset uploads were getting deleted when clearing data caches.
- Fixed a JavaScript error that occurred in the Control Panel.

### Security
- `craft\services\Security::hashData` and `validateData()` no longer require a `$key` argument.

## 3.0.0-alpha.2910 - 2016-08-04

### Added
- Ported all the changes from Craft 2.
- Craft 3 now requires PHP 5.6 or later.
- Craft 3 now requires MySQL 5.5 or later.
- The debug toolbar now has the Craft “C” logo rather than the Yii logo.
- `craft.app` now points to the same Application object that `Craft::$app` references in PHP, giving templates access to literally all of the service methods, etc., that PHP code has access to.
- Added the `is missing` Twig test for determining if an object implements `craft\base\MissingComponentInterface`.
- Added a new `uid` parameter to element queries, for fetching elements by their UID.
- Elements’ UIDs are now fetched in element queries, and stored as a `uid` property on the resulting elements.
- Added support for Reduced Redundancy Storage and Infrequent Access Storage for Amazon S3 asset volumes.
- Added the beforeCreateBackup event to `craft\db\Connection`.
- Added the beforeSaveGroup and afterSaveGroup events to `craft\services\Categories`.
- Documented all magic properties and methods in class doc blocks, so IDEs should always know what’s available.
- Added `craft\db\mysql\Schema::TYPE_TINYTEXT` and `TYPE_LONGTEXT`, and added custom support for them in `craft\db\mysql\ColumnSchemaBuilder`.
- Added `craft\db\Migration::tinyText()`, `mediumText()`, `longText()`, `enum()`, and `uid()` column schema definition helpers.
- Added `craft\errors\DbBackupException`.
- Added `craft\errors\DbUpdateException`.
- Added `craft\errors\DownloadPackageException`.
- Added `craft\errors\FilePermissionsException`.
- Added `craft\errors\InvalidateCacheException`.
- Added `craft\errors\MinimumRequirementException`.
- Added `craft\errors\MissingFileException`.
- Added `craft\errors\UnpackPackageException`.
- Added `craft\errors\ValidatePackageException`.
- Added `craft\helpers\Localization::getLocaleData()`.
- Added `craft\volumes\Temp`.
- Added a `$format` argument to `craft\i18n\Locale::getDateFormat()`, `getTimeFormat()`, and `getDateTimeFormat()`, for specifying whether the returned string should be in the ICU, PHP, or jQuery UI date/time format.

### Changed
- Craft now checks for a plugin.json file within plugin directories, rather than config.json.
- Plugin-based email messages now get translated using the plugin handle as the translation category, rather than `'app'`.
- “owner” is now a reserved field handle.
- Made lots of improvements to exceptions.
- Improved the handling of invalid locale IDs. (Work in progress, though.)
- Renamed lots of class and method names to be more in-line with Yii conventions.
- Renamed the `craft\i18n\Locale::FORMAT_*` constants to `LENGTH_*`.
- Renamed `craft\web\View::includeTranslations()` to `registerTranslations()`, and it now accepts two arguments: `$category` and `$messages`.
- `craft\db\Command::createTable()` and `craft\db\Migration::createTable()` no longer accept `$addIdColumn` or `$addAuditColumn` arguments. The `id`, `dateCreated`, `dateUpdated`, and `uid` columns are now expected to be included in the `$columns` array.
- `craft\web\View::clearJsBuffer()` no longer returns an array.
- Renamed `Craft.locale` to `Craft.language` in JS.
- `Craft.t()` in JS now accepts a category argument, like `Craft::t()` in PHP.
- Updated Yii to 2.0.9.
- Updated the Yii 2 Debug module to 2.0.6.
- Updated the Yii 2 Nested Sets module to 0.9.0.
- Updated Swiftmailer to 5.4.3.
- Updated SimplePie to 1.4.2.
- Updated Guzzle to 6.2.1.
- Updated Twig to 1.24.1.
- Updated PEL to 0.9.4.
- Updated Stringy to 2.3.2.
- Updated Imagine to 0.6.3.
- Updated Flysystem to 1.0.26.
- Updated PclZip to 2.8.2.
- Updated jQuery Timepicker to 1.11.2.
- Updated qUnit to 2.0.1.
- Updated Redactor II to 1.2.5.
- Updated selectize.js to 0.12.2.
- Updated element-resize-detector.js to 1.1.6.
- Updated jQuery UI to 1.12.0.
- Updated Picturefill to 3.0.2.
- Updated xRegExp to 3.1.1.

### Deprecated
- Deprecated `craft.categoryGroups`.
- Deprecated `craft.config`.
- Deprecated `craft.deprecator`.
- Deprecated `craft.elementIndexes`.
- Deprecated `craft.entryRevisions`.
- Deprecated `craft.feeds`.
- Deprecated `craft.fields`.
- Deprecated `craft.globals`.
- Deprecated `craft.i18n`.
- Deprecated `craft.request`.
- Deprecated `craft.sections`.
- Deprecated `craft.systemSettings`.
- Deprecated `craft.tasks`.
- Deprecated `craft.session`.
- Deprecated `craft.userGroups`.
- Deprecated `craft.emailMessages`.
- Deprecated `craft.userPermissions`.

### Removed
- Removed the `overridePhpSessionLocation` config setting.
- Removed `craft.elements`.
- Removed the `{% includeTranslations %}` tag.
- Removed the `craft\db\InstallMigration` class.
- Removed the `craft\web\twig\variables\ComponentInfo` class.

### Fixed
- Fixed a lot of bugs. OK?

## 3.0.0-alpha.2687 - 2015-08-20

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the `showBetaUpdates` config setting.
- Added `craft\helpers\StringHelper::delimit()`.
- Added `craft\helpers\StringHelper::indexOf()`.
- Added `craft\helpers\StringHelper::indexOfLast()`.
- Added `craft\helpers\StringHelper::lines()`.

### Changed
- Image transforms’ Quality settings are now represented as a dropdown input in the Control Panel, with the options “Auto”, “Low”, “Medium”, “High”, “Very High (Recommended)”, and “Maximum”.
- It is now possible to access `Craft.Grid` objects from their container elements, via `.data('grid')`.
- It is now possible to access `Craft.BaseElementSelectInput` objects from their container elements, via `.data('elementSelect')`.
- Craft now displays helpful errors after failed asset uploads.

### Fixed
- Fixed an error that occurred when backing up the database.
- Fixed a bug where plugin settings were getting HTML-encoded.
- Fixed an error that occurred when deleting a field group.
- Fixed a MySQL error that occurred when saving a category group without URLs.
- Fixed an error that occurred when fields existed with invalid types, if there was no corresponding column in the database.
- Fixed a MySQL error that occurred when saving user groups.
- Fixed a bug where user group names and handles were not being validated for uniqueness.
- Fixed a bug where route params registered with `craft\web\UrlManager::setRouteParams()` were not being passed to the template, for requests that ultimately get routed to a template.
- Fixed a bug where no Admin user would be created when installing Craft with the `useEmailAsUsername` config setting set to `true`.
- Fixed a bug where Title fields were not being validated.
- Fixed a bug where custom fields’ validation errors were not being reported after attempting to save elements.
- Fixed an error that occurred when attempting to save an element with a required Rich Text field that had no value.
- Fixed a bug where PHP could easily run out of memory when unzipping large zip files.
- Fixed a bug where `craft\helpers\MigrationHelper` would forget that it had dropped a table after `dropTable()` was called.
- Fixed an error that would occur if another error occurred during a MySQL transaction that included savepoints, and the savepoints had already been implicitly committed.

## 3.0.0-alpha.2681 - 2015-07-22

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the `enableCsrfCookie` config setting, which determines whether CSRF tokens should be saved in cookies. Defaults to `true`. CSRF tokens will be stored in the PHP session if set to `false`.
- The Content model has been removed. Elements now have `craft\behaviors\ContentBehavior`s directly. Because speed!
- Added `craft\base\ElementTrait::$contentId` for storing the content row’s ID.
- Added `craft\base\ElementTrait::$title` for storing the element’s title, replacing `craft\base\ElementInterface::getTitle()`.
- Added `craft\base\ElementInterface::getFieldValues()`, replacing `getContent()`.
- Added `craft\base\ElementInterface::setFieldValues()`, replacing `setContent()`.
- Added `craft\base\ElementInterface::setFieldValuesFromPost()`, replacing `setContentFromPost()`.
- Added `craft\base\ElementInterface::setRawPostValueForField()`, replacing `setRawPostContent()`.
- Added `craft\base\ElementInterface::setFieldValue()`.
- All `$value` arguments on Field methods are now set to the prepared field value, with the sole exception of `prepareValue()`.
- Added `craft\base\FieldInterface::prepareValueForDb()`, giving fields direct control over how their values should be saved to the database, without affecting the value stored on the element. (This replaces the protected `prepareValueBeforeSave()` method.)
- Added protected `craft\base\Field::isValueEmpty()` which aids `craft\base\Field::validateValue()` in required-field validation.
- Added `craft\helpers\DateTimeHelper::normalizeTimeZone()`.
- Added `craft.getTimeZone()` for Control Panel JavaScript.
- Added the `craft\craft\app\base\Savable` interface. Objects that implement it have control over how `craft\helpers\DbHelper::prepareValueForDb()` prepares them to be saved to the database.
- Added `craft\craft\app\web\View::getBodyHtml()`, replacing `getBodyBeginHtml()` and `getBodyEndHtml()`.

### Changed
- Updated Yii to 2.0.5.
- Element queries are no longer limited to 100 results by default.
- `craft\elements\db\ElementQuery::count()` now returns the total cached results, if set.
- `craft\base\FieldInterface::validateValue()` is now responsible for required-field validation (and a basic is-empty check is included in `craft\base\Field`).
- `craft\base\FieldInterface::validateValue()` no longer needs to return `true` if the value passed validation.
- `craft\dates\DateTimeHelper::toDateTime()`’s `$timezone` argument has been replaced with `$assumeSystemTimeZone`. If set to `true` and if `$value` doesn’t have an explicit time zone, the method will use the system’s time zone. Otherwise UTC will be used. (Defaults to `false`.)
- `craft\dates\DateTimeHelper::toDateTime()` now checks for a `timezone` key when `$value` is in the date/time-picker array format.
- `craft\dates\DateTimeHelper::toDateTime()` now returns `false` instead of `null` when an array without `'date'` or `'time'` keys is passed in.
- `craft\dates\DateTime::createFromFormat()` no longer sets the `$timezone` argument if left null, matching the base `DateTime` class’s behavior.
- Date/time-pickers in the CP now explicitly declare that they are using the system time zone in their post data.
- Nested database transactions will now set savepoints within the master transaction, as MySQL doesn’t support nested transactions.
- `PluginClassName::getInstance()` will now return the singular instance of the plugin class.

### Removed
- Removed `craft\dates\DateTime::format()`, so it no longer has a `$timezone` argument and automatically sets the time zone on the `DateTime` object.

### Fixed
- Fixed many, many bugs.

## 3.0.0-alpha.2671 - 2015-06-18

### Added
- Ported all new features and improvements that were introduced in [Craft 2.4](http://buildwithcraft.com/updates#build2664).
- The codebase now follows the [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style.
- Added support for `config/app.php`, which can return an array that will be merged with Craft’s core application config array.
- Craft now looks for translation files at `translations/locale-ID/category.php`, where `category` can either be `'app'`, `'site'`, `'yii'`, or a plugin’s handle.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the `'site'` category, to prevent translation conflicts with Craft’s own Control Panel translations.
- Craft now uses SwiftMailer to send emails.
- Added the ability for plugins to provide custom SwiftMailer transport options.
- Added `craft\helpers\DateTimeHelper::toIso8601()`, for converting a date (or date-formatted string) into an ISO-8601-formatted string.
- Added `craft\helpers\DateTimeHelper::isIso8601()`, for determining if a given variable is an ISO-8601-formatted string.
- Added `craft\helpers\DateTimeHelper::translateDate()`, for translating month and weekday names from English to the active application language.
- Added `craft\helpers\DbHeleper::prepareDateForDb()`, which replaces the old `craft\helpers\DateTimeHelper::formatTimeForDb()`.
- Added `craft\behaviors\FieldLayoutBehavior`, which replaces `craft\base\FieldLayoutTrait`, enabling a single class to have more than one field layout associated with it.
- The localized date format used by JavaScript date pickers is now available from `Craft.datepickerFormat`.
- Added the JavaScript method, `Craft.formatDate()`, for formatting a date (or date-formatted string) into a string using the same localized date format used by JavaScript date pickers.

### Changed
- The `translationDebugOutput` config setting will now wrap strings with `@` characters if the category is `'app'`, `$` if the category is `'site'`, and `%` for anything else.
- Web requests are now logged to `storage/logs/web.log`.
- Web requests that result in 404 errors are now logged to `storage/logs/web-404s.log`.
- Console requests are now logged to `storage/logs/console.log`.
- Template error handling now works similarly to how it does in Craft 2 when Craft is running in Dev Mode, where the template source is shown in the error view.
- Twig class names now link to their respective class reference URLs in the error view’s stack trace.
- The `registercss`, `registerhirescss`, and `registerjs` tags can now be used as tag pairs.
- The `registerassetbundle`, `registerjs`, `registerjsfile`, and `registerjsresource` tags now support an `at` param for specifying the location on the page their HTML tags should be placed. (Possible values are `at head`, `at beginBody`, and `at endBody`.)
- The `registerjs` tag now supports an `on` param for specifying when the JavaScript code should be executed. (Possible values are `on ready` and `on load`.) (The `at` and `on` parameters are mutually exclusive.)
- The `registercss`, `registerhirescss`, `registercssfile`, `registercssresource`, `registerjsfile`, and `registerjsresource` tags now support a `with` param, which can be set to an object that specifies additional attributes on the resulting tags (e.g. `with { rel: "stylesheet" }`).
- The `t` filter now always defaults to translating the given string using the `'site'` category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- `@craft/plugins/pluginhandle` aliases are now registered _before_ Craft attempts to load a plugin’s primary class, making it easier for plugins to use custom class names.
- `craft\base\Model::toArray()` now converts `DateTime` attributes to ISO-8601 strings.
- Renamed `craft\helpers\AssetHelper::cleanAssetName()` to `prepareAssetName()`.
- Renamed `craft\helpers\DbHelper::prepObjectValues()` to `prepareValuesForDb()`.
- Renamed `craft\helpers\DbHelper::prepValue()` to `prepareValueForDb()`.
- Renamed `craft\events\Event::$performAction` to `$isValid` to be consistent with Yii events.
- `craft\helpers\DateTimeHelper::toDateTime()` will now return `false` if it is unable to convert `$object` to a `DateTime` object.
- `craft\helpers\DateTimeHelper::toDateTime()` now supports ISO-8601-formatted dates.
- `craft\helpers\DbHelper::prepareValueForDb()` now treats ISO-8601 strings as dates, and converts them to the MySQL date format.

### Deprecated
- All the new `register*` tags must be all-lowercase now. (The old `include*` tags still work as they do in Craft 2, however they remain deprecated.)

### Removed
- Removed `craft\helpers\DateTimeHelper::currentTimeForDb()`.
- Removed `craft\helpers\DateTimeHelper::formatTimeForDb()`.

### Fixed
- Fixed a bug where Craft was not enforcing required entry titles and custom fields.
- Fixed a bug where it was not possible to create new Volumes on Craft Personal/Craft Client installs.
- Fixed an error that occurred when using the `type` param when querying for Matrix blocks.
- Fixed an “IP address restriction” error that prevented the Debug toolbar from showing up for some people.
- Fixed a PHP error that would occur when converting models without `$dateCreated` or `$dateUpdated` properties to an array.
- Fixed a PHP error that occurred when resetting a password.
- Fixed a PHP error that occurred when sending an activation email.
- Fixed a PHP error that occurred when saving an entry with validation errors, or when loading a draft for an entry, if the entry had any Matrix fields.
- Fixed a PHP error that occurred when editing an entry draft or version.
- Fixed a MySQL error that occurred when saving new image transforms.
- Fixed a MySQL error that occurred when saving a section with entry versioning disabled.
- Fixed `craft\web\View::registerHiResCss()`, and the `{% registerhirescss %}` tag.
- Fixed a bug that prevented user and email settings from being remembered.
- Fixed a JavaScript error that occurred on pages with fields that could be toggled by a checkbox.

## 3.0.0-alpha.2663 - 2015-05-27

### Changed
- Plugins can now add an array of Twig extensions from the `addTwigExtension` hook.

### Fixed
- Fixed a PHP error that occurred when installing Craft or validating URLs if the Intl extension wasn’t loaded.

## 3.0.0-alpha.2661 - 2015-05-22

### Added
- Added a `withPassword` criteria parameter to User queries, which includes the users’ hashed passwords in the query and sets them on the resulting User models.
- Added a `$setToSystemTimeZone` argument to `craft\helpers\DateTimeHelper::toDateTime()`, which will set the resulting `DateTime` object to the system’s Timezone setting (defaults to `true`).
- Added the `convertFilenamesToAscii` config setting.

### Changed
- The Debug toolbar no longer requires Dev Mode to be enabled.
- URL validation now accounts for URLs that include environment variables.
- URL validation now allows international domain names.
- `contentTable` is now a reserved field handle.
- Craft is now distributed with a `public/cpresources/` folder, which should be set with writable permissions.
- Craft now ensures that the public Resources folder exists and is writable at a much earlier stage so it can give a more helpful error message.

### Fixed
- Fixed a bug where many locales had the wrong display name in the CP if the Intl extension was not loaded.
- Fixed an error that occurred when saving a Volume using Craft Personal or Craft Client.
- Fixed an error error that occurred when validating a URL that included an environment variable.
- Fixed a bug where some dates were being output with the wrong timezone.
- Fixed an error that occurred when saving a Date/Time field set to only show the timepicker.
- Fixed a UI glitch where the bottom shadow of entry Save buttons would span the full width of the grid column if entry versioning was disabled or unavailable.
- Fixed a bug where the Min Build Required error message was getting HTML-encoded.
- Fixed a couple errors in that could occur if Craft didn’t receive the expected response from its web service.
- Fixed an error that occurred when attempting to log in with the wrong password multiple times.
- Fixed a bug where users would become permanently locked when their cooldown period had expired.
- Fixed a “Craft Client is required” error when editing entries in Craft Personal.
- Fixed an error that occurred after an Admin entered their password in order to change a user account’s email/password.
- Fixed a validation error on the New Password field if a user attempted to update their email address but didn’t want to change their existing password.
- Corrected the default config paths in the comments of `config/db.php` and `config/general.php`.
- Fixed a bug that resulted in the Updates page never getting past the “Checking for updates” step when an update was available.

## 3.0.0-alpha.2659 - 2015-05-19

### Added
- Added support for registering plugin resources via `craft\web\View::registerCssResource()` and `registerJsResource()`.
- Added `craft\base\Element::getStructureId()`, `setStructureId()`, and `resolveStructureId()`.

### Changed
- Drastically reduced the likelihood of importing a database backup with a falsely-identical schemaVersion as the files stored in `storage/runtime/compiled_classes/`.

### Fixed
- Updated the sample config files to use PHP 5.4’s short array syntax.
- Fixed a PHP error that would occur throughout the Control Panel when a new Craft update was available and its info was cached.
- Fixed a 404 error for jquery.placeholder.min.js that occurred on several pages in the Control Panel.
- Fixed a PHP error that occurred when formatting dates if the Intl extension wasn’t loaded.
- Fixed a PHP error that occurred when opening the category selection modal on a Categories field.
- Fixed a PHP error that occurred when saving an entry with a Categories field.
- Fixed a PHP error that occurred when searching for tags within a Tags field.
- Fixed a PHP error that occurred when using Date/Time fields configured to only show the timepicker.
- Fixed a bug where asset bundles wouldn’t get re-published if a file within one of its subdirectories was created or updated.
- Fixed a bug where database backups would get stored in `storagebackups/` instead of `storage/backups/`.
- Fixed a bug where the Min Build Required error message had encoded HTML.

## 3.0.0-alpha.2657 - 2015-05-19

### Added
- Completely rewritten and refactored codebase, powered by [Yii 2](http://www.yiiframework.com/).
- Improved internationalization support with PHP’s [Intl extension](http://php.net/manual/en/book.intl.php) and [Stringy](https://github.com/danielstjules/Stringy).
- Plugins are now loaded as Yii [modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html).
- Asset Sources are now called Volumes, and plugins can supply their own Volume Types (made easy with [Flysystem](http://flysystem.thephpleague.com/)).
- It is now possibly to customize the SQL of element queries, and there are more choices on how the data should be returned.
- Included the [Yii 2 Debug Extension](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).
