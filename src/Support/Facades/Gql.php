<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use craft\models\GqlSchema;
use craft\models\GqlToken;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Gql\Gql as GqlService;
use GraphQL\Type\Schema;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static Schema getSchemaDef(GqlSchema|null $schema = null, bool $prebuildSchema = false)
 * @method static array getValidationRules(bool $debug = false, bool $isIntrospectionQuery = false)
 * @method static array executeQuery(GqlSchema $schema, string $query, array|null $variables = null, string|null $operationName = null, bool $debugMode = false)
 * @method static void invalidateCaches()
 * @method static array|null getCachedResult(string $cacheKey)
 * @method static void setCachedResult(string $cacheKey, array $result, array $tags = [], int|null $duration = null)
 * @method static GqlSchema getActiveSchema()
 * @method static void setActiveSchema(GqlSchema|null $schema = null)
 * @method static GqlToken[] getTokens()
 * @method static GqlSchema|null getPublicSchema()
 * @method static array getAllSchemaComponents()
 * @method static void flushCaches()
 * @method static GqlToken|null getTokenById(int $id)
 * @method static GqlToken|null getTokenByName(string $tokenName)
 * @method static GqlToken getTokenByUid(string $uid)
 * @method static GqlToken getTokenByAccessToken(string $token)
 * @method static GqlToken|null getPublicToken()
 * @method static bool saveToken(GqlToken $token, bool $runValidation = true)
 * @method static bool deleteTokenById(int $id)
 * @method static bool saveSchema(GqlSchema $schema, bool $runValidation = true)
 * @method static bool deleteSchemaById(int $id)
 * @method static bool deleteSchema(GqlSchema $schema)
 * @method static GqlSchema|null getSchemaById(int $id)
 * @method static GqlSchema|null getSchemaByUid(string $uid)
 * @method static GqlSchema[] getSchemas()
 * @method static array getOrSetContentArguments(string $elementType, callable $setter)
 * @method static array getFieldLayoutArguments(FieldLayout $fieldLayout)
 * @method static array defineContentArgumentsForFieldLayouts(string $elementType, array $fieldLayouts)
 * @method static array defineContentArgumentsForFields(string $elementType, array $fields)
 * @method static array defineContentArgumentsForGeneratedFields(string $elementType, array $fields)
 * @method static array getContentArguments(array $contexts, string $elementType)
 * @method static array handleQueryErrors(array $errors, callable $formatter)
 * @method static array prepareFieldDefinitions(array $fields, string $typeName)
 *
 * @see GqlService
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
