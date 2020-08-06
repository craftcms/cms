# Running Release Notes for Craft CMS 3.6

### Added
- Added `craft\gql\GqlEntityRegistry::getPrefix()`.
- Added the `disableGraphqlTransformDirective` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))- Added the `enableGraphqlIntrospection` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlComplexity` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlDepth` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added the `maxGraphqlResults` config setting. ([#6466](https://github.com/craftcms/cms/issues/6466))
- Added `craft\helpers\Gql::eagerLoadComplexity()`.
- Added `craft\helpers\Gql::nPlus1Complexity()`.
- Added `craft\helpers\Gql::singleQueryComplexity()`.
- Added `craft\gql\GqlEntityRegistry::setPrefix()`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_CPU_HEAVY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_EAGER_LOAD`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_NPLUS1`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_QUERY`.
- Added `craft\services\Gql::GRAPHQL_COMPLEXITY_SIMPLE_FIELD`.

### Changed
- `craft\services\Gql::getValidationRules()` now has an `$isIntrospectionQuery` argument.
- Craft now requires PHP 7.2 or later.
- Updated Twig to 2.13.
- Updated `webonyx/graphql-php` to 14.1.1.
