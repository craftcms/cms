# Running Release Notes for Craft 3.4

> {tip} Element search indexing is a little smarter in Craft 3.4. It’s recommended that you resave all your entries from your terminal after updating.
>
> ```bash
> > ./craft resave/entries --update-search-index
> ```

### Added
- Improved the overall look and feel of the Control Panel. ([#2883](https://github.com/craftcms/cms/issues/2883))
- Added an overflow menu for Control Panel tabs that don’t fit into the available space. ([#3073](https://github.com/craftcms/cms/issues/3073))
- Added support for delta element updates. ([#4064](https://github.com/craftcms/cms/issues/4064))
- Split GraphQL Schemas into schemas and tokens. Schemas are now assigned to tokens, allowing multiple tokens to use the same schema.
- Add Project Config support for GraphQL schemas. ([#4829]((https://github.com/craftcms/cms/issues/4829))
- Elements now track which field values have changed since the element was first loaded. ([#4149](https://github.com/craftcms/cms/issues/4149))
- It’s now possible to preview HTML and PDF assets, and plugins can add support for additional file types. ([#5136](https://github.com/craftcms/cms/pull/5136))
- Added the `verifyEmailPath` config setting.
- Added the `maxBackups` config setting. ([#2078](https://github.com/craftcms/cms/issues/2078))
- Added the `{% requireGuest %}` Twig tag, which redirects a user to the path specified by the `postLoginRedirect` config setting if they’re already logged in. ([#5015](https://github.com/craftcms/cms/pull/5015))
- Added the `|purify` Twig filter. ([#5184](https://github.com/craftcms/cms/issues/5184))
- It’s now possible to query for Matrix blocks by their field handle, via the new `field` param. ([#5218](https://github.com/craftcms/cms/issues/5218))
- Added `craft\assetpreviews\HtmlPreview`.
- Added `craft\assetpreviews\ImagePreview`.
- Added `craft\assetpreviews\NoPreview`.
- Added `craft\assetpreviews\PdfPreview`.
- Added `craft\base\AssetPreview`.
- Added `craft\base\AssetPreviewInterface`.
- Added `craft\base\AssetPreviewTrait`.
- Added `craft\base\ElementInterface::clearDirtyFields()`.
- Added `craft\base\ElementInterface::getDirtyFields()`.
- Added `craft\base\ElementInterface::isFieldDirty()`.
- Added `craft\controllers\GraphqlController::actionDeleteToken()`.
- Added `craft\controllers\GraphqlController::actionEditToken()`.
- Added `craft\controllers\GraphqlController::actionSaveToken()`.
- Added `craft\controllers\GraphqlController::actionViewToken()`.
- Added `craft\db\Connection::DRIVER_MYSQL`.
- Added `craft\db\Connection::DRIVER_PGSQL`.
- Added `craft\elements\db\MatrixBlockQuery::field()`.
- Added `craft\elements\MatrixBlock::$dirty`.
- Added `craft\events\AssetPreviewEvent`.
- Added `craft\events\DefineGqlTypeFieldsEvent`.
- Added `craft\events\DefineGqlValidationRulesEvent`.
- Added `craft\events\ExecuteGqlQueryEvent::$schemaId`.
- Added `craft\events\RegisterGqlPermissionsEvent`.
- Added `craft\events\TemplateEvent::$templateMode`.
- Added `craft\gql\TypeManager`.
- Added `craft\helpers\Db::parseDsn()`.
- Added `craft\helpers\Db::url2config()`.
- Added `craft\helpers\Gql::createFullAccessToken()`.
- Added `craft\helpers\FileHelper::writeGitignoreFile()`
- Added `craft\models\GqlToken`.
- Added `craft\queue\jobs\UpdateSearchIndex::$fieldHandles`.
- Added `craft\records\GqlToken`.
- Added `craft\services\Assets::EVENT_GET_ASSET_PREVIEW`.
- Added `craft\services\Assets::getAssetPreview()`.
- Added `craft\services\Gql::CONFIG_GQL_SCHEMAS_KEY`.
- Added `craft\services\Gql::deleteSchema()`.
- Added `craft\services\Gql::deleteTokenById()`.
- Added `craft\services\Gql::EVENT_REGISTER_GQL_PERMISSIONS`.
- Added `craft\services\Gql::getPublicToken()`.
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
- Added `craft\web\twig\nodes\RequireGuestNode`.
- Added `craft\web\twig\tokenparsers\RequireGuestTokenParser`.
- Added `craft\web\twig\variables\Paginate::getDynamicRangeUrls()`, making it easy to create Google-style pagination links. ([#5005](https://github.com/craftcms/cms/issues/5005))
- Added `craft\web\User::guestRequired()`.
- Added `craft\web\View::$minifyCss`.
- Added `craft\web\View::$minifyJs`.
- Added `craft\web\View::getDeltaNames()`.
- Added `craft\web\View::getIsDeltaRegistrationActive()`.
- Added `craft\web\View::registerDeltaName()`.
- Added `craft\web\View::setIsDeltaRegistrationActive()`.
- Added the `cp.users.edit.prefs` template hook to the Edit User page. ([#5114](https://github.com/craftcms/cms/issues/5114))
- Added the Minify PHP package.

### Changed
- Sections’ entry URI format settings are now shown when running Craft in headless mode. ([#4934](https://github.com/craftcms/cms/issues/4934))
- The “Primary entry page” preview target is now user-customizable alongside all other preview targets in sections’ settings. ([#4520](https://github.com/craftcms/cms/issues/4520))
- Control panel requests are now always set to the primary site, regardless of the URL they were accessed from.
- Plain Text fields can now specify a maximum size in bytes. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Plain Text fields’ Column Type settings now have an “Automatic” option, which is selected by default for new fields. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Local asset volumes now ensure that their folder exists on save, and if it doesn’t, a `.gitignore` file will be added automatically to it, excluding the directory from Git. ([#5237](https://github.com/craftcms/cms/issues/5237))
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
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
- `craft\models\GqlSchema::$scope` is now read-only. Setting scope manually is now only possible when creating a new model.
- `craft\services\Elements::saveElement()` now has an `$updateSearchIndex` argument (defaults to `true`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- `craft\services\Elements::resaveElements()` now has an `$updateSearchIndex` argument (defaults to `false`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- `craft\services\Search::indexElementAttributes()` now has a `$fieldHandles` argument, for specifying which custom fields’ keywords should be updated.
- `craft\web\Controller::renderTemplate()` now has a `$templateMode` argument.
- `craft\web\View::renderTemplate()`, `renderPageTemplate()`, `renderTemplateMacro()`, `doesTemplateExist()`, and `resolveTemplate()` now have `$templateMode` arguments. ([#4570](https://github.com/craftcms/cms/pull/4570))
- Updated Yii to 2.0.29.
- GraphQL type `QueryParameter` is now correctly called `QueryArgument`.

### Deprecated
- Deprecated the `url`, `driver`, `database`, `server`, `port`, and `unixSocket` database config settings. `dsn` should be used instead.
- Deprecated `craft\config\DbConfig::DRIVER_MYSQL`.
- Deprecated `craft\config\DbConfig::DRIVER_PGSQL`.
- Deprecated `craft\config\DbConfig::updateDsn()`.
- Deprecated `craft\elements\Asset::getSupportsPreview()`. Use `craft\services\Assets::getAssetPreview()` instead.
- Deprecated `craft\events\ExecuteGqlQueryEvent::$accessToken`. Use `craft\events\ExecuteGqlQueryEvent::$schemaId` instead.
- Deprecated `craft\services\Search::indexElementFields()`.

### Removed
- Removed `craft\helpers\Gql::createFullAccessSchema()`.
- Removed `craft\models\GqlSchema::PUBLIC_TOKEN`.
- Removed `craft\models\GqlSchema::$accessToken`.
- Removed `craft\models\GqlSchema::$enabled`.
- Removed `craft\models\GqlSchema::$expiryDate`.
- Removed `craft\models\GqlSchema::$lastUsed`.
- Removed `craft\models\GqlSchema::$dateCreated`.
- Removed `craft\models\GqlSchema::$isTemporary`.
- Removed `craft\models\GqlSchema::getIsPublic()`.
- Removed `craft\services\Gql::getPublicSchema()`.



