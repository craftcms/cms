# Running Release Notes for Craft CMS 3.6

## Unreleased

### Added
- Craft now requires PHP 7.2.5 or later.
- Added the `users/list-admins` and `users/set-password` commands. ([#7067](https://github.com/craftcms/cms/issues/7067))
- Added the `disableGraphqlTransformDirective` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `enableGraphqlIntrospection` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlComplexity` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlDepth` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlResults` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added `craft\console\Controller::passwordPrompt()`.
- Added `craft\helpers\Gql::eagerLoadComplexity()`.
- Added `craft\helpers\Gql::nPlus1Complexity()`.
- Added `craft\helpers\Gql::singleQueryComplexity()`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_EAGER_LOAD`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_NPLUS1`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_QUERY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD`.

### Changed
- Renamed the `backup` and `restore` commands to `db/backup` and `db/restore`. ([#7023](https://github.com/craftcms/cms/issues/7023))
- Relational fields now include all related elements’ titles as search keywords, including disabled elements. ([#7079](https://github.com/craftcms/cms/issues/7079))
- `craft\services\Composer::install()` no longer has an `$allowlist` argument.
- `craft\services\Gql::getValidationRules()` now has an `$isIntrospectionQuery` argument.
- Craft no longer reports PHP deprecation errors.
- Updated Guzzle to 7.x, for projects that don’t have any plugins that require Guzzle 6. ([#6997](https://github.com/craftcms/cms/issues/6997))
- Updated Composer to 2.0.4.
- Updated the Symfony Yaml component to 5.x.

### Deprecated
- Deprecated the `backup` and `restore` commands.
- Deprecated `craft\services\Composer::$disablePackagist`.
- Deprecated `craft\web\View::$minifyCss`.
- Deprecated `craft\web\View::$minifyJs`.

### Removed
- Removed Minify and jsmin-php.
- Removed `craft\services\Api::getComposerWhitelist()`.
