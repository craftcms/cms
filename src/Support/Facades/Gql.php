<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Gql\Gql as GqlService;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \GraphQL\Type\Schema getSchemaDef(\CraftCms\Cms\Gql\Data\GqlSchema|null $schema = null, bool $prebuildSchema = false)
 * @method static array getValidationRules(bool $debug = false, bool $isIntrospectionQuery = false)
 * @method static array executeQuery(\CraftCms\Cms\Gql\Data\GqlSchema $schema, string $query, array|null $variables = null, string|null $operationName = null, bool $debugMode = false)
 * @method static void invalidateCaches()
 * @method static array|null getCachedResult(string $cacheKey)
 * @method static void setCachedResult(string $cacheKey, array $result, \CraftCms\DependencyAwareCache\Dependency\TagDependency|null $dependency = null, int|null $duration = null)
 * @method static \CraftCms\Cms\Gql\Data\GqlSchema getActiveSchema()
 * @method static void setActiveSchema(\CraftCms\Cms\Gql\Data\GqlSchema|null $schema = null)
 * @method static \CraftCms\Cms\Gql\Data\GqlToken[] getTokens()
 * @method static \CraftCms\Cms\Gql\Data\GqlSchema|null getPublicSchema()
 * @method static array getAllSchemaComponents()
 * @method static void flushCaches()
 * @method static \CraftCms\Cms\Gql\Data\GqlToken|null getTokenById(int $id)
 * @method static \CraftCms\Cms\Gql\Data\GqlToken|null getTokenByName(string $tokenName)
 * @method static \CraftCms\Cms\Gql\Data\GqlToken getTokenByUid(string $uid)
 * @method static \CraftCms\Cms\Gql\Data\GqlToken getTokenByAccessToken(string $token)
 * @method static \CraftCms\Cms\Gql\Data\GqlToken|null getPublicToken()
 * @method static string generateToken()
 * @method static bool saveToken(\CraftCms\Cms\Gql\Data\GqlToken $token, bool $runValidation = true)
 * @method static void handleChangedPublicToken(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteTokenById(int $id)
 * @method static bool saveSchema(\CraftCms\Cms\Gql\Data\GqlSchema $schema, bool $runValidation = true)
 * @method static void handleChangedSchema(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteSchemaById(int $id)
 * @method static bool deleteSchema(\CraftCms\Cms\Gql\Data\GqlSchema $schema)
 * @method static void handleDeletedSchema(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static \CraftCms\Cms\Gql\Data\GqlSchema|null getSchemaById(int $id)
 * @method static \CraftCms\Cms\Gql\Data\GqlSchema|null getSchemaByUid(string $uid)
 * @method static \CraftCms\Cms\Gql\Data\GqlSchema[] getSchemas()
 * @method static array getOrSetContentArguments(string $elementType, callable $setter)
 * @method static array getFieldLayoutArguments(\CraftCms\Cms\FieldLayout\FieldLayout $fieldLayout)
 * @method static array defineContentArgumentsForFieldLayouts(string $elementType, \CraftCms\Cms\FieldLayout\FieldLayout[] $fieldLayouts)
 * @method static array defineContentArgumentsForFields(string $elementType, \CraftCms\Cms\Field\Contracts\FieldInterface[] $fields)
 * @method static array defineContentArgumentsForGeneratedFields(string $elementType, array $fields)
 * @method static array getContentArguments(array $contexts, string $elementType)
 * @method static \Error[] handleQueryErrors(\Error[] $errors, callable $formatter)
 * @method static array prepareFieldDefinitions(array $fields, string $typeName)
 *
 * @see \CraftCms\Cms\Gql\Gql
 */
class Gql extends Facade
{
    public const string CACHE_TAG = GqlService::CACHE_TAG;

    /** The field name to use when fetching count of related elements. */
    public const string GRAPHQL_COUNT_FIELD = GqlService::GRAPHQL_COUNT_FIELD;

    /** Complexity value for accessing a simple field. */
    public const int GRAPHQL_COMPLEXITY_SIMPLE_FIELD = GqlService::GRAPHQL_COMPLEXITY_SIMPLE_FIELD;

    /** Complexity value for accessing a field that will trigger a single query for the request. */
    public const int GRAPHQL_COMPLEXITY_QUERY = GqlService::GRAPHQL_COMPLEXITY_QUERY;

    /** Complexity value for accessing a field that will add an instance of eager-loading for the request. */
    public const int GRAPHQL_COMPLEXITY_EAGER_LOAD = GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD;

    /** Complexity value for accessing a field that will likely trigger a CPU heavy operation. */
    public const int GRAPHQL_COMPLEXITY_CPU_HEAVY = GqlService::GRAPHQL_COMPLEXITY_CPU_HEAVY;

    /** Complexity value for accessing a field that will trigger a query for every parent returned. */
    public const int GRAPHQL_COMPLEXITY_NPLUS1 = GqlService::GRAPHQL_COMPLEXITY_NPLUS1;

    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return GqlService::class;
    }
}
