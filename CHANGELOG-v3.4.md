# Running Release Notes for Craft 3.4

> {warning} If `useProjectConfigFile` is enabled and you are using the GraphQL API, restore a fresh database backup from your production environment before updating your development environment. Otherwise you may lose your GraphQL schema data when updating production.

> {tip} Element search indexing is a little smarter in Craft 3.4. It’s recommended that you resave all your entries from your terminal after updating.
>
> ```bash
> > ./craft resave/entries --update-search-index
> ```

### Added
- Improved the overall look and feel of the Control Panel. ([#2883](https://github.com/craftcms/cms/issues/2883))
- Added an overflow menu for Control Panel tabs that don’t fit into the available space. ([#3073](https://github.com/craftcms/cms/issues/3073))
- Added support for delta element updates. ([#4064](https://github.com/craftcms/cms/issues/4064))
- Elements now track which field values have changed since the element was first loaded. ([#4149](https://github.com/craftcms/cms/issues/4149))
- Entry drafts now show which fields and attributes have changed within the draft, and which are outdated.
- If an entry draft contains outdated field and attribute values, it’s now possible to merge the latest source entry values into the draft manually, and they will be automatically merged in when the draft is published. ([#4642](https://github.com/craftcms/cms/issues/4642))
- It’s now possible to see all of the elements selected by relation fields from element indexes. ([#3030](https://github.com/craftcms/cms/issues/3030))
- It’s now possible to download multiple assets at once as a zip file. ([#5259](https://github.com/craftcms/cms/issues/5259))
- It’s now possible to preview HTML and PDF assets, and plugins can add support for additional file types. ([#5136](https://github.com/craftcms/cms/pull/5136))
- Added the `verifyEmailPath` config setting.
- Added the `maxBackups` config setting. ([#2078](https://github.com/craftcms/cms/issues/2078))
- Added the `{% requireGuest %}` Twig tag, which redirects a user to the path specified by the `postLoginRedirect` config setting if they’re already logged in. ([#5015](https://github.com/craftcms/cms/pull/5015))
- Added the `|purify` Twig filter. ([#5184](https://github.com/craftcms/cms/issues/5184))
- It’s now possible to query for Matrix blocks by their field handle, via the new `field` param. ([#5218](https://github.com/craftcms/cms/issues/5218))
- It’s now possible to eager-load the *count* of related elements, by setting `'count' => true` on the eager-loading criteria. 
- GraphQL access tokens are now managed separately from schema definitions, making it possible to create multiple tokens for the same schema.
- GraphQL schemas are now stored in the project config (sans tokens). ([#4829]((https://github.com/craftcms/cms/issues/4829))
- Added `craft\assetpreviews\HtmlPreview`.
- Added `craft\assetpreviews\ImagePreview`.
- Added `craft\assetpreviews\NoPreview`.
- Added `craft\assetpreviews\PdfPreview`.
- Added `craft\base\AssetPreviewInterface`.
- Added `craft\base\AssetPreviewTrait`.
- Added `craft\base\AssetPreview`.
- Added `craft\base\Element::ATTR_STATUS_CONFLICTED`.
- Added `craft\base\Element::ATTR_STATUS_MODIFIED`.
- Added `craft\base\Element::ATTR_STATUS_OUTDATED`.
- Added `craft\base\ElementInterface::getAttributeStatus()`.
- Added `craft\base\ElementInterface::getDirtyAttributes()`.
- Added `craft\base\ElementInterface::getDirtyFields()`.
- Added `craft\base\ElementInterface::getEagerLoadedElementCount()`.
- Added `craft\base\ElementInterface::getFieldStatus()`.
- Added `craft\base\ElementInterface::isFieldDirty()`.
- Added `craft\base\ElementInterface::markAsClean()`.
- Added `craft\base\ElementInterface::setAttributeStatus()`.
- Added `craft\base\ElementInterface::setEagerLoadedElementCount()`.
- Added `craft\base\ElementInterface::trackChanges()`.
- Added `craft\base\FieldInterface::getTranslationDescription()`.
- Added `craft\behaviors\DraftBehavior::$dateLastMerged`.
- Added `craft\behaviors\DraftBehavior::$mergingChanges`.
- Added `craft\behaviors\DraftBehavior::$trackChanges`.
- Added `craft\behaviors\DraftBehavior::getIsOutdated()`.
- Added `craft\behaviors\DraftBehavior::getOutdatedAttributes()`.
- Added `craft\behaviors\DraftBehavior::getOutdatedFields()`.
- Added `craft\behaviors\DraftBehavior::isAttributeModified()`.
- Added `craft\behaviors\DraftBehavior::isAttributeOutdated()`.
- Added `craft\behaviors\DraftBehavior::isFieldModified()`.
- Added `craft\behaviors\DraftBehavior::isFieldOutdated()`.
- Added `craft\controllers\DraftsController`.
- Added `craft\controllers\GraphqlController::actionDeleteToken()`.
- Added `craft\controllers\GraphqlController::actionEditPublicSchema()`.
- Added `craft\controllers\GraphqlController::actionEditPublicSchema()`.
- Added `craft\controllers\GraphqlController::actionEditToken()`.
- Added `craft\controllers\GraphqlController::actionSaveToken()`.
- Added `craft\controllers\GraphqlController::actionViewToken()`.
- Added `craft\db\Connection::DRIVER_MYSQL`.
- Added `craft\db\Connection::DRIVER_PGSQL`.
- Added `craft\elements\MatrixBlock::$dirty`.
- Added `craft\elements\db\ElementQuery::clearCachedResult()`.
- Added `craft\elements\db\MatrixBlockQuery::field()`.
- Added `craft\events\AssetPreviewEvent`.
- Added `craft\events\DefineGqlTypeFieldsEvent`.
- Added `craft\events\DefineGqlValidationRulesEvent`.
- Added `craft\events\ExecuteGqlQueryEvent::$schemaId`.
- Added `craft\events\RegisterGqlPermissionsEvent`.
- Added `craft\events\TemplateEvent::$templateMode`.
- Added `craft\gql\TypeManager`.
- Added `craft\helpers\ArrayHelper::append()`.
- Added `craft\helpers\ArrayHelper::prepend()`.
- Added `craft\helpers\Db::parseDsn()`.
- Added `craft\helpers\Db::url2config()`.
- Added `craft\helpers\FileHelper::writeGitignoreFile()`.
- Added `craft\helpers\ProjectConfigHelper::packAssociativeArray()`.
- Added `craft\helpers\ProjectConfigHelper::unpackAssociativeArray()`.
- Added `craft\models\GqlToken`.
- Added `craft\queue\jobs\UpdateSearchIndex::$fieldHandles`.
- Added `craft\records\GqlToken`.
- Added `craft\services\Assets::EVENT_GET_ASSET_PREVIEW`.
- Added `craft\services\Assets::getAssetPreview()`.
- Added `craft\services\Drafts::EVENT_AFTER_MERGE_SOURCE_CHANGES`.
- Added `craft\services\Drafts::EVENT_BEFORE_MERGE_SOURCE_CHANGES`.
- Added `craft\services\Drafts::mergeSourceChanges()`.
- Added `craft\services\Gql::CONFIG_GQL_SCHEMAS_KEY`.
- Added `craft\services\Gql::EVENT_REGISTER_GQL_PERMISSIONS`.
- Added `craft\services\Gql::deleteSchema()`.
- Added `craft\services\Gql::deleteTokenById()`.
- Added `craft\services\Gql::getSchemaByUid()`.
- Added `craft\services\Gql::getTokenByAccessToken()`.
- Added `craft\services\Gql::getTokenById()`.
- Added `craft\services\Gql::getTokenByUid()`.
- Added `craft\services\Gql::getTokens()`.
- Added `craft\services\Gql::getValidationRules()`.
- Added `craft\services\Gql::handleChangedSchema()`.
- Added `craft\services\Gql::handleDeletedSchema()`.
- Added `craft\services\Gql::saveToken()`.
- Added `craft\services\Plugins::$pluginConfigs`. ([#1989](https://github.com/craftcms/cms/issues/1989))
- Added `craft\web\Controller::requireGuest()`.
- Added `craft\web\User::guestRequired()`.
- Added `craft\web\View::$minifyCss`.
- Added `craft\web\View::$minifyJs`.
- Added `craft\web\View::getDeltaNames()`.
- Added `craft\web\View::getIsDeltaRegistrationActive()`.
- Added `craft\web\View::registerDeltaName()`.
- Added `craft\web\View::setIsDeltaRegistrationActive()`.
- Added `craft\web\twig\nodes\RequireGuestNode`.
- Added `craft\web\twig\tokenparsers\RequireGuestTokenParser`.
- Added `craft\web\twig\variables\Paginate::getDynamicRangeUrls()`, making it easy to create Google-style pagination links. ([#5005](https://github.com/craftcms/cms/issues/5005))
- Added the `cp.users.edit.prefs` template hook to the Edit User page. ([#5114](https://github.com/craftcms/cms/issues/5114))
- Added the [Interactive Shell Extension for Yii 2](https://github.com/yiisoft/yii2-shell).
- Added the Minify PHP package.

### Changed
- Control panel requests are now always set to the primary site, regardless of the URL they were accessed from.
- The control panel no longer shows the tab bar on pages with only one tab. ([#2915](https://github.com/craftcms/cms/issues/2915))
- Sections’ entry URI format settings are now shown when running Craft in headless mode. ([#4934](https://github.com/craftcms/cms/issues/4934))
- The “Primary entry page” preview target is now user-customizable alongside all other preview targets in sections’ settings. ([#4520](https://github.com/craftcms/cms/issues/4520))
- Plain Text fields can now specify a maximum size in bytes. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Plain Text fields’ Column Type settings now have an “Automatic” option, which is selected by default for new fields. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Matrix fields now show an accurate description of their propagation behavior in the translation icon tooltip. ([#5304](https://github.com/craftcms/cms/issues/5304))
- Local asset volumes now ensure that their folder exists on save, and if it doesn’t, a `.gitignore` file will be added automatically to it, excluding the directory from Git. ([#5237](https://github.com/craftcms/cms/issues/5237))
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
- Craft now uses the `slugWordSeparator` when generating URI formats. ([#5315](https://github.com/craftcms/cms/pull/5315))
- CSS registered with `craft\web\View::registerCss()` or the `{% css %}` tag is now minified by default. ([#5183}https://github.com/craftcms/cms/issues/5183])
- JavaScript code registered with `craft\web\registerJs()` or the `{% js %}` tag is now minified per the `useCompressedJs` config setting. ([#5183}https://github.com/craftcms/cms/issues/5183])
- `resave/*` commands now have an `--update-search-index` argument (defaults to `false`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- The installer now requires `config/db.php` to be setting the `dsn` database config setting with a `DB_DSN` environment variable, if a connection can’t already be established.
- The full GraphQL schema is now always generated when Dev Mode is enabled.
- Punctuation is now removed from search keywords and search terms, rather than being replaced with a space. ([#5214](https://github.com/craftcms/cms/issues/5214))
- The `_includes/forms/field.html` template now supports `fieldAttributes`, `labelAttributes`, and `inputAttributes` variables.
- The `_includes/field.html` template now supports a `registerDeltas` variable.
- The `_layouts/cp.html` template now supports `mainAttributes` and `mainFormAttributes` variables.
- Plugins can now modify the GraphQL schema via `craft\gql\TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS`.
- Plugins can now modify the GraphQL permissions via `craft\services\Gql::EVENT_REGISTER_GQL_PERMISSIONS`.
- Renamed the`QueryParameter` GraphQL type to `QueryArgument`.
- Project config now sorts the `project.yaml` file alphabetically by keys. ([#5147](https://github.com/craftcms/cms/issues/5147))
- Active record classes now normalize attribute values right when they are set.
- `craft\models\GqlSchema::$scope` is now read-only.
- `craft\services\Elements::resaveElements()` now has an `$updateSearchIndex` argument (defaults to `false`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- `craft\services\Elements::saveElement()` now has an `$updateSearchIndex` argument (defaults to `true`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- `craft\services\Search::indexElementAttributes()` now has a `$fieldHandles` argument, for specifying which custom fields’ keywords should be updated.
- `craft\web\Controller::renderTemplate()` now has a `$templateMode` argument.
- `craft\web\View::renderTemplate()`, `renderPageTemplate()`, `renderTemplateMacro()`, `doesTemplateExist()`, and `resolveTemplate()` now have `$templateMode` arguments. ([#4570](https://github.com/craftcms/cms/pull/4570))
- Updated Yii to 2.0.29.

### Deprecated
- Deprecated the `url`, `driver`, `database`, `server`, `port`, and `unixSocket` database config settings. `dsn` should be used instead.
- Deprecated `craft\config\DbConfig::DRIVER_MYSQL`.
- Deprecated `craft\config\DbConfig::DRIVER_PGSQL`.
- Deprecated `craft\config\DbConfig::updateDsn()`.
- Deprecated `craft\elements\Asset::getSupportsPreview()`. Use `craft\services\Assets::getAssetPreview()` instead.
- Deprecated `craft\events\ExecuteGqlQueryEvent::$accessToken`. Use `craft\events\ExecuteGqlQueryEvent::$schemaId` instead.
- Deprecated `craft\services\Search::indexElementFields()`.

### Removed
- Removed `craft\models\GqlSchema::PUBLIC_TOKEN`.
- Removed `craft\models\GqlSchema::$accessToken`.
- Removed `craft\models\GqlSchema::$enabled`.
- Removed `craft\models\GqlSchema::$expiryDate`.
- Removed `craft\models\GqlSchema::$lastUsed`.
- Removed `craft\models\GqlSchema::$dateCreated`.
- Removed `craft\models\GqlSchema::$isTemporary`.
- Removed `craft\models\GqlSchema::getIsPublic()`.

### Fixed
- Fixed a SQL error that could occur if the `info` table has more than one row. ([#5222](https://github.com/craftcms/cms/issues/5222))
