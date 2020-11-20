# Running Release Notes for Craft CMS 3.6

## Unreleased

> {warning} If you have a custom session driver, make sure you update it for Yii 2.0.29 compatibility.

### Added
- Craft now requires PHP 7.2.5 or later.
- Volumes now have “Title Translation Method” and “Title Translation Key Format” settings, like entry types. ([#7135](https://github.com/craftcms/cms/issues/7135))
- Added the `users/list-admins` and `users/set-password` commands. ([#7067](https://github.com/craftcms/cms/issues/7067))
- Added the `disableGraphqlTransformDirective` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `enableGraphqlIntrospection` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlComplexity` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlDepth` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlResults` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added `craft\base\ElementExporterInterface::isFormattable()`.
- Added `craft\base\VolumeTrait::$titleTranslationMethod`.
- Added `craft\base\VolumeTrait::$titleTranslationKeyFormat`.
- Added `craft\console\Controller::passwordPrompt()`.
- Added `craft\elements\db\ElementQueryInterface::afterPopulate()`.
- Added `craft\elements\db\ElementQueryInterface::createElement()`.
- Added `craft\elements\Entry::EVENT_DEFINE_ENTRY_TYPES`. ([#7136](https://github.com/craftcms/cms/issues/7136))
- Added `craft\elements\Entry::getAvailableEntryTypes()`.
- Added `craft\events\DefineEntryTypesEvent`.
- Added `craft\fieldlayoutelements\AssetTitleField`.
- Added `craft\helpers\Gql::eagerLoadComplexity()`.
- Added `craft\helpers\Gql::nPlus1Complexity()`.
- Added `craft\helpers\Gql::singleQueryComplexity()`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_EAGER_LOAD`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_NPLUS1`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_QUERY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD`.
- Added the `Craft.index()` JavaScript method.

### Changed
- Renamed the `backup` and `restore` commands to `db/backup` and `db/restore`. ([#7023](https://github.com/craftcms/cms/issues/7023))
- Relational fields now include all related elements’ titles as search keywords, including disabled elements. ([#7079](https://github.com/craftcms/cms/issues/7079))
- `craft\base\ElementExporterInterface::export()` can now return raw response data, or a resource, if `isFormattable()` returns `false`. If a resource is returned, it will be streamed to the browser. ([#7148](https://github.com/craftcms/cms/issues/7148))
- `craft\services\Composer::install()` no longer has an `$allowlist` argument.
- `craft\services\Gql::getValidationRules()` now has an `$isIntrospectionQuery` argument.
- Craft no longer reports PHP deprecation errors.
- GraphQL queries now support eager-loading for arguments provided as input objects.
- Updated Yii to 2.0.39.
- Updated Guzzle to 7.x, for projects that don’t have any plugins that require Guzzle 6. ([#6997](https://github.com/craftcms/cms/issues/6997))
- Updated Composer to 2.0.7.
- Updated LitEmoji ot 2.x.
- Updated the Symfony Yaml component to 5.x.
- Updated webonyx/graphql-php to 14.x.

### Deprecated
- Deprecated the `backup` and `restore` commands.
- Deprecated the `relatedToAll` GraphQL query argument.
- Deprecated `craft\services\Composer::$disablePackagist`.
- Deprecated `craft\web\View::$minifyCss`.
- Deprecated `craft\web\View::$minifyJs`.

### Removed
- Removed Minify and jsmin-php.
- Removed `craft\controllers\ElementIndexesController::actionCreateExportToken()`.
- Removed `craft\controllers\ExportController`.
- Removed `craft\services\Api::getComposerWhitelist()`.

### Fixed
- Fixed a bug where asset queries’ `withTransforms` param wasn’t being respected for eager-loaded assets. ([#6140](https://github.com/craftcms/cms/issues/6140))
