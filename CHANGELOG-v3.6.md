# Running Release Notes for Craft CMS 3.6

## Unreleased

> {warning} If you have a custom session driver, make sure you update it for Yii 2.0.29 compatibility.

### Added
- Craft now requires PHP 7.2.5 or later.
- Entries now begin life as “unpublished drafts” rather than “unsaved drafts”. They are no longer ephemeral; they will continue to exist until explicitly published or deleted. ([#5661](https://github.com/craftcms/cms/issues/5661), [#7216](https://github.com/craftcms/cms/issues/7216))
- It’s now possible to delete entries for a specific site, if their section’s propagation method is set to “Let each entry choose which sites it should be saved to”. ([#7190](https://github.com/craftcms/cms/issues/7190))
- User indexes can now include a “Groups” column. ([#7211](https://github.com/craftcms/cms/issues/7211))
- Volumes now have “Title Translation Method” and “Title Translation Key Format” settings, like entry types. ([#7135](https://github.com/craftcms/cms/issues/7135))
- It’s now possible to set sites’ Name settings to environment variables.
- Added the `users/list-admins` and `users/set-password` commands. ([#7067](https://github.com/craftcms/cms/issues/7067))
- Added the `disableGraphqlTransformDirective` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `enableGraphqlIntrospection` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlComplexity` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlDepth` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlResults` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `rasterizeSvgThumbs` config setting. ([#7146](https://github.com/craftcms/cms/issues/7146))
- Added the `{% tag %}` Twig tag.
- Added the `withGroups` user query param.
- Added the `relatedToAssets`, `relatedToCategories`, `relatedToEntries`, `relatedToTags`, and `relatedToUsers` arguments to GraphQL queries. ([#7110](https://github.com/craftcms/cms/issues/7110))
- Added the `isUnpublishedDraft` GraphQL field.
- Added `craft\base\ElementExporterInterface::isFormattable()`.
- Added `craft\base\ElementInterface::getIsUnpublishedDraft()`.
- Added `craft\base\FieldInterface::includeInGqlSchema()`. ([#7244](https://github.com/craftcms/cms/pull/7244))
- Added `craft\base\VolumeTrait::$titleTranslationKeyFormat`.
- Added `craft\base\VolumeTrait::$titleTranslationMethod`.
- Added `craft\console\Controller::passwordPrompt()`.
- Added `craft\controllers\BaseEntriesController::enforceDeleteEntryPermissions()`.
- Added `craft\elements\db\ElementQueryInterface::afterPopulate()`.
- Added `craft\elements\db\ElementQueryInterface::createElement()`.
- Added `craft\elements\Entry::EVENT_DEFINE_ENTRY_TYPES`. ([#7136](https://github.com/craftcms/cms/issues/7136))
- Added `craft\elements\Entry::getAvailableEntryTypes()`.
- Added `craft\events\DefineEntryTypesEvent`.
- Added `craft\events\RegisterGqlArgumentHandlersEvent`.
- Added `craft\events\SearchEvent::$results`. ([#7237](https://github.com/craftcms/cms/issues/7237))
- Added `craft\fieldlayoutelements\AssetTitleField`.
- Added `craft\gql\ArgumentManager`.
- Added `craft\gql\base\ArgumentHandler`.
- Added `craft\gql\base\ArgumentHandlerInterface`.
- Added `craft\gql\base\Generator`.
- Added `craft\gql\base\RelationArgumentHandler`.
- Added `craft\gql\ElementQueryConditionBuilder::setArgumentManager()`.
- Added `craft\gql\handlers\RelatedAssets`.
- Added `craft\gql\handlers\RelatedCategories`.
- Added `craft\gql\handlers\RelatedEntries`.
- Added `craft\gql\handlers\RelatedTags`.
- Added `craft\gql\handlers\RelatedUsers`.
- Added `craft\gql\types\input\criteria\Asset`.
- Added `craft\gql\types\input\criteria\Category`.
- Added `craft\gql\types\input\criteria\Entry`.
- Added `craft\gql\types\input\criteria\Tag`.
- Added `craft\gql\types\input\criteria\User`.
- Added `craft\helpers\Diff`.
- Added `craft\helpers\Gql::eagerLoadComplexity()`.
- Added `craft\helpers\Gql::nPlus1Complexity()`.
- Added `craft\helpers\Gql::singleQueryComplexity()`.
- Added `craft\log\Dispatcher`.
- Added `craft\models\Site::getName()`.
- Added `craft\models\Site::setBaseUrl()`.
- Added `craft\models\Site::setName()`.
- Added `craft\services\Drafts::EVENT_AFTER_APPLY_DRAFT`.
- Added `craft\services\Drafts::EVENT_BEFORE_APPLY_DRAFT`.
- Added `craft\services\Drafts::publishDraft()`.
- Added `craft\services\Globals::deleteSet()`.
- Added `craft\services\Globals::reset()`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_EAGER_LOAD`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_NPLUS1`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_QUERY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD`.
- Added `craft\test\ActiveFixture`.
- Added `craft\test\DbFixtureTrait`.
- Added `craft\test\fixtures\elements\BaseElementFixture`.
- Added `craft\test\TestSetup::SITE_URL`.
- Added the `Craft.index()` JavaScript method.

### Changed
- Renamed the `backup` and `restore` commands to `db/backup` and `db/restore`. ([#7023](https://github.com/craftcms/cms/issues/7023))
- Relational fields now include all related elements’ titles as search keywords, including disabled elements. ([#7079](https://github.com/craftcms/cms/issues/7079))
- Improved the performance of project config change diffs. ([#7218](https://github.com/craftcms/cms/issues/7218))
- Improved the accessibility of info icons.
- The `withoutKey` Twig filter can now accept an array, for removing multiple keys at once. ([#7230](https://github.com/craftcms/cms/issues/7230))
- It’s now possible to add new log targets by overriding `components.log.targets` in `config/app.php`, rather than the entire `log` component config.
- `craft\base\ElementExporterInterface::export()` can now return raw response data, or a resource, if `isFormattable()` returns `false`. If a resource is returned, it will be streamed to the browser. ([#7148](https://github.com/craftcms/cms/issues/7148))
- `craft\behaviors\EnvAttributeParserBehavior::$attributes` can now be set to an array with key/value pairs, where the key is the attribute name, and the value is the raw (unparsed) value, or a callable that returns the raw value.
- `craft\behaviors\EnvAttributeParserBehavior::$attributes` can now be set to an array with key/value pairs, where the key is the attribute name, and the value is the raw (unparsed) value, or a callable that returns the raw value.
- `craft\db\Connection::getPrimaryKeyName()`, `getForeignKeyName()`, and `getIndexName()` now generate completely random object names, rather than basing them on a table name, etc. ([#7153](https://github.com/craftcms/cms/issues/7153))
- `craft\helpers\Gql::isSchemaAwareOf()`, `extractAllowedEntitiesFromSchema()`, `canSchema()`, `extractEntityAllowedActions()`, `canMutateEntries()`, `canMutateTags()`, `canMutateGlobalSets()`, `canMutateCategories()`, `canMutateAssets()`, `canQueryEntries()`, `canQueryAssets()`, `canQueryCategories()`, `canQueryTags()`, `canQueryGlobalSets()`, and `canQueryUsers()` now have `$schema` arguments.
- `craft\models\Site::$baseUrl` is now a magic property, which returns the parsed base URL. ([#3964](https://github.com/craftcms/cms/issues/3964))
- `craft\models\Site::$name` is now a magic property, which returns the parsed site name. ([#3964](https://github.com/craftcms/cms/issues/3964))
- `craft\models\Site::getBaseUrl()` now has a `$parse` argument, which can be set to `false` to return the raw (unparsed) base URL.
- `craft\services\Composer::install()` no longer has an `$allowlist` argument.
- `craft\services\Gql::getValidationRules()` now has an `$isIntrospectionQuery` argument.
- Craft no longer reports PHP deprecation errors.
- GraphQL queries now support eager-loading for arguments provided as input objects.
- Updated Yii to 2.0.39.
- Updated Guzzle to 7.x, for projects that don’t have any plugins that require Guzzle 6. ([#6997](https://github.com/craftcms/cms/issues/6997))
- Updated Composer to 2.0.7.
- Updated LitEmoji to 2.x.
- Updated the Symfony Yaml component to 5.x.
- Updated webonyx/graphql-php to 14.x.

### Deprecated
- Deprecated the `backup` and `restore` commands.
- Deprecated the `purgeUnsavedDraftsDuration` config setting.
- Deprecated the `siteName` config setting. Sites’ Name settings should be set to environment variables instead.
- Deprecated the `siteUrl` config setting. Sites’ Base URL settings should be set to aliases or environment variables instead. ([#3205](https://github.com/craftcms/cms/issues/3205))
- Deprecated the `relatedToAll` GraphQL query argument.
- Deprecated the `isUnsavedDraft` GraphQL field.
- Deprecated `craft\base\Element::getIsUnsavedDraft()`. `getIsUnpublishedDraft()` should be used instead.
- Deprecated `craft\db\Connection::trimObjectName()`.
- Deprecated `craft\gql\base\Resolver::getArrayableArguments()`.
- Deprecated `craft\gql\base\Resolver::prepareArguments()`.
- Deprecated `craft\helpers\App::logConfig()`.
- Deprecated `craft\services\Composer::$disablePackagist`.
- Deprecated `craft\services\Drafts::applyDraft()`. `publishDraft()` should be used instead.
- Deprecated `craft\services\Drafts::EVENT_AFTER_APPLY_DRAFT`. `EVENT_AFTER_PUBLISH_DRAFT` should be used instead.
- Deprecated `craft\services\Drafts::EVENT_BEFORE_APPLY_DRAFT`. `EVENT_BEFORE_PUBLISH_DRAFT` should be used instead.
- Deprecated `craft\services\Drafts::purgeUnsavedDrafts()`.
- Deprecated `craft\test\Fixture`. `craft\test\ActiveFixture` should be used instead.
- Deprecated `craft\web\View::$minifyCss`.
- Deprecated `craft\web\View::$minifyJs`.

### Removed
- Removed Minify and jsmin-php.
- Removed `craft\controllers\ElementIndexesController::actionCreateExportToken()`.
- Removed `craft\controllers\ExportController`.
- Removed `craft\services\Api::getComposerWhitelist()`.
- Removed `craft\test\fixtures\elements\ElementFixture`. `craft\test\fixtures\elements\BaseElementFixture` should be used instead.
- Removed `craft\test\fixtures\FieldLayoutFixture::deleteAllByFieldHandle()`.
- Removed `craft\test\fixtures\FieldLayoutFixture::extractTabsFromFieldLayout()`.
- Removed `craft\test\fixtures\FieldLayoutFixture::getTabsForFieldLayout()`.
- Removed `craft\test\fixtures\FieldLayoutFixture::linkFieldToLayout()`.

### Fixed
- Fixed a bug where asset queries’ `withTransforms` param wasn’t being respected for eager-loaded assets. ([#6140](https://github.com/craftcms/cms/issues/6140))
- Fixed a bug where `craft\db\Connection::getPrimaryKeyName()`, `getForeignKeyName()`, and `getIndexName()` could generate non-unique object names. ([#7153](https://github.com/craftcms/cms/issues/7153))
- Fixed a bug where number strings were not correctly typecast to the right PHP numeric type when using the Number GraphQL type.
- Fixed a bug where it wasn’t possible to save a Global set with a predefined UID.
- Fixed a bug where `craft\helpers\Db::prepareValuesForDb()` would omit or JSON-encode `DateTime` objects, depending on the PHP version, rather than converting them to ISO-8601-formatted strings.
- Fixed a bug where info icons’ content was focusable before the icon was activated. ([#7257](https://github.com/craftcms/cms/issues/7257))
