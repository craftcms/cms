# Release Notes for Craft CMS 4

## Unreleased

### Added
- Added the `disableGraphqlTransformDirective` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `enableGraphqlIntrospection` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlComplexity` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlDepth` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlResults` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added `craft\gql\GqlEntityRegistry::getPrefix()`.
- Added `craft\gql\GqlEntityRegistry::setPrefix()`.
- Added `craft\helpers\Gql::eagerLoadComplexity()`.
- Added `craft\helpers\Gql::nPlus1Complexity()`.
- Added `craft\helpers\Gql::singleQueryComplexity()`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_EAGER_LOAD`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_NPLUS1`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_QUERY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD`.

### Changed
- Craft now requires PHP 7.4 or later.
- `craft\base\Model::setAttributes()` now normalizes date attributes into `DateTime` objects.
- `craft\services\Gql::getValidationRules()` now has an `$isIntrospectionQuery` argument.
- Updated Twig to 2.13.
- Updated `webonyx/graphql-php` to 14.1.1.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::append()`. `array_unshift()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::prepend()`. `array_push()` should be used instead.