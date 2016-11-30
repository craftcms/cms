Craft CMS 3.0 Working Changelog
===============================

## [Unreleased]

### Added
- Ported all recent changes from Craft 2, including chart-related things added in Craft 2.6.
- Craft can now be installed via Composer: `composer require craftcms/craft`.
- Craft now supports installing plugins via Composer, with the help [Craft CMS Composer Installer](https://github.com/craftcms/composer-installer).
- Craft now checks for plugin info in a composer.json file, rather than plugin.json, for plugins that were manually installed in craft/plugins/. (See the [Craft CMS Composer Installer](https://github.com/craftcms/composer-installer) readme for details on how the info should be formatted.)
- Added the bootstrap/ folder alongside the src/ folder, with new web.php and console.php bootstrap files.
- Added PostgreSQL support, which can be enabled by setting the ‘driver’ setting in craft/config/db.pgp to “pgsql”.
- Added the ‘update/run-pending-migrations’ controller action, which can be used as a post-deploy hook for deployment services like DeployBot, to get Craft to automatically run any pending migrations, minimizing site downtime.
- Added the ‘backupCommand’ config setting, which can be used to override the command Craft executes when creating a database backup.
- Added the ‘restoreCommand’ config setting, which can be used to override the command Craft executes when restoring a database backup.
- Added the ‘dsn’ DB config setting, which can be used to manually specify the DSN string, ignoring most other DB config settings.
- Added the ‘schema’ DB config setting, which can be used to assign the default schema used when connecting to a PostgreSQL database.
- Added the className() global Twig function, which returns the class name of a given object.
- Added the JavaScript method BaseElementIndex::refreshSources().
- Added db\pgsql\QueryBuilder.
- Added db\pgsql\Schema.
- Added db\TableSchema.
- Added events\BackupFailureEvent.
- Added events\RegisterCacheOptionsEvent.
- Added events\RegisterComponentTypesEvent.
- Added events\RegisterCpAlertsEvent.
- Added events\RegisterCpNavItemsEvent.
- Added events\RegisterElementSortableAttributesEvent.
- Added events\RegisterElementSourcesEvent.
- Added events\RegisterElementTableAttributesEvent.
- Added events\RegisterEmailMessagesEvent.
- Added events\RegisterRichTextLinkOptionsEvent.
- Added events\RegisterUrlRulesEvent.
- Added events\RegisterUserActionsEvent.
- Added events\RegisterUserPermissionsEvent.
- Added events\ResolveResourcePathEvent.
- Added events\SetAssetFilenameEvent.
- Added events\SetElementRouteEvent.
- Added events\SetElementTableAttributeHtmlEvent.
- Added helpers\FileHelper.
- Added helpers\MailerHelper.
- Added validators\ArrayValidator.
- Added validators\AssetFilenameValidator.
- Added validators\UserPasswordValidator.
- Added base\Element::$validateCustomFields, which can be set to true or false to explicitly require/prevent custom field validation.
- Added base\Element::afterDelete(), which is called after an element is deleted.
- Added base\Element::afterMoveInStructure(), which is called after an element is moved within a structure.
- Added base\Element::beforeDelete(), which is called before the element is deleted.
- Added base\Element::defineActions().
- Added base\Element::defineSortableAttributes().
- Added base\Element::defineSources().
- Added base\Element::defineTableAttributes().
- Added base\Element::getHtmlAttributes(), which gives elements a chance to define any HTML attributes that should be included when rendering an element node for the Control Panel.
- Added base\Element::getSerializedFieldValues().
- Added base\Element::route().
- Added base\Element::tableAttributeHtml().
- Added base\Field::afterElementDelete(), which is called by an element after it is deleted.
- Added base\Field::beforeElementDelete(), which is called by an element before it is deleted.
- Added base\Field::getElementValidationRules(), which field types can override to return their element-level validation rules.
- Added base\MissingComponentTrait::createFallback().
- Added db\mysql\Schema::findIndexes().
- Added elements\Asset::$keepFileOnDelete, which can be set to true if the corresponding file should not be deleted when deleting the asset.
- Added elements\Asset::$newFilename, which can be set before saving an asset to rename its file.
- Added helpers\App::craftDownloadUrl().
- Added helpers\App::isComposerInstall().
- Added helpers\App::majorMinorVersion().
- Added helpers\Db::isTypeSupported().
- Added services\Config::getDbPort().
- Added services\Elements::deleteElement().
- Added the ‘beforeDelete’, ‘afterDelete’, ‘beforeMoveInStructure’, and ‘afterMoveInStructure’,  events to base\Element.
- Added the ‘beforeElementSave’, ‘afterElementSave’, ‘beforeElementDelete’, and ‘afterElementDelete’ events to base\Field.
- Added the ‘beforeRestoreBackup’, ‘afterRestoreBackup’, and ‘restoreFailure’ events to db\Connection.
- Added the ‘registerActions’ event to base\Element.
- Added the ‘registerAlerts’ event to helpers\Cp.
- Added the ‘registerCacheOptions’ event to tools\ClearCaches.
- Added the ‘registerCpNavItems’ event to variables\Cp.
- Added the ‘registerCpUrlRules’ and ‘registerSiteUrlRules’ events to web\UrlManager.
- Added the ‘registerElementTypes’ event to services\Elements.
- Added the ‘registerFieldTypes’ event to services\Fields.
- Added the ‘registerLinkOptions’ event to fields\RichText.
- Added the ‘registerMailerTransportTypes’ event to helpers\MailerHelper.
- Added the ‘registerMessages’ event to services\EmailMessages.
- Added the ‘registerPermissions’ event to services\UserPermissions.
- Added the ‘registerSortableAttributes’ event to base\Element.
- Added the ‘registerSources’ event to base\Element.
- Added the ‘registerTableAttributes’ event to base\Element.
- Added the ‘registerUserActions’ event to controllers\UsersController.
- Added the ‘registerVolumeTypes’ event to services\Volumes.
- Added the ‘registerWidgetTypes’ event to services\Dashboard.
- Added the ‘resolveResourcePath’ event to services\Resources.
- Added the ‘setFilename’ event to helpers\Assets.
- Added the ‘setRoute’ event to base\Element.
- Added the ‘setTableAttributeHtml’ event to base\Element.
- Added Guzzle 6 HTTP Adapter.
- Added php-shellcommand.

### Changed
- The bootstrap script now assumes that the vendor/ folder is 3 levels up from the bootstrap/ directory by default (e.g. vendor/craftcms/craft/bootstrap/). If that is not the case (most likely because Craft had been symlinked into place), the `CRAFT_VENDOR_PATH` PHP constant can be used to correct that.
- The default ‘port’ DB config value is now either 3306 (if MySQL) or 5432 (if PostgreSQL).
- The default ‘tablePrefix’ DB config value is now empty.
- When a category is deleted, its nested categories are no longer deleted with it.
- Renamed the “Get Help” widget to “Craft Support”.
- When editing a field whose type class cannot be found, Craft will now select Plain Text as a fallback and display a validation error on the Field Type setting.
- When editing a volume whose type class cannot be found, Craft will now select Local as a fallback and display a validation error on the Volume Type setting.
- When editing email settings and the previously-selected transport type class cannot be found, Craft will now select PHP Mail as a fallback and display a validation error on the Transport Type setting.
- The Feed widget is now limited to show 5 articles by default.
- Element queries’ ‘status’ params must now always be set to valid statuses, or the query won’t return any results.
- Craft now relies on command line tools to create database backups (mysqldump and pg_dump).
- Test emails now mask the values for any Mailer transport type settings that include “key” or “password” in their setting name.
- The Control Panel page header is now fixed when scrolling down.
- Translatable fields are no longer marked as translatable when editing an element type that isn’t localizable (e.g. user accounts).
- Custom email messages are now stored on o per-language basis rather than per-site basis.
- Element indexes now remember which sources were expanded across multiple requests.
- Element indexes now remember if a nested source was selected across multiple requests.
- The ‘backupDbOnUpdate’ config setting has been renamed to ‘backupOnUpdate’. Note that performance should no longer be a major factor when setting this to false, since the backup is no longer generated by PHP.
- The ‘restoreDbOnUpdateFailure’ config setting has been renamed to ‘restoreOnUpdateFailure’.
- Plugin schema versions now default to “1.0.0”, and plugins absolutely must increment their schema version if they want any pending migrations to be noticed.
- base\Plugin no longer automatically registers field types in the plugin’s fields/ subfolder.
- base\Plugin no longer automatically registers widget types in the plugin’s widgets/ subfolder.
- base\Plugin no longer automatically registers volume types in the plugin’s volumes/ subfolder.
- helpers\Io::copyFolder() now creates the destination folder if it didn’t already exist.
- Mailer Transport Adapters’ getTransportConfig() methods are now called at runtime when configuring the Mailer app component, rather than only when email settings are saved.
- elements\User now supports a ‘password’ validation scenario, which only validates the $newPassword property.
- It is no longer possible to change a user’s locked/suspended/pending/archived status when saving the User element normally.
- elements\db\MatrixBlockQuery::owner() and ownerSiteId() now set the $siteId property when appropriate.
- The source keys that are passed into element methods’ $source arguments now reflect the full path to the source, if it is a nested source (e.g. “folder:1/folder:2”).
- The ‘Craft.publishableSections’ Javascript array now includes info about each section’s entry types.
- db\mysql\Schema::getTableNames() no longer only returns the table names with the right table prefix.
- services\Elements::deleteElementById() no longer accepts an array of element IDs.
- base\Element::afterSave() now has an $isNew argument, which will indicate whether the element is brand new.
- base\Element::beforeSave() now has an $isNew argument, which will indicate whether the element is brand new.
- base\Field::afterElementSave() now has an $isNew argument, which will indicate whether the element is brand new.
- base\Field::beforeElementSave() now has an $isNew argument, which will indicate whether the element is brand new.
- base\SavableComponent::afterSave() now has an $isNew argument, which will indicate whether the element is brand new.
- base\SavableComponent::beforeSave() now has an $isNew argument, which will indicate whether the element is brand new.
- services\Elements::deleteElementById() now has $elementType and $siteId arguments.
- The ‘beforeElementSave’ and ‘afterElementSave’ events triggered by base\Element now have $isNew properties, which indicate whether the element is brand new.
- The ‘beforeSave’ and ‘afterSave’ events triggered by base\Element now have $isNew properties, which indicate whether the element is brand new.
- The ‘beforeSave’ and ‘afterSave’ events triggered by base\SavableComponent now have $isNew properties, which indicate whether the component is brand new.
- Renamed literally every Craft class’ namespace from `craft\app\*` to `craft\*`.
- Renamed base\Savable to Serializable, and its getSavableValue() method was renamed to serialize().
- Renamed et\Et to EtTransport.
- Renamed events\DbBackupEvent to BackupEvent.
- Renamed events\EntryEvent to VersionEvent.
- Renamed mail\transportadaptors\BaseTransportAdaptor to mail\transportadapters\BaseTransportAdapter.
- Renamed mail\transportadaptors\Gmail to mail\transportadapters\Gmail.
- Renamed mail\transportadaptors\Php to mail\transportadapters\Php.
- Renamed mail\transportadaptors\Sendmail to mail\transportadapters\Sendmail.
- Renamed mail\transportadaptors\Smtp to mail\transportadapters\Smtp.
- Renamed mail\transportadaptors\TransportAdaptorInterface to mail\transportadapters\TransportAdapterInterface.
- Renamed base\Element::defineAvailableTableAttributes() to tableAttributes().
- Renamed base\Element::defineSearchableAttributes() to searchableAttributes().
- Renamed base\Element::defineSortableAttributes() to sortableAttributes().
- Renamed base\Element::getAvailableActions() to actions().
- Renamed base\Element::getContentPostLocation() to getFieldParamNamespace().
- Renamed base\Element::getDefaultTableAttributes() to defaultTableAttributes().
- Renamed base\Element::getEagerLoadingMap() to eagerLoadingMap().
- Renamed base\Element::getIndexHtml() to indexHtml().
- Renamed base\Element::getSources() to sources().
- Renamed base\Element::getStatuses() to statuses().
- Renamed base\Element::setContentPostLocation() to setFieldParamNamespace().
- Renamed base\Element::setFieldValuesFromPost() to setFieldValuesFromRequest(), and the method no longer accepts an array of field values. Only call this method as a shortcut for setFieldParamNamespace() and setFieldValues(), passing in the param namespace the field values should be extracted from on the request body.
- Renamed base\Field::getContentPostLocation() to getRequestParamName().
- Renamed base\Field::prepareValue() to normalizeValue().
- Renamed base\Field::prepareValueForDb() to serializeValue().
- Renamed Craft::getCookieConfig() to cookieConfig()
- Renamed db\Command::insertOrUpdate() to upsert().
- Renamed db\Migration::insertOrUpdate() to upsert().
- Renamed db\mysql\QueryBuilder::insertOrUpdate() to upsert().
- Renamed elements\User::getAuthData() to authData()
- Renamed helpers\App::getEditionName() to editionName().
- Renamed helpers\App::getEditions() to editions().
- Renamed helpers\App::getMajorVersion() to majorVersion().
- Renamed helpers\App::getPhpConfigValueAsBool() to phpConfigValueAsBool().
- Renamed helpers\App::getPhpConfigValueInBytes() to phpConfigValueInBytes().
- Renamed helpers\ArrayHelper::getFirstKey() to firstKey().
- Renamed helpers\ArrayHelper::getFirstValue() to firstValue().
- Renamed helpers\Assets::getFileTransferList() to fileTransferList().
- Renamed helpers\Assets::getPeriodList() to periodList().
- Renamed helpers\Assets::getTempFilePath() to tempFilePath().
- Renamed helpers\Assets::getUrlAppendix to urlAppendix().
- Renamed helpers\ChartHelper::getCurrencyFormat() to currencyFormat().
- Renamed helpers\ChartHelper::getDateRanges() =>dateRanges()
- Renamed helpers\ChartHelper::getDecimalFormat() =>decimalFormat()
- Renamed helpers\ChartHelper::getFormats() to formats().
- Renamed helpers\ChartHelper::getPercentFormat() to percentFormat().
- Renamed helpers\ChartHelper::getShortDateFormats() to shortDateFormats().
- Renamed helpers\Cp::getAlerts() to alerts().
- Renamed helpers\ElementHelper::getEditableSiteIdsForElement() to editableSiteIdsForElement().
- Renamed helpers\ElementHelper::getSupportedSitesForElement() to supportedSitesForElement().
- Renamed helpers\Image::getImageSize() to imageSize().
- Renamed helpers\Image::getPngImageInfo() to pngImageInfo().
- Renamed helpers\Image::getWebSafeFormats() to webSafeFormats().
- Renamed helpers\Localization::getLocaleData() to localeData().
- Renamed helpers\MailerHelper::getAllMailerTransportTypes() to allMailerTransportTypes().
- Renamed helpers\Search::getMinWordLength() to minWordLength().
- Renamed helpers\Search::getStopWords() to stopWords().
- Renamed helpers\StringHelper::getAsciiCharMap() to asciiCharMap().
- Renamed helpers\StringHelper::getCharAt() to charAt().
- Renamed helpers\StringHelper::getEncoding() to encoding().
- Renamed helpers\StringHelper::uppercaseFirst() to upperCaseFirst().
- Renamed helpers\Template::getRaw() to raw().
- Renamed helpers\Url::getUrl() to url().
- Renamed helpers\Url::getUrlWithParams() to urlWithParams().
- Renamed helpers\Url::getUrlWithProtocol() to urlWithProtocol().
- Renamed helpers\Url::getUrlWithToken() to urlWithToken().
- Renamed models\AssetTransform::getTransformModes() to modes().
- Renamed services\Assets::renameAsset() to renameFile(), and replaced its $newFilename argument with $runValidation.
- Renamed services\Resources::getResourcePath() to resolveResourcePath().
- Renamed volumes\AwsS3::getClient() to client().
- Renamed volumes\AwsS3::getStorageClasses() to storageClasses().
- Renamed volumes\GoogleCloud::getClient() to client().
- Renamed volumes\Rackspace::getClient() to client().
- base\Element::getEditorHtml() is no longer static, and no longer has an $element argument.
- base\Element::getElementRoute() is no longer static, no longer has an $element argument, and has been renamed to getRoute().
- base\Element::getElementQueryStatusCondition() has been moved to elements\db\ElementQuery::statusCondition(), and no longer has a $query argument.
- base\Element::getFieldsForElementQuery() has been moved to elements\db\ElementQuery::customFields(), and no longer has a $query argument.
- base\Element::getTableAttributeHtml() is no longer static, and no longer has an $element argument.
- base\Element::onAfterMoveElementInStructure() is no longer static, no longer has an $element argument, and has been renamed to afterMoveInStructure().
- helpers\Io::createFile() now returns `true` on success rather than an io\File object.
- helpers\Io::createFolder() now returns `true` on success rather than an io\Folder object.
- helpers\Io::folderExists() now returns the folder path without a trailing slash, if the folder exists.
- helpers\Io::getFolderContents() now returns nested folder paths without trailing slashes.
- helpers\Io::getFolderName() now returns the folder path without a trailing slash when the `$fullPath` argument is `true`.
- helpers\Io::getFolders() now returns the child folder paths without trailing slashes.
- helpers\Io::getParentFolderPath() now returns the parent folder path without a trailing slash, or `false` if `$path` is already a root.
- helpers\Io::getRealPath() now returns the path without a trailing slash, if the given `$path` is for a folder.
- helpers\Io::normalizePathSeparators() now return the normalized path without a trailing slash.
- io\File::getFolderName() and io\Folder::getFolderName() now return the folder path without a trailing slash when the `$fullPath` argument is `true`.
- io\Folder::getContents() now returns nested folder paths without trailing slashes.
- services\Updates::getUnwritableFolders() now returns folder paths without trailing slashes.
- Updated Yii to 2.0.10.
- Updated Yii 2 Debug Extension to 2.0.7.
- Updated Yii 2 Auth Client to 2.1.1.
- Updated Yii 2 SwiftMailer to 2.0.6.
- Updated SimplePie to 1.4.3.
- Updated Guzzle to 6.2.2.
- Updated Imagine to the new `pixelandtonic/imagine` fork at 0.6.3.1.
- Updated Twig to 1.28.2.

### Deprecated
- The getTranslations() global Twig function has been deprecated. Use craft.app.view.getTranslations() instead.

### Removed
- Removed support for the `CRAFT_FRAMEWORK_PATH` PHP constant in the bootstrap script. It is now expected Yii is located alongside Craft and other dependencies in the vendor/ folder.
- Removed the ‘collation’ DB config setting.
- Removed the ‘initSQLs’ DB config setting.
- Removed the PEL library.
- Removed the PclZip library.
- Removed support for EXIF data removal and automatic image rotating for servers without ImageMagick installed.
- Removed db\DbBackup.
- Removed enums\BaseEnum.
- Removed events\CategoryEvent.
- Removed events\DeleteUserEvent.
- Removed events\EntryDeleteEvent.
- Removed events\UserEvent.
- Removed io\BaseIO.
- Removed io\File.
- Removed io\Folder.
- Removed io\PclZip.
- Removed io\Zip.
- Removed io\ZipArchive.
- Removed io\ZipInterface.
- Removed models\LogEntry.
- Removed models\Password.
- Removed base\Component::getType(). It was only really there for objects that implement base\MissingComponentInterface, and now they have an $expectedType property.
- Removed base\Element::getContentFromPost().
- Removed base\Element::getSourceByKey().
- Removed base\Element::saveElement().
- Removed base\Element::setRawPostValueForField().
- Removed base\FieldInterface::validateValue(). Fields should start implementing getElementValidationRules() if they want to customize how their values get validated.
- Removed base\Model::create().
- Removed base\Model::getAllErrors().
- Removed base\Model::populateModel().
- Removed base\Plugin::getClassesInSubpath().
- Removed base\Plugin::getFieldTypes().
- Removed base\Plugin::getVolumeTypes().
- Removed base\Plugin::getWidgetTypes().
- Removed db\Command::addColumnAfter().
- Removed db\Command::addColumnBefore().
- Removed db\Command::addColumnFirst().
- Removed db\Migration::addColumnAfter().
- Removed db\Migration::addColumnBefore().
- Removed db\Migration::addColumnFirst().
- Removed db\mysql\QueryBuilder::addColumnAfter().
- Removed db\mysql\QueryBuilder::addColumnBefore().
- Removed db\mysql\QueryBuilder::addColumnFirst().
- Removed helpers\Io::copyFolder(). Use helpers\FileHelper::copyDirectory() instead.
- Removed helpers\Io::getFile().
- Removed helpers\Io::getFolder().
- Removed helpers\Io::normalizePathSeparators(). Use helpers\FileHelper::normalizePath() instead.
- Removed services\Assets::deleteAssetsByIds().
- Removed services\Assets::deleteCategory().
- Removed services\Assets::deleteCategoryById().
- Removed services\Assets::findAsset().
- Removed services\Assets::findAssets().
- Removed services\Categories::saveCategory().
- Removed services\Content::validateContent().
- Removed services\Entries::deleteEntry().
- Removed services\Entries::deleteEntryById().
- Removed services\Entries::saveEntry().
- Removed services\Globals::deleteSetById().
- Removed services\Globals::saveContent().
- Removed services\Matrix::deleteBlockById().
- Removed services\Matrix::saveBlock().
- Removed services\Matrix::validateBlock().
- Removed services\Plugins::call().
- Removed services\Plugins::callFirst().
- Removed services\Tags::saveTag().
- Removed services\Users::changePassword().
- Removed services\Users::deleteUser().
- Removed the $attribute argument from base\ApplicationTrait::getInfo().
- Removed the $except argument from base\Element::getFieldValues().
- Removed the $newName and $after arguments from db\Command::alterColumn().
- Removed the $newName and $after arguments from db\Migration::alterColumn().
- Removed the $newName and $after arguments from db\mysql\QueryBuilder::alterColumn().
- Removed the $runValidation argument from services\Content::saveContent().
- Removed the &$params argument from helpers\Db::parseDateParam().
- Removed the &$params argument from helpers\Db::parseParam().
- Removed the ‘beforeDeleteAsset’, ‘afterDeleteAsset’, ‘beforeSaveAsset’ and ‘afterSaveAsset’ events from services\Assets.
- Removed the ‘beforeDeleteCategory’, ‘afterDeleteCategory’, ‘beforeSaveCategory’ and ‘afterSaveCategory’ events from services\Categories.
- Removed the ‘beforeDeleteEntry’, ‘afterDeleteEntry’, ‘beforeSaveEntry’ and ‘afterSaveEntry’ events from services\Entry.
- Removed the ‘beforeDeleteGlobalSet’, ‘beforeDeleteGlobalSet’, ‘beforeSaveGlobalContent’ and ‘afterSaveGlobalContent’ events from services\Globals.
- Removed the ‘beforeDeleteUser’, ‘afterDeleteUser’, ‘beforeSaveUser’, ‘afterSaveUser’, ‘beforeSetPassword’, and ‘afterSetPassword’ events from services\Users.
- Removed the ‘beforeSaveTag’ and ‘afterSaveTag’ events from services\Tags.
- Removed the ‘addRichTextLinkOptions’ plugin hook. Custom Rich Text field link options should be registered using the ‘registerLinkOptions’ event on fields\RichText now.
- Removed the ‘addTwigExtension’ plugin hook. Custom Twig extensions should be added by calling `Craft::$app->view->twig->addExtension()` directly.
- Removed the ‘addUserAdministrationOptions’ plugin hook. Custom actions for the Edit User page should be registered using the ‘registerUserActions’ event on controllers\UsersController now.
- Removed the ‘defineAdditionalAssetTableAttributes’, ‘defineAdditionalCategoryTableAttributes’, ‘defineAdditionalEntryTableAttributes’, and ‘defineAdditionalUserTableAttributes’ plugin hooks. Custom table attributes should be registered using the ‘registerTableAttributes’ event on base\Element or one of its subclasses now.
- Removed the ‘defineAssetActions’, ‘defineCategoryActions’, ‘defineEntryActions’, and ‘defineUserActions’ plugin hooks. Custom element actions should be registered using the ‘registerActions’ event on base\Element or one of its subclasses now.
- Removed the ‘getAssetTableAttributeHtml’, ‘getCategoryTableAttributeHtml’, ‘getEntryTableAttributeHtml’, and ‘getUserTableAttributeHtml’ plugin hooks. Table attribute HTML should be overridden using the ‘setTableAttributeHtml’ event on base\Element or one of its subclasses now.
- Removed the ‘getCpAlerts’ plugin hook. Custom Control Panel alerts should be registered using the ‘registerAlerts’ event on helpers\Cp now.
- Removed the ‘getElementRoute’ plugin hook. Element routes should be overridden using the ‘setRoute’ event on base\Element or one of its subclasses now.
- Removed the ‘getFieldTypes’ plugin hook. Custom field types should be registered using the ‘registerFieldTypes’ event on services\Fields now.
- Removed the ‘getMailTransportAdapters’ plugin hook. Custom transport types should be registered using the ‘registerMailerTransportTypes’ event on helpers\MailerHelper now.
- Removed the ‘getResourcePath’ plugin hook. Custom resource URIs should be resolved to file paths using the ‘resolveResourcePath’ event on services\Resources now.
- Removed the ‘getTableAttributesForSource’ plugin hook.
- Removed the ‘getVolumeTypes’ plugin hook. Custom volume types should be registered using the ‘registerVolumeTypes’ event on services\Volumes now.
- Removed the ‘getWidgetTypes’ plugin hook. Custom widget types should be registered using the ‘registerWidgetTypes’ event on services\Dashboard now.
- Removed the ‘modifyAssetFilename’ plugin hook. Asset filenames should be overridden using the ‘setFilename’ event on helpers\Assets now.
- Removed the ‘modifyAssetSortableAttributes’, ‘modifyCategorySortableAttributes’, ‘modifyEntrySortableAttributes’, and ‘modifyUserSortableAttributes’ plugin hooks. Sortable attribute modifications should be made using the ‘registerSortableAttributes’ event on base\Element or one of its subclasses now.
- Removed the ‘modifyAssetSources’, ‘modifyCategorySources’, ‘modifyEntrySources’, and ‘modifyUserSources’ plugin hooks. Element source modifications should be made using the ‘registerSources’ event on base\Element or one of its subclasses now.
- Removed the ‘modifyCpNav’ plugin hook. Control Panel nav modifications should be made using the ‘registerCpNavItems’ event on variables\Cp now.
- Removed the ‘registerCachePaths’ plugin hook. Custom options for the Clear Caches tool (which can be set to callbacks now in addition to file paths) should be registered using the ‘registerCacheOptions’ event on tools\ClearCaches now.
- Removed the ‘registerCpRoutes’ and ‘registerSiteRoutes’ plugin hooks. Custom URL rules for the Control Panel and front-end site should be registered using the ‘registerCpUrlRules’ and ‘registerSiteUrlRules’ events on web\UrlManager now.
- Removed the ‘registerEmailMessages’ plugin hook. Custom email messages should be registered using the ‘registerMessages’ event on services\EmailMessages now.
- Removed the ‘registerUserPermissions’ plugin hook. Custom user permissions should be registered using the ‘registerPermissions’ event on services\UserPermissions now.

### Fixed
- Fixed a bug where custom 503 templates weren’t rendering when Craft was in the middle of updating from an earlier version than 3.0.2933.
- Fixed a validation error that occurred when saving a field.
- Fixed a PHP error that occurred when using the {% header %} Twig tag.
- Fixed the GeneratePendingTransforms task creation that occurs in services\Assets::getUrlForAsset().
- Fixed a PHP error that occurred when viewing entry revisions that were created before updating to Craft 3.
- Fixed a bug where brand new elements were not getting their $uid property set on them after getting saved.
- Fixed a bug where user activation emails that were sent immediately after creating the user account were getting an invalid activation URL.
- Fixed a PHP error that occurred when saving a Structure section entry with a new parent.
- Fixed a bug where entry titles were required for validation even if the entry type didn’t opt to show the title field.
- Fixed a bug where the ‘users/edit-user’ controller action wasn’t respecting the passed in User object, if there was one, so validation errors weren’t getting reported.
- Fixed an error that occurred when the Control Panel attempted to start running background tasks if there were no tasks queued up.
- Fixed a bug where Recent Entries widgets would lose their “Locale” (now “Site”) setting values when upgrading from an older version of Craft.
- Fixed a bug where the Dashboard was allowing users to add widgets that were calling themselves unselectable.
- Fixed a bug where sessions table rows weren’t getting deleted after users logged out.
- Fixed a bug where the ‘fixedOrder’ parameter wasn’t being respected for entry queries.
- Fixed a bug where plugin-supplied custom fields weren’t working.
- Fixed a PHP error that occurred when opening an element editor.
- Fixed authorization error that occurred when editing an entry in a section that’s not enabled for the current site.
- Fixed a PHP error when using the {% cache %} tag.
- Fixed an error that occurred when clicking on an email message to edit it.
- Fixed an error that occurred when helpers\Io\FileCache::setValue() was called and the destination folder already existed.
- Fixed support for the testToEmailAddress config setting.
- Fixed a bug where the ‘tasks/run-pending-tasks’ controller action was requiring an authenticated session.
- Fixed a PHP error that occurred when saving a Recent Entries widget.
- Fixed a bug where Recent Entries widgets’ Site and Limit settings weren’t being validated correctly.
- Fixed a bug where widget settings errors were getting reported twice.
- Fixed a bug where console requests were only working when running the Pro edition.
- Fixed a bug where the Instructions setting within newly-created sub-fields in a Matrix field’s settings were getting marked as required.
- Fixed a bug where custom nested element sources registered by plugins were not getting Admin-defined custom table attributes.
- Fixed a bug where searching by an `"exact phrase"` wasn’t working.
- Fixed a bug where the Backup Database tool wasn’t downloading the DB backup if the “Download backup?” checkbox was checked.
- Fixed a bug where helpers\Io::clearFolder() was returning `false` for folders that existed but had no contents.

## [v3.0.0-alpha.2948] - 2016-09-29

### Added
- Added i18n\Locale::getNumberPattern().
- Added web\Request::getValidatedBodyParam().
- Added services\Fields::deleteGroup().
- Added services\Fields::deleteLayout().
- Added services\Sections::deleteSection().
- Added services\Sections::deleteEntryType().
- Added services\Sites::deleteSite().
- Added services\Volumes::deleteVolume().
- Added base\SavableComponent::beforeSave().
- Added base\SavableComponent::afterSave().
- Added base\SavableComponent::beforeDelete().
- Added base\SavableComponent::afterDelete().
- Added the ‘beforeSave’, ‘afterSave’, ‘beforeDelete’, and ‘afterDelete’ events to base\SavableComponent.
- Added the ‘beforeSaveWidget’, ‘afterSaveWidget’, ‘beforeDeleteWidget’, and ‘afterDeleteWidget’ events to services\Dashboard.
- Added the ‘beforeSaveFieldLayout’, ‘beforeDeleteFieldLayout’, ‘afterDeleteFieldLayout’, ‘beforeSaveFieldGroup’, ‘afterSaveFieldGroup’, ‘beforeDeleteFieldGroup’, ‘afterDeleteFieldGroup’, ‘beforeSaveField’, ‘afterSaveField’, ‘beforeDeleteField’, and ‘afterDeleteField’ events to services\Fields.
- Added the ‘beforeLoadPlugins’, ‘beforeEnablePlugin’, ‘afterEnablePlugin’, ‘beforeDisablePlugin’, ‘afterDisablePlugin’, ‘beforeInstallPlugin’, ‘afterInstallPlugin’, ‘beforeUninstallPlugin’, ‘afterUninstallPlugin’, ‘beforeSavePluginSettings’, and ‘afterSavePluginSettings’ events to services\Plugins.
- Added the ‘beforeSaveRoute’, ‘afterSaveRoute’, ‘beforeDeleteRoute’, and ‘afterDeleteRoute’ events to services\Routes.
- Added the ‘beforeSearch’ and ‘afterSearch’ events to services\Search.
- Added the ‘beforeSaveTask’, ‘afterSaveTask’, ‘beforeDeleteTask’, and ‘afterDeleteTask’ events to services\Tasks.
- Added the ‘loginFailure’ event to controllers\UsersController (replacing the like-named event on elements\User).

### Changed
- Updated the Intl fallback data based on ICU 56.1. If you have any additional locale data files in craft/locales/, you should [update them](https://github.com/pixelandtonic/CraftLocaleData), too.
- Ported recent changes from Craft 2.
- The ‘beforeSaveAssetTransform’ event on services\AssetTransforms no longer supports an $isValid property.
- The ‘beforeDeleteAssetTransform’ event on services\AssetTransforms no longer supports a $isValid property.
- The ‘beforeSaveCategory’ event on services\Categories no longer supports an $isValid property.
- The ‘beforeDeleteGroup event on services\Categories no longer supports an $isValid property.
- The ‘beforeDeleteSection’ event on services\Sections no longer supports an $isValid property.
- The ‘beforeSaveEntryType’ event on services\Sections no longer supports an $isValid property.
- The ‘beforeDeleteEntryType’ event on services\Sections no longer supports an $isValid property.
- The ‘beforeReorderSites’ event on services\Sites no longer supports an $isValid property.
- The ‘beforeMoveElement’ event on services\Structures no longer supports an $isValid property.
- The ‘beforeSaveTag’ event on services\Tags no longer supports an $isValid property.
- The ‘beforeSaveGroup’ event on services\Tags no longer supports an $isValid property.
- The ‘beforeDeleteGroup’ event on services\Tags no longer supports an $isValid property.
- The ‘beforeSaveUserGroup’ event on services\UserGroups no longer supports an $isValid property.
- The ‘beforeDeleteUserGroup’ event on services\UserGroups no longer supports an $isValid property.
- The ‘beforeSaveVolume’ event on services\Volumes no longer supports an $isValid property.
- The ‘beforeDeleteVolume’ event on services\Volumes no longer supports an $isValid property.
- services\AssetTransforms::saveTransform() now accepts a $runValidation argument.
- services\Categories::saveCategory() now accepts a $runValidation argument.
- services\Entries::saveEntry() now accepts a $runValidation argument.
- services\Fields::saveField() now accepts a $runValidation argument.
- services\Fields::saveGroup() now accepts a $runValidation argument.
- services\Fields::saveLayout() now accepts a $runValidation argument.
- services\Tags::saveTag() now accepts a $runValidation argument.
- services\Tags::saveTagGroup() now accepts a $runValidation argument.
- services\UserGroups::saveGroup() now accepts a $runValidation argument.
- services\Volumes::saveVolume() now accepts a $runValidation argument.
- services\Sections::deleteEntryTypeById() no longer accepts an array of entry type IDs.
- Renamed validators\DateTime to DateTimeValidator.
- Renamed validators\Handle to HandleValidator.
- Renamed validators\SingleSectionUri to SingleSectionUriValidator.
- Renamed validators\SiteId to SiteIdValidator.
- Renamed validators\Unique to UniqueValidator.
- Renamed validators\Uri to UriValidator.
- Renamed validators\Url to UrlValidator.
- Renamed validators\UriFormat to UriFormatValidator.
- Deleted the services\Volumes::VOLUME_INTERFACE constant.
- Deleted the services\Tasks::TASK_INTERFACE constant.
- Deleted the services\Fields::FIELD_INTERFACE constant.
- Deleted the services\Elements::ELEMENT_INTERFACE constant.
- Deleted the services\Elements::ACTION_INTERFACE constant.
- Deleted the services\Dashboard::WIDGET_INTERFACE constant.
- Deleted enums\ConfigCategory. Its constants have been moved to services\Config in the form of “CATEGORY_X”.

### Removed
- Removed the concept of “Safe Mode” from Twig, as it is no longer needed.
- Removed the ‘beforeInstall’, ‘afterInstall’, ‘beforeUpdate’, ‘afterUpdate’, ‘beforeUninstall’, and ‘afterUninstall’ events from base\Plugin.

### Fixed
- Fixed a bug where Craft couldn’t locate the DB backup file when attempting to restore it after a failed update.
- Fixed bug where element queries weren’t automatically getting ordered in their structure’s order, when applicable.
- Fixed a bug where changes to users’ Preferred Language settings weren’t sticking.
- Fixed a bug where i18n\I18N::getAppLocales() and getAppLocaleIds() were only returning en-US.
- Fixed a bug where Craft was including a localized ‘0’ character when formatting numbers as JPY, KRW, VND, XAF, XOF, and XPF currencies, if the Intl extension was not installed.
- Fixed a bug where Craft wasn’t displaying the “Can’t run Craft CMS” page correctly if it couldn’t connect to the database.
- Fixed a bug where updating to Craft 3.0.2933 or later could fail if the backupDbOnUpdate config setting was enabled.
- Fixed a bug where Matrix fields would lose their blocks when the owner element was saved outside of a normal Save Entry (etc.) POST request, e.g. when creating a new site.
- Fixed a PHP error that occurred when loading a category page on the front-end.
- Fixed a PHP error that occurred when calling ElementQuery::site().
- Fixed an infinite recursion bug when calling ElementQuery::toArray() or passing an ElementQuery object into ArrayHelper::toArray().
- Fixed a PHP error that occurred when deleting an entry.
- Fixed a bug where Craft wasn’t renaming content table columns when a field’s handle was renamed; instead it was just adding a new column based on the new handle.

## [v3.0.0-alpha.2942] - 2016-09-21

### Added
- Added [Content Migrations](https://craftcms.com/news/craft-3-content-migrations) support.
- Added the |timestamp filter, for formatting a date as a user-friendly timestamp.
- Added the |datetime filter, for formatting a date with a localized date+time format.
- Added the |time filter, for formatting a date with a localized time format.
- Added web\Request::getAcceptsJson(), which returns whether the HTTP request accepts a JSON response.
- Added web\Controller::requireAcceptsJson(), which mandates that the HTTP request accepts a JSON response.
- Added i18n\Formatter::asTimestamp(), for formatting a date as a user-friendly timestamp.
- Added StringHelper::ensureStartsWith(), which will check if a string begins with a substring, and prepend it if it doesn’t.
- Added StringHelper::ensureEndsWith(), which will check if a string ends with a substring, and append it if it doesn’t.
- Added the services\EntryRevisions::beforeRevertEntryToVersion event.
- Added MigrationHelper::doesForeignKeyExist().
- Added MigrationHelper::doesIndexExist().

### Changed
- Improved the error message when a Migrate action was called with an invalid --plugin arg.
- Ported all recent changes from Craft 2.
- The |date filter can be passed “short”, “medium”, “long”, and “full”, which will format the date with a localized date format.
- The {% cache %} tag no longer includes the query string when storing the cache URL.
- DateTimeHelper::isToday(), isYesterday(), isThisYear(), isThisWeek(), isThisMonth(), isWithinLast(), isInThePast(), and timeAgoInWords() now support all the same types of $date values as DateTimeHelper::toDateTime().
- MigrationHelper::dropForeignKeyIfExists() and dropIndexIfExists() now allow the $columns argument to be a string.
- The services\EntryRevisions::beforeSaveDraft event’s $isValid property is no longer respected. To prevent a draft from saving, set `$isValid = false` on the EntryDraft::beforeValidate event.
- The services\EntryRevisions::beforePublishDraft event’s $isValid property is no longer respected. To prevent a draft from publishing, set `$isValid = false` on the EntryDraft::beforeValidate event.
- The services\EntryRevisions::beforeDeleteDraft event’s $isValid property is no longer respected.
- The services\Updates::afterUpdateFail event has been renamed to updateFailure.
- The mail\Mailer::sendEmailError event has been renamed to sendMailFailure.
- Front-end forms that submit Ajax requests to Craft that expect a JSON response must start passing an `Accept: application/json` header. jQuery’s Ajax methods will do this if the `dataType` option is set to `'json'`.
- Renamed DateTimeHelper::wasYesterday() to isYesterday().
- Renamed DateTimeHelper::wasWithinLast() to isWithinLast().
- Renamed DateTimeHelper::wasInThePast() to isInThePast().
- The $day argument of i18n\Locale::getWeekDayName() should now be 0-6 instead of 1-7, where `0` represents Sunday.

### Deprecated
- Deprecated the round() Twig function. Use the |round filter instead.
- Deprecated craft\app\dates\DateTime::__toString(). Use `format('Y-m-d')` instead.
- Deprecated craft\app\dates\DateTime::atom(). Use `format(DateTime::ATOM)` instead.
- Deprecated craft\app\dates\DateTime::cookie(). Use `format(DateTime::COOKIE)` instead.
- Deprecated craft\app\dates\DateTime::iso8601(). Use `format(DateTime::ISO8601)` instead.
- Deprecated craft\app\dates\DateTime::rfc822(). Use `format(DateTime::RFC822)` instead.
- Deprecated craft\app\dates\DateTime::rfc850(). Use `format(DateTime::RFC850)` instead.
- Deprecated craft\app\dates\DateTime::rfc1036(). Use `format(DateTime::RFC1036)` instead.
- Deprecated craft\app\dates\DateTime::rfc1123(). Use `format(DateTime::RFC1123)` instead.
- Deprecated craft\app\dates\DateTime::rfc2822(). Use `format(DateTime::RFC2822)` instead.
- Deprecated craft\app\dates\DateTime::rfc3339(). Use `format(DateTime::RFC3339)` instead.
- Deprecated craft\app\dates\DateTime::rss(). Use `format(DateTime::RSS)` instead.
- Deprecated craft\app\dates\DateTime::w3c(). Use `format(DateTime::W3C)` instead.
- Deprecated craft\app\dates\DateTime::w3cDate(). Use `format('Y-m-d')` instead.
- Deprecated craft\app\dates\DateTime::mySqlDateTime(). Use `format('Y-m-d H:i:s')` instead.
- Deprecated craft\app\dates\DateTime::localeDate(). Use `Craft::$app->formatter->asDate($date, 'short')` instead.
- Deprecated craft\app\dates\DateTime::localeTime(). Use `Craft::$app->formatter->asTime($date, 'short')` instead.
- Deprecated craft\app\dates\DateTime::year(). Use `format('Y')` instead.
- Deprecated craft\app\dates\DateTime::month(). Use `format('n')` instead.
- Deprecated craft\app\dates\DateTime::day(). Use `format('j')` instead.
- Deprecated craft\app\dates\DateTime::nice(). Use `Craft::$app->formatter->asDatetime($date)` instead.
- Deprecated craft\app\dates\DateTime::uiTimestamp(). Use `Craft::$app->formatter->asTimestamp($date, 'short')` instead.

### Removed
- Removed the static $plugin property on base\Plugin.
- Removed web\Controller::requireAjaxRequest(). Use requireAcceptsJson() instead.
- Removed DateTimeHelper::fromString(). Use `DateTimeHelper::toDateTime($date)->getTimestamp()` instead.
- Removed DateTimeHelper::uiTimestamp(). Use `Craft::$app->formatter->asTimestamp($date)` instead.
- Removed DateTimeHelper::timeAgoInWords(). Use `Craft::$app->formatter->asRelativeTime($date)` instead.
- Removed DateTimeHelper::nice(). Use `Craft::$app->formatter->asDatetime($date)` instead.
- Removed DateTimeHelper::niceShort().
- Removed the craft\app\dates\DateTime::W3C_DATE constant.
- Removed the craft\app\dates\DateTime::MYSQL_DATETIME constant.
- Removed the craft\app\dates\DateTime::UTC constant.
- Removed the craft\app\dates\DateTime::DATEFIELD_24HOUR constant.
- Removed the craft\app\dates\DateTime::DATEFIELD_12HOUR constant.

### Fixed
- Fixed PHP error when Craft::$app->getIsUpdating() was called in a console request.
- Fixed a bug that occurred when applying a coupon to a Craft Client/Pro purchase.
- Fixed bug where StringHelper::startsWith() and endsWith() were returning strings instead of booleans.
- Fixed a bug where the $isNew property on entry draft events wasn’t getting set correctly.
- Fixed the default email settings.
- Fixed a PHP error that occurred when installing/updating/uninstalling a plugin that didn’t have a migrations/ folder.
- Fixed a bug where files that were registered via an asset bundle weren’t getting included when calling web\View::getHeadHtml().
- Fixed a bug where localized datepicker resources weren’t getting included properly.
- Fixed a bug where matrixblocks.ownerSiteId was getting set to NOT NULL for new Craft installs, resulting in MySQL errors when saving non-localized Matrix fields.
- Fixed a PHP error that occurred when saving a category.
- Fixed a JavaScript error in Craft.compare() that affected Live Preview.
- Fixed a bug where changes to tags’ titles made from the inline editor within Tags fields would not be reflected in the Tags field, for newly-selected tags.

## [v3.0.0-alpha.2939] - 2016-09-15

### Fixed
- Fixed a bug where helpers\Io::copyFolder() was not working.
- Fixed an "Entry is missing its type ID" bug when saving a entry without multiple entry types.
- Fixed a PHP error that occurred when saving a Matrix field's settings on a Craft install with only one Site.
- Fixed a couple places where Craft wasn't enforcing the PHP 5.6 requirement.
- Fixed a bug where Craft would attempt to create a 'migrations' folder within plugins' folders when checking if they have any new migrations.
- Fixed a bug where ordering element queries based on custom field values wasn't working if the orderBy param was set via the orderBy() method.
- Fixed a MySQL error that occurred when updating Craft when any plugins were installed.
- Fixed a bug where element queries were not always respecting the 'limit' and 'offset' params when the 'search' param was applied.
- Fixed a bug where element queries were non respecting the 'orderBy' param if it was set to "score" and the 'search' param was applied.

## [v3.0.0-alpha.2937] - 2016-09-13

### Changed
- It is now possible to override 'assetManager', 'cache', 'db', 'mailer', 'locale', 'formatter', and 'log' application components' configurations from craft/config/app.php.
- Renamed services\Plugins::getPluginInfo() to getAllPluginInfo().
- elements\MatrixBlock::getType() now throws an InvalidConfigException if the block type cannot be determined, rather than returning `null`.

### Fixed
- Fixed a MySQL error that occurred when updating a Craft install to Craft Dev 3.0.2933, if any user permissions had been assigned.
- Fixed an "Invalid entry type ID" error that occurred when creating a new entry.
- Fixed a bug where fields weren't passing validation, with no visible validation errors, when there was only one Site.
- Fixed a bug where elements' afterSave() functions and fields' afterElementSave() functions weren't getting called when saving new elements, which prevented Matrix and relationship fields' values from getting saved.
- Fixed a PHP error that occurred when executing `migrate` commands from the CLI.
- Fixed a bug where craft/config/db.php was not getting validated before attempting to establish a DB connection.

## [v3.0.0-alpha.2933] - 2016-09-12

### Added
- [Multi-site management!](https://craftcms.com/news/craft-3-multi-site)
- Locale permissions have been replaced by Site permissions.
- Sections and category groups can now choose to have URLs on a per-site basis.
- Field types that have a column in the content table now get a "Translation Method" setting (replacing the "This field is translatable" setting), with the options "Not translatable", "Translate for each language", "Translate for each site", and "Custom…".
- Matrix fields now have a "Manage blocks on a per-site basis" setting.
- Relationship fields now have a "Manage relations on a per-site basis" setting.
- Added support for a `CRAFT_SITE` PHP constant, which can be set to the handle of the current site.
- Element queries now support 'site', 'siteId', and 'enabledForSite' criteria params.
- Added services\Sites.
- Added validators\SiteId.
- Added Craft::$app->getIsMultiSite(), replacing getIsLocalized().
- Added Craft::$app->getSystemUid(), replacing getSiteUid().
- Added i18n\I18N::getSiteLanguages(), replacing getSiteLocaleIds().
- Added elements\User::getPreferredLanguage(), replacing getPreferredLocale().
- Added helpers\ElementHelper::getSupportedSitesForElement()
- Added helpers\ElementHelper::getEditableSiteIdsForElement(), replacing getEditableLocaleIdsForElement()
- Added ElementInterface::getSupportedSites(), replacing getLocales().
- Added Craft.isMultiSite for JavaScript, replacing Craft.isLocalized.
- Added Craft.systemUid for JavaScript, replacing Craft.siteUid.
- Added Craft.UriFormatGenerator for JavaScript, replacing Craft.EntryUrlFormatGenerator.
- Plugins now have a static $plugin property, which gets set to the instance of the plugin, if it has been initialized.

### Changed
- Routes can now be stored on a per-site basis, rather than per-locale.
- Renamed all "URL Format" things to "URI Format", in the Control Panel UI and in the code.
- Structure sections and category groups no longer have Nested URL Format settings. (It's still possible to achieve the same result with a single URI Format setting.)
- Fields that are translatable now have a chat bubble icon, replacing the locale ID badge.
- elements\Category::getGroup() now throws an InvalidConfigException if the category group type cannot be determined, rather than returning `null`.
- Renamed base\PluginTrait::releasesFeedUrl to releaseFeedUrl.
- mail\Message::setTo() will now set a 'user' variable on the message, if it was passed a User model.
- Craft.ui.createSelect() and createLightswitch() now initialize the FieldToggle class on the returned elements.

### Fixed
- Fixed a PHP error that occurred if a Position Select field had a blank first option.
- Fixed a bug where users without Control Panel access weren't getting redirected correctly after account activation.
- Fixed a JavaScript error that occurred when calling Craft.ui.createCheckboxField().

## [v3.0.0-alpha.2928] - 2016-08-31

### Added
- Table fields now have a "Lightswitch" column type option.
- Added a `|unique` Twig filter for filtering out duplicate values in an array.
- Added craft\app\validators\Unique, which can be used as a drop-in replacement for yii\validators\UniqueValidator, but with better support for validating a model's attributes based on an associated record's attributes.
- Added Craft.ui.createTextarea(), createTextareaField(), createLightswitch(), and createLightswitchField().
- Added a `lightswitch` column type option to editable tables.
- Text columns within editable tables can now define placeholder text.
- Editable table cell values can now be specified as an array with `value` and `hasErrors` (optional boolean) keys. Cells where `hasErrors` is set to `true` will get a red border.

### Changed
- Ported all recent changes from Craft 2.
- elements\Entry::getSection() and getType() now throw an InvalidConfigException if the section/entry type cannot be determined, rather than returning `null`.

### Fixed
- Fixed bug where `&nbsp;` was getting output for editable table headings that should have been empty.
- Fixed a bug where xregexp-all.js was not getting included properly when the useCompressedJs config setting was set to `true` (as it is by default).

## [v3.0.0-alpha.2918] - 2016-08-25

### Added
- Added $flavor and $inlineOnly arguments to the `|markdown` filter, making it possible to choose which Markdown flavor should be used, and whether it should parse paragraphs or treat the whole thing as inline elements.
- Added Craft::$app->getIsUpdating(), which returns `true` if Craft is undergoing an application update.
- Added the beforeSaveGlobalSet, afterSaveGlobalSet, beforeDeleteGlobalSet, and afterDeleteGlobalSet events to services\Globals.
- Added the beforeSaveUserGroup, afterSaveUserGroup, beforeDeleteUserGroup and afterDeleteUserGroup events to services\UserGroups.
- Added the beforeSaveAssetTransform, afterSaveAssetTransform, beforeDeleteAssetTransform, and afterDeleteAssetTransform events to services\AssetTransforms.
- Added the beforeDeleteEntryType and afterDeleteEntryType events to services\Sections.
- Added the afterInit event to web\Application and console\Application.
- Added the loginFailure event to elements\User.

### Changed
- All before/after-save events now have an $isNew property that identifies whether the object of the event is brand new or not.
- The _includes/forms/field include template now parses the passed in `warning` variable as an inline Markdown string, if it exists.
- The _includes/forms/editableTable include template now supports a `staticRows` variable, which if defined and set to `true` will prevent row CRUD operations in the resulting table.
- The _includes/forms/editableTable include template now supports a `heading` column type, which can be used in conjunction with `staticRows` to define row headings.
- The _includes/forms/editableTable include template now supports an `info` property on column configs, which will add an info button to the column header which reveas instruction text for the column.
- Renamed some event classes to be more consistent.

### Fixed
- Fixed a bug where it was not possible to replace a file from the Assets page.
- Fixed a bug where asset volumes' Cache Duration settings were not being respected.
- Fixed a bug that affected asset indexing reliability.
- Fixed a PHP error that occurred when rendering a Twig template from a console request.
- Fixed a bug where setting custom field attributes on an element query would prevent the query from executing.
- Fixed a bug where plugin Control Panel nav items were getting the full plugin class name in the URL instead of just handle.

## [v3.0.0-alpha.2915] - 2016-08-17

### Added
- User photos are now assets. (Note that the craft/storage/userphotos/ folder must be manually moved to a publicly accessible location, and the User Photos asset volume’s settings must be updated accordingly, for user photos to work properly.)
- Added User::getPhoto(), which returns the user’s photo asset, if they have one.
- Added StringHelper::randomStringWithChars().
- Added the ‘beforeSaveContent’ event to services\Content.
- Added the ‘beforeSaveDraft’ and ‘beforePublishDraft’ events to services\EntryRevisions.
- Added the ‘beforeSaveGroup’, ‘afterSaveGroup’, ‘beforeDeleteGroup’, and ‘afterDeleteGroup’ events to services\Tags.
- Added the ‘beforeSaveVolume’, ‘afterSaveVolume’, ‘beforeDeleteVolume’, and ‘afterDeleteVolume’ events to services\Volumes.
- Added the redirectInput() global function, which simplifies the code required to create `redirect` params.

### Changed
- All `redirect` params must now be hashed using the `|hash` filter (e.g. `<input type="hidden" name="redirect" value="{{ 'foo'|hash }}">`). A new redirectInput() global function makes this a little easier (e.g. `{{ redirectInput('foo') }}`).
- Ported recent changes from Craft 2.
- The getCsrfInput() global function has been renamed to csrfInput(). (getCsrfInput() still works but produces a deprecation error.)
- The `|hash` filter no longer requires a $key argument.
- Brought back getId(), getName(), and getNativeName() methods to Locale, so Craft 2 templates don’t need to be updated right away, although they will produce a deprecation error.

### Deprecated
- User::getPhotoUrl() is now deprecated in favor of `user.getPhoto.getUrl()`.

### Fixed
- Fixed a bug where helpers\StringHelper::toTitleCase() was returning all-lowercase strings.
- Fixed a typo where helpers\Json::encodeIfJson() should have been named decodeIfJson().
- Fixed a bug where element queries were not respecting the ‘offset’ property.
- Fixed a MySQL error that occurred when querying users with the ‘can’ property.
- Fixed a MySQL error that occurred when executing an element query that had custom query params.
- Fixed a PHP error that occurred when rendering templates with a {% paginate %} tag. (Note that you may need to delete your craft/storage/runtime/compiled_templates/ folder.)
- Fixed a PHP warning that occurred when using services\Feeds on a server running PHP 7.
- Fixed a few bugs related to saving elements on localized sites.
- Fixed tag group saving and deleting.
- Fixed a bug where all global sets were listed in the sidebar of the Globals page, rather than just the ones the user had permission to edit.
- Fixed a bug where nested Structure section entries were not showing the parent entry on their edit pages.
- Fixed a bug where nested categories were not showing the parent category on their edit pages.
- Fixed a MySQL error that occurred when saving a nested Structure section entry.
- Fixed a MySQL error that occurred when saving a nested category.
- Fixed a bug where User elements returned by services\Users::getUserByUsernameOrEmail() and getUserByEmail() were missing their content.
- Fixed a bug where user info like Last Login Date was not getting updated when users logged in.
- Fixed a bug where email messages were not respecting the recipient user’s preferred language.
- Fixed a PHP error that occurred when saving an element with an invalid field, if the field’s value couldn’t be converted to a string.
- Fixed a PHP error that occurred when sharing an entry or category with a tokenized share URL.
- Fixed a bug where the ‘dateCreated’, ‘dateUpdated’, and ‘uid’ attributes on new ActiveRecord’s would not necessarily reflect the values in the database, after saving the record for the first time.
- Fixed a bug that broke cookie-based authentication.
- Fixed a bug where temporary asset uploads were getting deleted when clearing data caches.
- Fixed a JavaScript error that occurred in the Control Panel.

### Security
- services\Security::hashData and validateData() no longer require a $key argument.

## [v3.0.0-alpha.2910] - 2016-08-04

### Added
- Ported all the changes from Craft 2.
- Craft 3 now requires PHP 5.6 or later.
- Craft 3 now requires MySQL 5.5 or later.
- The debug toolbar now has the Craft “C” logo rather than the Yii logo.
- `craft.app` now points to the same Application object that `Craft::$app` references in PHP, giving templates access to literally all of the service methods, etc., that PHP code has access to.
- Added the `is missing` Twig test for determining if an object implements base\MissingComponentInterface.
- Added a new “uid” parameter to element queries, for fetching elements by their UID.
- Elements’ UIDs are now fetched in element queries, and stored as a “uid” property on the resulting elements.
- Added support for Reduced Redundancy Storage and Infrequent Access Storage for Amazon S3 asset volumes.
- Added the beforeCreateBackup event to db\Connection.
- Added the beforeSaveGroup and afterSaveGroup events to services\Categories.
- Documented all magic properties and methods in class doc blocks, so IDEs should always know what’s available.
- Added db\mysql\Schema::TYPE_TINYTEXT and TYPE_LONGTEXT, and added custom support for them in db\mysql\ColumnSchemaBuilder.
- Added db\Migration::tinyText(), mediumText(), longText(), enum(), and uid() column schema definition helpers.
- Added errors\DbBackupException.
- Added errors\DbUpdateException.
- Added errors\DownloadPackageException.
- Added errors\FilePermissionsException.
- Added errors\InvalidateCacheException.
- Added errors\MinimumRequirementException.
- Added errors\MissingFileException.
- Added errors\UnpackPackageException.
- Added errors\ValidatePackageException.
- Added helpers\Localization::getLocaleData().
- Added volumes\Temp.
- Added a $format argument to i18n\Locale::getDateFormat(), getTimeFormat(), and getDateTimeFormat(), for specifying whether the returned string should be in the ICU, PHP, or jQuery UI date/time format.

### Changed
- Craft now checks for a plugin.json file within plugin directories, rather than config.json.
- Plugin-based email messages now get translated using the plugin handle as the translation category, rather than “app”.
- “owner” is now a reserved field handle.
- Made lots of improvements to exceptions.
- Improved the handling of invalid locale IDs. (Work in progress, though.)
- Renamed lots of class and method names to be more in-line with Yii conventions.
- Renamed the i18n\Locale::FORMAT_* constants to LENGTH_*.
- Renamed web\View::includeTranslations() to registerTranslations(), and it now accepts two arguments: $category and $messages.
- db\Command::createTable() and db\Migration::createTable() no longer accept $addIdColumn or $addAuditColumn arguments. The ‘id’, ‘dateCreated’, ‘dateUpdated’, and ‘uid’ columns are now expected to be included in the $columns array.
- web\View::clearJsBuffer() no longer returns an array.
- Renamed Craft.locale to Craft.language in JS.
- Craft.t() in JS now accepts a category argument, like Craft::t() in PHP.
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
- Removed the overridePhpSessionLocation config setting.
- Removed `craft.elements`.
- Removed the `{% includeTranslations %}` tag.
- Removed the db\InstallMigration class.
- Removed the web\twig\variables\ComponentInfo class.

### Fixed
- Fixed a lot of bugs. OK?

## [v3.0.0-alpha.2687] - 2015-08-20

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the ‘showBetaUpdates’ config setting.
- Added StringHelper::delimit().
- Added StringHelper::indexOf().
- Added StringHelper::indexOfLast().
- Added StringHelper::lines().

### Changed
- Image transforms’ Quality settings are now represented as a dropdown input in the Control Panel, with the options “Auto”, “Low”, “Medium”, “High”, “Very High (Recommended)”, and “Maximum”.
- It is now possible to access Craft.Grid objects from their container elements, via `.data('grid')`.
- It is now possible to access Craft.BaseElementSelectInput objects from their container elements, via `.data('elementSelect')`.
- Craft now displays helpful errors after failed asset uploads.

### Fixed
- Fixed an error that occurred when backing up the database.
- Fixed a bug where plugin settings were getting HTML-encoded.
- Fixed an error that occurred when deleting a field group.
- Fixed a MySQL error that occurred when saving a category group without URLs.
- Fixed an error that occurred when fields existed with invalid types, if there was no corresponding column in the database.
- Fixed a MySQL error that occurred when saving user groups.
- Fixed a bug where user group names and handles were not being validated for uniqueness.
- Fixed a bug where route params registered with UrlManager::setRouteParams() were not being passed to the template, for requests that ultimately get routed to a template.
- Fixed a bug where no Admin user would be created when installing Craft with the ‘useEmailAsUsername’ config setting set to `true`.
- Fixed a bug where Title fields were not being validated.
- Fixed a bug where custom fields’ validation errors were not being reported after attempting to save elements.
- Fixed an error that occurred when attempting to save an element with a required Rich Text field that had no value.
- Fixed a bug where PHP could easily run out of memory when unzipping large zip files.
- Fixed a bug where MigrationHelper would forget that it had dropped a table after dropTable() was called.
- Fixed an error that would occur if another error occurred during a MySQL transaction that included savepoints, and the savepoints had already been implicitly committed.

## [v3.0.0-alpha.2681] - 2015-07-22

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the ‘enableCsrfCookie’ config setting, which determines whether CSRF tokens should be saved in cookies. Defaults to `true`. CSRF tokens will be stored in the PHP session if set to `false`.
- The Content model has been removed. Elements now have ContentBehavior’s directly. Because speed!
- Added ElementTrait::$contentId for storing the content row’s ID.
- Added ElementTrait::$title for storing the element’s title, replacing ElementInterface::getTitle().
- Added ElementInterface::getFieldValues(), replacing getContent().
- Added ElementInterface::setFieldValues(), replacing setContent().
- Added ElementInterface::setFieldValuesFromPost(), replacing setContentFromPost().
- Added ElementInterface::setRawPostValueForField(), replacing setRawPostContent().
- Added ElementInterface::setFieldValue().
- All `$value` arguments on Field methods are now set to the prepared field value, with the sole exception of prepareValue().
- Added FieldInterface::prepareValueForDb(), giving fields direct control over how their values should be saved to the database, without affecting the value stored on the element. (This replaces the protected prepareValueBeforeSave() method.)
- Added protected Field::isValueEmpty() which aids base\Field::validateValue() in required-field validation.
- Added DateTimeHelper::normalizeTimeZone().
- Added craft.getTimeZone() for Control Panel JavaScript.
- Added the craft\app\base\Savable interface. Objects that implement it have control over how DbHelper::prepareValueForDb() prepares them to be saved to the database.
- Added craft\app\web\View::getBodyHtml(), replacing getBodyBeginHtml() and getBodyEndHtml().

### Changed
- Updated Yii to 2.0.5.
- Element queries are no longer limited to 100 results by default.
- ElementQuery::count() now returns the total cached results, if set.
- FieldInterface::validateValue() is now responsible for required-field validation (and a basic is-empty check is included in base/Field).
- FieldInterface::validateValue() no longer needs to return `true` if the value passed validation.
- DateTimeHelper::toDateTime()’s `$timezone` argument has been replaced with `$assumeSystemTimeZone`. If set to `true` and if `$value` doesn’t have an explicit time zone, the method will use the system’s time zone. Otherwise UTC will be used. (Defaults to `false`.)
- DateTimeHelper::toDateTime() now checks for a `timezone` key when `$value` is in the date/time-picker array format.
- DateTimeHelper::toDateTime() now returns `false` instead of `null` when an array without `'date'` or `'time'` keys is passed in.
- Craft’s extended DateTime::createFromFormat() function no longer sets the `$timezone` argument if left null, matching the base DateTime class’s behavior.
- Date/time-pickers in the CP now explicitly declare that they are using the system time zone in their post data.
- Nested database transactions will now set savepoints within the master transaction, as MySQL doesn’t support nested transactions.
- PluginClassName::getInstance() will now return the singular instance of the plugin class.

### Removed
- Removed Craft’s extended DateTime::format() method, so it no longer has a `$timezone` argument and automatically sets the time zone on the DateTime object.

### Fixed
- Fixed many, many bugs.

## [v3.0.0-alpha.2671] - 2015-06-18

### Added
- Ported all new features and improvements that were introduced in [Craft 2.4](http://buildwithcraft.com/updates#build2664).
- The codebase now follows the [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style.
- Added support for config/app.php, which can return an array that will be merged with Craft’s core application config array.
- Craft now looks for translation files at craft/translations/locale-ID/category.php, where `category` can either be “app”, “site”, “yii”, or a plugin’s handle.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the “site” category, to prevent translation conflicts with Craft’s own Control Panel translations.
- Craft now uses SwiftMailer to send emails.
- Added the ability for plugins to provide custom SwiftMailer transport options.
- Added DateTimeHelper::toIso8601(), for converting a date (or date-formatted string) into an ISO-8601-formatted string.
- Added DateTimeHelper::isIso8601(), for determining if a given variable is an ISO-8601-formatted string.
- Added DateTimeHelper::translateDate(), for translating month and weekday names from English to the active application language.
- Added DbHeleper::prepareDateForDb(), which replaces the old DateTimeHelper::formatTimeForDb().
- Added FieldLayoutBehavior, which replaces FieldLayoutTrait, enabling a single class to have more than one field layout associated with it.
- The localized date format used by JavaScript date pickers is now available from `Craft.datepickerFormat`.
- Added the JavaScript method, Craft.formatDate(), for formatting a date (or date-formatted string) into a string using the same localized date format used by JavaScript date pickers.

### Changed
- The translationDebugOutput config setting will now wrap strings with `@` characters if the category is “app”, `$` if the category is “site”, and `%` for anything else.
- Web requests are now logged to craft/storage/logs/web.log.
- Web requests that result in 404 errors are now logged to craft/storage/logs/web-404s.log.
- Console requests are now logged to craft/storage/logs/console.log.
- Template error handling now works similarly to how it does in Craft 2 when Craft is running in Dev Mode, where the template source is shown in the error view.
- Twig class names now link to their respective class reference URLs in the error view’s stack trace.
- The `registercss`, `registerhirescss`, and `registerjs` tags can now be used as tag pairs.
- The `registerassetbundle`, `registerjs`, `registerjsfile`, and `registerjsresource` tags now support an `at` param for specifying the location on the page their HTML tags should be placed. (Possible values are `at head`, `at beginBody`, and `at endBody`.)
- The `registerjs` tag now supports an `on` param for specifying when the JavaScript code should be executed. (Possible values are `on ready` and `on load`.) (The `at` and `on` parameters are mutually exclusive.)
- The `registercss`, `registerhirescss`, `registercssfile`, `registercssresource`, `registerjsfile`, and `registerjsresource` tags now support a `with` param, which can be set to an object that specifies additional attributes on the resulting tags (e.g. `with { rel: "stylesheet" }`).
- The `t` filter now always defaults to translating the given string using the “site” category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- `@craft/plugins/pluginhandle` aliases are now registered _before_ Craft attempts to load a plugin’s primary class, making it easier for plugins to use custom class names.
- Model::toArray() now converts DateTime attributes to ISO-8601 strings.
- Renamed AssetHelper::cleanAssetName() to prepareAssetName().
- Renamed DbHelper::prepObjectValues() to prepareValuesForDb().
- Renamed DbHelper::prepValue() to prepareValueForDb().
- Renamed Event::$performAction to $isValid to be consistent with Yii events.
- DateTimeHelper::toDateTime() will now return `false` if it is unable to convert `$object` to a DateTime object.
- DateTimeHelper::toDateTime() now supports ISO-8601-formatted dates.
- DbHelper::prepareValueForDb() now treats ISO-8601 strings as dates, and converts them to the MySQL date format.

### Deprecated
- All the new `register*` tags must be all-lowercase now. (The old `include*` tags still work as they do in Craft 2, however they remain deprecated.)

### Removed
- Removed DateTimeHelper::currentTimeForDb().
- Removed DateTimeHelper::formatTimeForDb().

### Fixed
- Fixed a bug where Craft was not enforcing required entry titles and custom fields.
- Fixed a bug where it was not possible to create new Volumes on Craft Personal/Craft Client installs.
- Fixed an error that occurred when using the `type` param when querying for Matrix blocks.
- Fixed an “IP address restriction” error that prevented the Debug toolbar from showing up for some people.
- Fixed a PHP error that would occur when converting models without $dateCreated or $dateUpdated properties to an array.
- Fixed a PHP error that occurred when resetting a password.
- Fixed a PHP error that occurred when sending an activation email.
- Fixed a PHP error that occurred when saving an entry with validation errors, or when loading a draft for an entry, if the entry had any Matrix fields.
- Fixed a PHP error that occurred when editing an entry draft or version.
- Fixed a MySQL error that occurred when saving new image transforms.
- Fixed a MySQL error that occurred when saving a section with entry versioning disabled.
- Fixed View::registerHiResCss(), and the {% registerhirescss %} tag.
- Fixed a bug that prevented user and email settings from being remembered.
- Fixed a JavaScript error that occurred on pages with fields that could be toggled by a checkbox.

## [v3.0.0-alpha.2663] - 2015-05-27

### Changed
- Plugins can now add an array of Twig extensions from the `addTwigExtension` hook.

### Fixed
- Fixed a PHP error that occurred when installing Craft or validating URLs if the Intl extension wasn’t loaded.

## [v3.0.0-alpha.2661] - 2015-05-22

### Added
- Added a `withPassword` criteria parameter to User queries, which includes the users’ hashed passwords in the query and sets them on the resulting User models.
- Added a `$setToSystemTimeZone` argument to DateTimeHelper::toDateTime(), which will set the resulting DateTime object to the system’s Timezone setting (defaults to `true`).
- Added the ‘convertFilenamesToAscii’ config setting.

### Changed
- The Debug toolbar no longer requires Dev Mode to be enabled.
- URL validation now accounts for URLs that include environment variables.
- URL validation now allows international domain names.
- ‘contentTable’ is now a reserved field handle.
- Craft is now distributed with a public/cpresources folder, which should be set with writable permissions.
- Craft now ensures that the public Resources folder exists and is writable at a much earlier stage so it can give a more helpful error message.

### Fixed
- Fixed a bug where many locales had the wrong display name in the CP if the Intl extension was not loaded.
- Fixed an error that occurred when saving a Volume using Craft Personal or Craft Client.
- Fixed an error error that occurred when validating a URL that included an environment variable.
- Fixed a bug where some dates were being output with the wrong timezone.
- Fixed an error that occurred when saving a DateTime field set to only show the timepicker.
- Fixed a UI glitch where the bottom shadow of entry Save buttons would span the full width of the grid column if entry versioning was disabled or unavailable.
- Fixed a bug where the Min Build Required error message was getting HTML-encoded.
- Fixed a couple errors in that could occur if Craft didn’t receive the expected response from its web service.
- Fixed an error that occurred when attempting to log in with the wrong password multiple times.
- Fixed a bug where users would become permanently locked when their cooldown period had expired.
- Fixed a “Craft Client is required” error when editing entries in Craft Personal.
- Fixed an error that occurred after an Admin entered their password in order to change a user account’s email/password.
- Fixed a validation error on the New Password field if a user attempted to update their email address but didn’t want to change their existing password.
- Corrected the default config paths in the comments of config/db.php and config/general.php.
- Fixed a bug that resulted in the Updates page never getting past the “Checking for updates” step when an update was available.

## [v3.0.0-alpha.2659] - 2015-05-19

### Added
- Added support for registering plugin resources via View::registerCssResource() and registerJsResource().
- Added Element::getStructureId(), setStructureId(), and resolveStructureId().

### Changed
- Drastically reduced the likelihood of importing a database backup with a falsely-identical schemaVersion as the files stored in craft/storage/runtime/compiled_classes/.

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
- Fixed a bug where database backups would get stored in `craft/storagebackups/` instead of `craft/storage/backups/`.
- Fixed a bug where the Min Build Required error message had encoded HTML.

## v3.0.0-alpha.2657 - 2015-05-19

### Added
- Completely rewritten and refactored codebase, powered by [Yii 2](http://www.yiiframework.com/).
- Improved internationalization support with PHP’s [Intl extension](http://php.net/manual/en/book.intl.php) and [Stringy](https://github.com/danielstjules/Stringy).
- Plugins are now loaded as Yii [modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html).
- Asset Sources are now called Volumes, and plugins can supply their own Volume Types (made easy with [Flysystem](http://flysystem.thephpleague.com/)).
- It is now possibly to customize the SQL of element queries, and there are more choices on how the data should be returned.
- Included the [Yii 2 Debug Extension](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).

[Unreleased]: https://github.com/craftcms/craft/compare/v3.0.0-alpha.2948...develop
[v3.0.0-alpha.2948]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2942...3.0.0-alpha.2948
[v3.0.0-alpha.2942]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2939...3.0.0-alpha.2942
[v3.0.0-alpha.2939]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2937...3.0.0-alpha.2939
[v3.0.0-alpha.2937]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2933...3.0.0-alpha.2937
[v3.0.0-alpha.2933]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2928...3.0.0-alpha.2933
[v3.0.0-alpha.2928]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2918...3.0.0-alpha.2928
[v3.0.0-alpha.2918]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2915...3.0.0-alpha.2918
[v3.0.0-alpha.2915]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2910...3.0.0-alpha.2915
[v3.0.0-alpha.2910]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2687...3.0.0-alpha.2910
[v3.0.0-alpha.2687]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2681...3.0.0-alpha.2687
[v3.0.0-alpha.2681]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2671...3.0.0-alpha.2681
[v3.0.0-alpha.2671]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2663...3.0.0-alpha.2671
[v3.0.0-alpha.2663]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2661...3.0.0-alpha.2663
[v3.0.0-alpha.2661]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2659...3.0.0-alpha.2661
[v3.0.0-alpha.2659]: https://github.com/craftcms/craft/compare/3.0.0-alpha.2657...3.0.0-alpha.2659
