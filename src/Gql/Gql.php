<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use BadMethodCallException;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface as BaseElementInterface;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Data\GqlToken;
use CraftCms\Cms\Gql\Directives\Directive;
use CraftCms\Cms\Gql\Events\ExecutedGqlQuery;
use CraftCms\Cms\Gql\Events\GqlQueryExecuting;
use CraftCms\Cms\Gql\Events\GqlSchemaComponentsResolving;
use CraftCms\Cms\Gql\Events\GqlValidationRulesResolving;
use CraftCms\Cms\Gql\Exceptions\GqlException;
use CraftCms\Cms\Gql\Models\GqlSchema as GqlSchemaModel;
use CraftCms\Cms\Gql\Models\GqlToken as GqlTokenModel;
use CraftCms\Cms\Gql\Types\Mutation;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Gql\Types\Query;
use CraftCms\Cms\Gql\Types\QueryArgument;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\Directive as GqlDirective;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\TypeInfo;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\KnownTypeNames;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\ValidationRule;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @phpstan-type SchemaComponents array<string, array{label: string, nested?: array<string, array{label: string}>}>
 */
#[Scoped]
class Gql
{
    public const string CACHE_TAG = 'graphql';

    /** The field name to use when fetching count of related elements. */
    public const string GRAPHQL_COUNT_FIELD = '_count';

    /** Complexity value for accessing a simple field. */
    public const int GRAPHQL_COMPLEXITY_SIMPLE_FIELD = 1;

    /** Complexity value for accessing a field that will trigger a single query for the request. */
    public const int GRAPHQL_COMPLEXITY_QUERY = 10;

    /** Complexity value for accessing a field that will add an instance of eager-loading for the request. */
    public const int GRAPHQL_COMPLEXITY_EAGER_LOAD = 25;

    /** Complexity value for accessing a field that will likely trigger a CPU heavy operation. */
    public const int GRAPHQL_COMPLEXITY_CPU_HEAVY = 200;

    /** Complexity value for accessing a field that will trigger a query for every parent returned. */
    public const int GRAPHQL_COMPLEXITY_NPLUS1 = 500;

    /**
     * @var Schema|null Currently loaded schema definition
     */
    private ?Schema $_schemaDef = null;

    /**
     * @var GqlSchema|null The active GraphQL schema
     *
     * @see setActiveSchema()
     */
    private ?GqlSchema $_schema = null;

    /**
     * @var array<class-string<BaseElementInterface>, array<string, ArgumentConfig>> Content arguments by element class
     *
     * @see getOrSetContentArguments()
     */
    private array $_contentArguments = [];

    /**
     * @var array<string, array<string, ArgumentConfig>> Custom field arguments by field layout UUID
     *
     * @see getFieldLayoutArguments()
     */
    private array $_fieldArguments = [];

    /**
     * @var TypeManager|null GQL type manager
     */
    private ?TypeManager $_typeManager = null;

    /** @var array<string, array<string, FieldDefinitionConfig>> */
    private array $_typeDefinitions = [];

    /**
     * @see getPublicToken()
     */
    private ?GqlToken $_publicToken = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly ElementCaches $elementCaches,
        private readonly GqlDirectives $gqlDirectives,
        private readonly GqlTypes $gqlTypes,
        private readonly GqlQueries $gqlQueries,
        private readonly GqlMutations $gqlMutations,
    ) {}

    /**
     * @param  bool  $prebuildSchema  should the schema be deep-scanned and pre-built instead of lazy-loaded
     *
     * @throws GqlException in case of invalid schema
     */
    public function getSchemaDef(?GqlSchema $schema = null, bool $prebuildSchema = false): Schema
    {
        if ($schema) {
            $this->setActiveSchema($schema);
        }
        if (! $this->_schemaDef || $prebuildSchema) {
            $registeredTypes = $this->_registerGqlTypes();
            $this->_registerGqlQueries();
            $this->_registerGqlMutations();

            $schemaConfig = [
                'typeLoader' => TypeLoader::loadType(...),
                'query' => TypeLoader::loadType('Query'),
                'mutation' => TypeLoader::loadType('Mutation'),
                'directives' => $this->_loadGqlDirectives($schema),
            ];

            // If we're not required to pre-build the schema the relevant GraphQL types will be added to the Schema
            // as the query is being resolved thanks to the magic of lazy-loading, so we needn't worry.
            if (! $prebuildSchema) {
                $this->_schemaDef = new Schema($schemaConfig);
                // add default description (use schema name), or DumpSchemaCommandTest and SchemaPrinter::printSchemaDefinition() will error
                // because of SchemaPrinter::hasDefaultRootOperationTypes($schema) and "Subscription"
                $this->_schemaDef->description ??= $schema?->name;

                // but we always have to add the InputObjectType mutation args
                /** @var ObjectType $mutation */
                $mutation = $schemaConfig['mutation'];
                foreach ($mutation->config['fields'] as $item) {
                    if (isset($item['args'])) {
                        foreach ($item['args'] as $arg) {
                            $argType = Type::getNamedType($arg->getType());
                            if ($argType instanceof InputObjectType) {
                                TypeInfo::extractTypes($argType, $arg);
                            }
                        }
                    }
                }

                return $this->_schemaDef;
            }

            foreach ($registeredTypes as $registeredType) {
                if (method_exists($registeredType, 'getTypeGenerator')) {
                    $typeGeneratorClass = $registeredType::getTypeGenerator();
                    if (is_subclass_of($typeGeneratorClass, GeneratorInterface::class)) {
                        foreach ($typeGeneratorClass::generateTypes() as $type) {
                            $schemaConfig['types'][] = $type;
                        }
                    }
                }
            }

            try {
                $this->_schemaDef = new Schema($schemaConfig);
                $this->_schemaDef->getTypeMap();
                // add default description (use schema name), or DumpSchemaCommandTest and SchemaPrinter::printSchemaDefinition() will error
                // because of SchemaPrinter::hasDefaultRootOperationTypes($schema) and "Subscription"
                $this->_schemaDef->description ??= $schema?->name;
            } catch (Throwable $exception) {
                throw new GqlException('Failed to validate the GQL Schema - '.$exception->getMessage(),
                    previous: $exception);
            }
        }

        return $this->_schemaDef;
    }

    /**
     * @param  bool  $debug  Whether debugging validation rules should be allowed.
     * @param  bool  $isIntrospectionQuery  Whether this is an introspection query
     */
    /** @return list<ValidationRule> */
    public function getValidationRules(bool $debug = false, bool $isIntrospectionQuery = false): array
    {
        $validationRules = DocumentValidator::defaultRules();

        if (! $debug) {
            // Remove the rules which would generate a full schema just for a nice message, to avoid performance hit.
            unset(
                $validationRules[KnownTypeNames::class],
                $validationRules[FieldsOnCorrectType::class],
            );
        }

        $generalConfig = Cms::config();

        if (! $isIntrospectionQuery) {
            // Set complexity rule, if defined,
            if (! empty($generalConfig->maxGraphqlComplexity)) {
                $validationRules[QueryComplexity::class] = new QueryComplexity($generalConfig->maxGraphqlComplexity);
            }

            // Set depth rule, if defined,
            if (! empty($generalConfig->maxGraphqlDepth)) {
                $validationRules[QueryDepth::class] = new QueryDepth($generalConfig->maxGraphqlDepth);
            }
        }

        if (! $generalConfig->enableGraphqlIntrospection && Auth::guest()) {
            $validationRules[DisableIntrospection::class] = new DisableIntrospection(0);
        }

        event($event = new GqlValidationRulesResolving(
            validationRules: $validationRules,
            debug: $debug,
        ));
        $validationRules = $event->validationRules;

        return array_values($validationRules);
    }

    /**
     * @param  GqlSchema  $schema  The schema definition to use.
     * @param  string  $query  The query string to execute.
     * @param  array<string, mixed>|null  $variables  The variables to use.
     * @param  string|null  $operationName  The operation name.
     * @param  bool  $debugMode  Whether debug mode validations rules should be used for GraphQL.
     * @return array<string, mixed>
     */
    public function executeQuery(
        GqlSchema $schema,
        string $query,
        ?array $variables = null,
        ?string $operationName = null,
        bool $debugMode = false,
    ): array {
        $context = [
            'conditionBuilder' => new ElementQueryConditionBuilder,
            'argumentManager' => new ArgumentManager,
        ];
        $result = $rootValue = null;
        $dep = null;
        $duration = null;

        event($event = new GqlQueryExecuting(
            schema: $schema,
            query: $query,
            variables: $variables,
            operationName: $operationName,
            context: $context,
            debugMode: $debugMode,
        ));
        $query = $event->query;
        $rootValue = $event->rootValue;
        $variables = $event->variables;
        $operationName = $event->operationName;
        $context = $event->context;
        $result = $event->result;

        if ($result === null) {
            $cacheKey = $this->_getCacheKey(
                $schema,
                $query,
                $rootValue,
                $variables,
                $operationName,
            );

            if ($cacheKey && ($cachedResult = $this->getCachedResult($cacheKey)) !== null) {
                $result = $cachedResult;
            } else {
                $isIntrospectionQuery = GqlHelper::isIntrospectionQuery($query);
                $prebuildSchema = $isIntrospectionQuery || ! Cms::config()->lazyGqlTypes;
                $schemaDef = $this->getSchemaDef($schema, $prebuildSchema);
                $this->elementCaches->startCollectingCacheInfo();

                $result = GraphQL::executeQuery(
                    $schemaDef,
                    $query,
                    $rootValue,
                    $context,
                    $variables,
                    $operationName,
                    null,
                    $this->getValidationRules($debugMode, $isIntrospectionQuery),
                )
                    ->setErrorsHandler($this->handleQueryErrors(...))
                    ->toArray($debugMode ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE : DebugFlag::NONE);

                [$dep, $duration] = $this->elementCaches->stopCollectingCacheInfo();

                if (empty($result['errors']) && $cacheKey && $this->shouldCache($result)) {
                    $this->setCachedResult($cacheKey, $result, $dep, $duration);
                }
            }
        }

        event($event = new ExecutedGqlQuery(
            schema: $schema,
            query: $query,
            variables: $variables,
            operationName: $operationName,
            context: $context,
            rootValue: $rootValue,
            result: $result,
            cacheTags: $dep?->tags,
            cacheDuration: $duration,
            debugMode: $debugMode,
        ));

        return $event->result ?? [];
    }

    public function invalidateCaches(): void
    {
        TagDependency::invalidate(self::CACHE_TAG);
    }

    /** @return array<string, mixed>|null */
    public function getCachedResult(string $cacheKey): ?array
    {
        $data = DependencyCache::get($cacheKey);

        if (! isset($data['result'], $data['cacheInfo']['tags'])) {
            return null;
        }

        // If we're actively collecting cache info, register this cache's tags and duration
        if ($this->elementCaches->isCollectingCacheInfo()) {
            $this->elementCaches->collectCacheTags($data['cacheInfo']['tags']);
            if (isset($data['cacheInfo']['expiryDate'])) {
                $this->elementCaches->setCacheExpiryDate($data['cacheInfo']['expiryDate']);
            }
        }

        return $data['result'];
    }

    /** @param array<array-key, mixed> $result */
    private function shouldCache(array $result): bool
    {
        foreach ($result as $value) {
            if (is_string($value)) {
                if (str_contains(stripslashes($value), 'assets/generate-transform')) {
                    return false;
                }

                continue;
            }

            if (is_array($value) && ! $this->shouldCache($value)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $result */
    public function setCachedResult(
        string $cacheKey,
        array $result,
        ?TagDependency $dependency = null,
        ?int $duration = null,
    ): void {
        $dependency ??= new TagDependency;

        // Add the global graphql cache tag
        $dependency->tags[] = self::CACHE_TAG;

        $cacheInfo = [
            'tags' => $dependency->tags,
        ];

        if ($duration) {
            $cacheInfo['expiryDate'] = now()->addSeconds($duration);
        }

        DependencyCache::put($cacheKey, [
            'result' => $result,
            'cacheInfo' => $cacheInfo,
        ], $duration, $dependency);
    }

    /**
     * @throws GqlException if no schema is currently active.
     */
    public function getActiveSchema(): GqlSchema
    {
        if (! $this->_schema) {
            throw new GqlException('No schema is active.');
        }

        return $this->_schema;
    }

    /**
     * @param  GqlSchema|null  $schema  The schema, or `null` to unset the active schema
     */
    public function setActiveSchema(?GqlSchema $schema = null): void
    {
        $this->_schema = $schema;
    }

    /** @return GqlToken[] */
    public function getTokens(): array
    {
        $rows = $this->_createTokenQuery()->get();

        $schemas = [];
        $names = [];

        foreach ($rows as $row) {
            $token = new GqlToken((array) $row);

            if (! $token->getIsPublic()) {
                $schemas[] = $token;
                $names[] = $token->name;
            }
        }

        // Sort them by name
        array_multisort($names, SORT_ASC, SORT_STRING, $schemas);

        return $schemas;
    }

    /**
     * Returns the public schema. If it does not exist and admin changes are allowed, it will be created.
     *
     * @throws Exception
     */
    public function getPublicSchema(): ?GqlSchema
    {
        return $this->getPublicToken()?->getSchema();
    }

    /** @return array{queries: array<string, SchemaComponents>, mutations: array<string, SchemaComponents>} */
    public function getAllSchemaComponents(): array
    {
        $queries = [];
        $mutations = [];

        // Sites
        $label = t('Sites');
        [$queries[$label], $mutations[$label]] = $this->siteSchemaComponents();

        // Elements
        $label = t('All elements');
        [$queries[$label], $mutations[$label]] = $this->elementSchemaComponents();

        // Entries
        $label = t('Entries');
        [$queries[$label], $mutations[$label]] = $this->entrySchemaComponents();

        // Assets
        $label = t('Assets');
        [$queries[$label], $mutations[$label]] = $this->assetSchemaComponents();

        // Users
        $label = t('Users');
        [$queries[$label], $mutations[$label]] = $this->userSchemaComponents();

        event($event = new GqlSchemaComponentsResolving(
            queries: $queries,
            mutations: $mutations,
        ));
        $queries = $event->queries;
        $mutations = $event->mutations;

        return [
            'queries' => $queries,
            'mutations' => $mutations,
        ];
    }

    public function flushCaches(): void
    {
        $this->_schema = null;
        $this->_schemaDef = null;
        $this->_contentArguments = [];
        $this->_typeDefinitions = [];
        TypeLoader::flush();
        GqlEntityRegistry::flush();
        $this->invalidateCaches();
    }

    public function getTokenById(int $id): ?GqlToken
    {
        $result = $this->_createTokenQuery()->find($id);

        return $result ? new GqlToken((array) $result) : null;
    }

    public function getTokenByName(string $tokenName): ?GqlToken
    {
        $result = $this->_createTokenQuery()
            ->where('name', $tokenName)
            ->first();

        return $result ? new GqlToken((array) $result) : null;
    }

    /** @throws InvalidArgumentException if $uid is invalid */
    public function getTokenByUid(string $uid): GqlToken
    {
        $result = $this->_createTokenQuery()->where('uid', $uid)->first();

        if (! $result) {
            throw new InvalidArgumentException('Invalid UID');
        }

        return new GqlToken((array) $result);
    }

    /** @throws InvalidArgumentException if $token is invalid */
    public function getTokenByAccessToken(string $token): GqlToken
    {
        if ($token === GqlToken::PUBLIC_TOKEN) {
            $publicToken = $this->getPublicToken();

            if (! $publicToken) {
                throw new InvalidArgumentException('Invalid access token');
            }

            return $publicToken;
        }

        $result = $this->_createTokenQuery()->where('accessToken', $token)->first();

        if (! $result) {
            throw new InvalidArgumentException('Invalid access token');
        }

        return new GqlToken((array) $result);
    }

    public function getPublicToken(): ?GqlToken
    {
        if (! isset($this->_publicToken)) {
            $config = $this->projectConfig->get(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN) ?? [];
            $this->_publicToken = $this->_createPublicToken($config);

            if ($this->_publicToken) {
                $this->_publicToken->id = $this->_createTokenQuery()
                    ->where('accessToken', GqlToken::PUBLIC_TOKEN)
                    ->value('id');
            }
        }

        return $this->_publicToken;
    }

    /** @param array{enabled?: bool, expiryDate?: mixed} $config */
    private function _createPublicToken(array $config): ?GqlToken
    {
        $schema = $this->_getPublicSchema();

        if (! $schema) {
            if (! Cms::config()->allowAdminChanges) {
                return null;
            }

            $schema = $this->_createPublicSchema();
        }

        return new GqlToken([
            'name' => 'Public Token',
            'accessToken' => GqlToken::PUBLIC_TOKEN,
            'schema' => $schema,
            'enabled' => $config['enabled'] ?? false,
            'expiryDate' => DateTimeHelper::toDateTime($config['expiryDate'] ?? false) ?: null,
        ]);
    }

    public function generateToken(): string
    {
        return Str::random(32, extendedChars: true);
    }

    /**
     * @param  GqlToken  $token  the schema to save
     * @param  bool  $runValidation  Whether the schema should be validated
     *
     * @throws Exception
     */
    public function saveToken(GqlToken $token, bool $runValidation = true): bool
    {
        if ($token->isTemporary) {
            return false;
        }

        if ($runValidation && ! $token->validate()) {
            Log::info('Token not saved due to validation error.', [__METHOD__]);

            return false;
        }

        // Public token information is stored in the project config
        if ($token->accessToken === GqlToken::PUBLIC_TOKEN) {
            $data = [
                'enabled' => $token->enabled,
                'expiryDate' => $token->expiryDate?->getTimestamp(),
            ];

            $muteEvents = $this->projectConfig->muteEvents;
            $this->projectConfig->muteEvents = false;
            $this->projectConfig->set(ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN, $data);
            $this->projectConfig->muteEvents = $muteEvents;
        }

        $this->_saveTokenInternal($token);

        return true;
    }

    public function handleChangedPublicToken(ConfigEvent $event): void
    {
        // If we're just adding a public schema, ensure it makes it in.
        ProjectConfigHelper::ensureAllGqlSchemasProcessed();

        $this->_publicToken = $this->_createPublicToken($event->newValue);
    }

    public function deleteTokenById(int $id): bool
    {
        $model = GqlTokenModel::find($id);

        if (! $model) {
            return true;
        }

        return $model->delete();
    }

    /**
     * @param  GqlSchema  $schema  the schema to save
     * @param  bool  $runValidation  Whether the schema should be validated
     *
     * @throws Exception
     */
    public function saveSchema(GqlSchema $schema, bool $runValidation = true): bool
    {
        $isNewSchema = ! $schema->id;

        if ($runValidation && ! $schema->validate()) {
            Log::info('Schema not saved due to validation error.', [__METHOD__]);

            return false;
        }

        if ($isNewSchema && empty($schema->uid)) {
            $schema->uid = Str::uuid()->toString();
        } elseif (empty($schema->uid)) {
            $schema->uid = DB::table(Table::GQLSCHEMAS)->uidById($schema->id);
        }

        $configPath = ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.'.$schema->uid;
        $configData = $schema->getConfig();
        $this->projectConfig->set($configPath, $configData, "Save GraphQL schema “{$schema->name}”");

        if ($isNewSchema) {
            $schema->id = DB::table(Table::GQLSCHEMAS)->idByUid($schema->uid);
        }

        return true;
    }

    public function handleChangedSchema(ConfigEvent $event): void
    {
        $schemaUid = $event->tokenMatches[0];
        $data = $event->newValue;

        DB::beginTransaction();

        try {
            $schemaModel = $this->getSchemaModel($schemaUid);
            $isNew = ! $schemaModel->exists;

            $schemaModel->uid = $schemaUid;
            $schemaModel->name = $data['name'];
            $schemaModel->isPublic = (bool) ($data['isPublic'] ?? false);
            $schemaModel->scope = ($data['scope'] ?? false) ?: [];

            // Save the schema record
            $schemaModel->save();

            // If we're updating to 3.4+, check if the old token info for this schema was cached
            if (
                $isNew &&
                ($allSchemas = Cache::get('migration:add_gql_project_config_support:schemas')) &&
                ! empty($allSchemas[$schemaUid])
            ) {
                $migratedSchema = $allSchemas[$schemaUid];
                $token = new GqlToken([
                    'name' => $migratedSchema['name'],
                    'accessToken' => $migratedSchema['accessToken'],
                    'enabled' => $migratedSchema['enabled'],
                    'expiryDate' => $migratedSchema['expiryDate'],
                    'lastUsed' => $migratedSchema['lastUsed'],
                    'schemaId' => $schemaModel->id,
                ]);
                $this->saveToken($token);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->invalidateCaches();
    }

    public function deleteSchemaById(int $id): bool
    {
        $schema = $this->getSchemaById($id);

        if (! $schema) {
            return false;
        }

        return $this->deleteSchema($schema);
    }

    public function deleteSchema(GqlSchema $schema): bool
    {
        $this->projectConfig->remove(ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.'.$schema->uid,
            "Delete the “{$schema->name}” GraphQL schema");

        return true;
    }

    public function handleDeletedSchema(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $schemaModel = $this->getSchemaModel($uid);

        if (! $schemaModel->exists) {
            return;
        }

        DB::beginTransaction();

        try {
            // Delete the schema
            DB::table(Table::GQLSCHEMAS)->delete($schemaModel->id);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->invalidateCaches();
    }

    /**
     * @param  int  $id  The schema's ID
     */
    public function getSchemaById(int $id): ?GqlSchema
    {
        $result = $this->_createSchemaQuery()->find($id);

        return $result ? new GqlSchema((array) $result) : null;
    }

    /**
     * @param  string  $uid  The schema's UID
     */
    public function getSchemaByUid(string $uid): ?GqlSchema
    {
        $result = $this->_createSchemaQuery()
            ->where('uid', $uid)
            ->first();

        return $result ? new GqlSchema((array) $result) : null;
    }

    /** @return GqlSchema[] */
    public function getSchemas(): array
    {
        return $this->_createSchemaQuery()
            ->get()
            ->map(fn (object $result) => new GqlSchema((array) $result))
            ->all();
    }

    /**
     * @param  class-string<BaseElementInterface>  $elementType
     *
     * @phpstan-param callable(): array<string, ArgumentConfig> $setter
     *
     * @return array<string, ArgumentConfig>
     */
    public function getOrSetContentArguments(string $elementType, callable $setter): array
    {
        $this->_contentArguments[$elementType] ??= $setter();

        return $this->_contentArguments[$elementType];
    }

    /** @return array<string, ArgumentConfig> */
    public function getFieldLayoutArguments(FieldLayout $fieldLayout): array
    {
        if (! isset($fieldLayout->type)) {
            throw new InvalidArgumentException('Field layout is missing its element type.');
        }

        $this->_fieldArguments[$fieldLayout->uid] ??= $this->defineContentArgumentsForFields($fieldLayout->type, $fieldLayout->getCustomFields()) +
        $this->defineContentArgumentsForGeneratedFields($fieldLayout->type, $fieldLayout->getGeneratedFields());

        return $this->_fieldArguments[$fieldLayout->uid];
    }

    /**
     * @param  class-string<BaseElementInterface>  $elementType
     * @param  FieldLayout[]  $fieldLayouts
     * @return array<string, ArgumentConfig>
     */
    public function defineContentArgumentsForFieldLayouts(string $elementType, array $fieldLayouts): array
    {
        $fields = [];
        $generatedFields = [];
        $handledFieldLayouts = [];

        foreach ($fieldLayouts as $fieldLayout) {
            // avoid checking the same field layout more than once
            if (isset($fieldLayout->uid)) {
                if (isset($handledFieldLayouts[$fieldLayout->uid])) {
                    continue;
                }
                $handledFieldLayouts[$fieldLayout->uid] = true;
            }

            array_push($fields, ...$fieldLayout->getCustomFields());
            array_push($generatedFields, ...$fieldLayout->getGeneratedFields());
        }

        return $this->defineContentArgumentsForFields($elementType, $fields) +
            $this->defineContentArgumentsForGeneratedFields($elementType, $generatedFields);
    }

    /**
     * @param  class-string<BaseElementInterface>  $elementType
     * @param  FieldInterface[]  $fields
     * @return array<string, ArgumentConfig>
     */
    public function defineContentArgumentsForFields(string $elementType, array $fields): array
    {
        $arguments = [];
        $elementQuery = Elements::createElementQuery($elementType);

        foreach ($fields as $field) {
            if (
                ! isset($arguments[$field->handle]) &&
                ! $field instanceof GqlInlineFragmentFieldInterface &&
                ! method_exists($elementQuery, $field->handle)
            ) {
                $arguments[$field->handle] = $field->getContentGqlQueryArgumentType();
            }
        }

        return $arguments;
    }

    /**
     * @param  class-string<BaseElementInterface>  $elementType
     * @param  array<array-key, array{handle?: string}>  $fields
     * @return array<string, ArgumentConfig>
     */
    public function defineContentArgumentsForGeneratedFields(string $elementType, array $fields): array
    {
        $arguments = [];
        $elementQuery = Elements::createElementQuery($elementType);

        foreach ($fields as $field) {
            $handle = $field['handle'] ?? '';
            if (
                $handle !== '' &&
                ! isset($arguments[$handle]) &&
                ! method_exists($elementQuery, $handle)
            ) {
                $arguments[$handle] = [
                    'name' => $handle,
                    'type' => Type::listOf(QueryArgument::getType()),
                ];
            }
        }

        return $arguments;
    }

    /**
     * @param  class-string<BaseElementInterface>  $elementType
     * @param  array<array-key, FieldLayoutProviderInterface>  $contexts
     * @return array<string, ArgumentConfig>
     */
    public function getContentArguments(array $contexts, string $elementType): array
    {
        return $this->getOrSetContentArguments($elementType, function () use ($contexts, $elementType): array {
            $fields = [];
            $generatedFields = [];

            foreach ($contexts as $context) {
                if (GqlHelper::isSchemaAwareOf($elementType::gqlScopesByContext($context))) {
                    $layout = $context->getFieldLayout();
                    try {
                        array_push($fields, ...$layout->getCustomFields());
                        array_push($generatedFields, ...$layout->getGeneratedFields());
                    } catch (BadMethodCallException) {
                    }
                }
            }

            return $this->defineContentArgumentsForFields($elementType, $fields) +
                $this->defineContentArgumentsForGeneratedFields($elementType, $generatedFields);
        });
    }

    /**
     * @param  list<Error>  $errors
     * @param  callable(Throwable): array{message: string, locations?: array<int, array{line: int, column: int}>, path?: array<int, int|string>, extensions?: array<string, mixed>}  $formatter
     * @return list<array{message: string, locations?: array<int, array{line: int, column: int}>, path?: array<int, int|string>, extensions?: array<string, mixed>}>
     */
    public function handleQueryErrors(array $errors, callable $formatter): array
    {
        $devMode = Cms::config()->devMode;

        foreach ($errors as &$error) {
            $originException = $nextException = $error;

            // Get the origin exception.
            while ($nextException = $nextException->getPrevious()) {
                $originException = $nextException;
            }

            // If devMode enabled or exception is safe to show, substitute the original exception here.
            if (
                ($devMode || ($originException instanceof ClientAware && $originException->isClientSafe())) &&
                ! empty($originException->getMessage())
            ) {
                $error = $originException;
            } elseif (! $originException instanceof Error) {
                // If devMode not enabled and the error seems to be originating from Craft, display a generic message
                $error = new Error(
                    t('Something went wrong when processing the GraphQL query.'),
                );
            }

            // Log it.
            report($originException);
        }

        return array_map($formatter, $errors);
    }

    /**
     * @param  array<string, FieldDefinitionConfig>  $fields
     * @return array<string, FieldDefinitionConfig>
     */
    public function prepareFieldDefinitions(array $fields, string $typeName): array
    {
        if (! array_key_exists($typeName, $this->_typeDefinitions)) {
            $this->_typeManager ??= new TypeManager;

            $this->_typeDefinitions[$typeName] = $this->_typeManager->registerFieldDefinitions($fields, $typeName);
        }

        return $this->_typeDefinitions[$typeName];
    }

    /** @param array<string, mixed>|null $variables */
    private function _getCacheKey(
        GqlSchema $schema,
        string $query,
        mixed $rootValue,
        ?array $variables = null,
        ?string $operationName = null,
    ): ?string {
        // No cache key, if explicitly disabled
        $generalConfig = Cms::config();

        if (! $generalConfig->enableGraphqlCaching) {
            return null;
        }

        // Do not cache mutations
        if (GqlHelper::isMutation($query, $operationName)) {
            return null;
        }

        // No cache key if we have placeholder elements
        if (! empty(Elements::getPlaceholderElements())) {
            return null;
        }

        try {
            $cacheKey = self::CACHE_TAG.
                '::'.Sites::getCurrentSite()->id.
                '::'.$schema->uid.
                '::'.md5($query).
                '::'.serialize($rootValue).
                '::'.Info::fetch()->configVersion.
                '::'.serialize($variables).
                ($operationName ? "::$operationName" : '');
        } catch (Throwable $e) {
            report($e);
            $cacheKey = null;
        }

        return $cacheKey;
    }

    /** @return array<string, FieldDefinitionConfig> */
    protected function queryDefinitions(): array
    {
        return $this->operationDefinitions($this->gqlQueries->types(), 'getQueries');
    }

    /** @return array<string, FieldDefinitionConfig> */
    protected function mutationDefinitions(): array
    {
        return $this->operationDefinitions($this->gqlMutations->types(), 'getMutations');
    }

    /**
     * @return array<class-string<SingularTypeInterface>> the list of registered types.
     */
    private function _registerGqlTypes(): array
    {
        $types = $this->gqlTypes->types()->all();

        foreach ($types as $type) {
            /** @var class-string<SingularTypeInterface> $type */
            TypeLoader::registerType($type::getName(), "$type::getType");
        }

        return $types;
    }

    private function _registerGqlQueries(): void
    {
        $queries = $this->queryDefinitions();

        TypeLoader::registerType('Query', fn () => call_user_func(Query::class.'::getType', $queries));
    }

    private function _registerGqlMutations(): void
    {
        $mutations = $this->mutationDefinitions();

        TypeLoader::registerType('Mutation', fn () => call_user_func(Mutation::class.'::getType', $mutations));
    }

    /**
     * @param  iterable<class-string>  $providers
     * @return array<string, FieldDefinitionConfig>
     */
    private function operationDefinitions(iterable $providers, string $method): array
    {
        $definitions = [];

        foreach ($providers as $provider) {
            $definitions = array_merge($definitions, $provider::$method());
        }

        return $definitions;
    }

    /**
     * @return GqlDirective[]
     */
    private function _loadGqlDirectives(?GqlSchema $schema): array
    {
        /** @var class-string<Directive>[] $directiveClasses */
        $directiveClasses = $this->gqlDirectives
            ->forSchema($schema)
            ->all();

        $directives = GqlDirective::builtInDirectives();

        foreach ($directiveClasses as $class) {
            /** @var class-string<Directive> $class */
            $directives[] = $class::create();
        }

        return $directives;
    }

    /** @return array{SchemaComponents, SchemaComponents} */
    private function siteSchemaComponents(): array
    {
        $sites = Sites::getAllSites(true);
        $queryComponents = [];

        foreach ($sites as $site) {
            $queryComponents["sites.{$site->uid}:read"] = [
                'label' => t('Query for elements in the “{site}” site', [
                    'site' => $site->getName(),
                ]),
            ];
        }

        return [$queryComponents, []];
    }

    /** @return array{SchemaComponents, SchemaComponents} */
    private function elementSchemaComponents(): array
    {
        $queryComponents = [
            'elements.drafts:read' => [
                'label' => t('Query for element drafts'),
            ],
            'elements.revisions:read' => [
                'label' => t('Query for element revisions'),
            ],
            'elements.inactive:read' => [
                'label' => t('Query for non-enabled elements'),
            ],
        ];

        return [$queryComponents, []];
    }

    /** @return array{SchemaComponents, SchemaComponents} */
    private function entrySchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $singles = Sections::getSectionsByType(SectionType::Single);

        foreach ($singles as $section) {
            $name = t($section->name, category: 'site');
            $prefix = "sections.$section->uid";
            $queryComponents["$prefix:read"] = [
                'label' => t('Query for the “{name}” entry', ['name' => $name]),
            ];
            $mutationComponents["$prefix:save"] = [
                'label' => t('Save the “{section}” entry', ['section' => $name]),
            ];
        }

        foreach (Sections::getAllSections() as $section) {
            if ($section->type === SectionType::Single) {
                continue;
            }

            $name = t($section->name, category: 'site');
            $prefix = "sections.$section->uid";

            $queryComponents["$prefix:read"] = [
                'label' => t('Query for entries in the “{name}” section', ['name' => $name]),
            ];

            $mutationComponents["$prefix:edit"] = [
                'label' => t('Edit entries in the “{name}” section', ['name' => $name]),
                'nested' => [
                    "$prefix:create" => [
                        'label' => t('Create entries in the “{section}” section', ['section' => $name]),
                    ],
                    "$prefix:save" => [
                        'label' => t('Save entries in the “{section}” section', ['section' => $name]),
                    ],
                    "$prefix:delete" => [
                        'label' => t('Delete entries in the “{section}” section', ['section' => $name]),
                    ],
                ],
            ];
        }

        // Add components for fields that manage nested entries
        $fieldsService = app(Fields::class);
        /** @var ElementContainerFieldInterface[] $fields */
        $fields = array_merge(...array_map(
            fn (string $type) => $fieldsService->getFieldsByType($type)->all(),
            $fieldsService->getNestedEntryFieldTypes()->all(),
        ));
        usort($fields, fn (
            ElementContainerFieldInterface $a,
            ElementContainerFieldInterface $b,
        ) => $a::displayName() <=> $b::displayName());

        foreach ($fields as $field) {
            $name = t($field->name, category: 'site');
            $type = $field::displayName();
            $prefix = "nestedentryfields.$field->uid";

            $queryComponents["$prefix:read"] = [
                'label' => t('Query for entries in the “{name}” {type} field', [
                    'name' => $name,
                    'type' => $type,
                ]),
            ];

            $mutationComponents["$prefix:edit"] = [
                'label' => t('Edit entries in the “{name}” {type} field', [
                    'name' => $name,
                    'type' => $type,
                ]),
                'nested' => [
                    "$prefix:create" => [
                        'label' => t('Create entries in the “{section}” {type} field', [
                            'section' => $name, // todo: change to 'name'
                            'type' => $type,
                        ]),
                    ],
                    "$prefix:save" => [
                        'label' => t('Save entries in the “{section}” {type} field', [
                            'section' => $name, // todo: change to 'name'
                            'type' => $type,
                        ]),
                    ],
                    "$prefix:delete" => [
                        'label' => t('Delete entries in the “{section}” {type} field', [
                            'section' => $name, // todo: change to 'name'
                            'type' => $type,
                        ]),
                    ],
                ],
            ];
        }

        return [$queryComponents, $mutationComponents];
    }

    /** @return array{SchemaComponents, SchemaComponents} */
    private function assetSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $volumes = app(Volumes::class)->getAllVolumes();

        if ($volumes->isNotEmpty()) {
            foreach ($volumes as $volume) {
                $name = t($volume->name, category: 'site');
                $prefix = "volumes.$volume->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for assets in the “{name}” volume', ['name' => $name]),
                ];
                $mutationComponents["$prefix:edit"] = [
                    'label' => t('Edit assets in the “{volume}” volume', ['volume' => $name]),
                    'nested' => [
                        "$prefix:create" => [
                            'label' => t('Create assets in the “{volume}” volume', ['volume' => $name]),
                        ],
                        "$prefix:save" => [
                            'label' => t('Modify assets in the “{volume}” volume', ['volume' => $name]),
                        ],
                        "$prefix:delete" => [
                            'label' => t('Delete assets from the “{volume}” volume', ['volume' => $name]),
                        ],
                    ],
                ];
            }
        }

        return [$queryComponents, $mutationComponents];
    }

    /** @return array{SchemaComponents, SchemaComponents} */
    private function userSchemaComponents(): array
    {
        $queryComponents = [];

        if (Edition::get() === Edition::Solo) {
            $queryComponents['usergroups.solo:read'] = [
                'label' => t('View {type}', ['type' => User::lowerDisplayName()]),
            ];
        } else {
            $userGroups = UserGroups::getAllGroups();

            $queryComponents['usergroups.everyone:read'] = [
                'label' => t('Query for users'),
            ];

            foreach ($userGroups as $userGroup) {
                $name = t($userGroup->name, category: 'site');
                $prefix = "usergroups.$userGroup->uid";
                $queryComponents["$prefix:read"] = [
                    'label' => t('Query for users in the “{name}” user group', ['name' => $name]),
                ];
            }
        }

        return [$queryComponents, []];
    }

    private function _createTokenQuery(): Builder
    {
        return DB::table(Table::GQLTOKENS)
            ->select([
                'id',
                'schemaId',
                'name',
                'accessToken',
                'enabled',
                'expiryDate',
                'lastUsed',
                'dateCreated',
                'uid',
            ]);
    }

    private function _createSchemaQuery(): Builder
    {
        return DB::table(Table::GQLSCHEMAS)
            ->select([
                'id',
                'name',
                'scope',
                'isPublic',
                'uid',
            ]);
    }

    private function _getPublicSchema(): ?GqlSchema
    {
        $result = $this->_createSchemaQuery()->where('isPublic', true)->first();

        return $result ? new GqlSchema((array) $result) : null;
    }

    private function _createPublicSchema(): GqlSchema
    {
        $schemaUid = Str::uuid()->toString();
        $publicSchema = new GqlSchema([
            'name' => 'Public Schema',
            'uid' => $schemaUid,
            'isPublic' => true,
        ]);

        $this->saveSchema($publicSchema, false);

        return $publicSchema;
    }

    private function getSchemaModel(string $uid): GqlSchemaModel
    {
        return GqlSchemaModel::findByUid($uid) ?? new GqlSchemaModel;
    }

    private function _saveTokenInternal(GqlToken $token): void
    {
        $isNewToken = ! $token->id;

        if ($isNewToken) {
            $tokenModel = new GqlTokenModel;
        } else {
            $tokenModel = GqlTokenModel::find($token->id) ?? new GqlTokenModel;
        }

        $tokenModel->name = $token->name;
        $tokenModel->enabled = $token->enabled;
        $tokenModel->expiryDate = \CraftCms\Cms\Support\Query::prepareDateForDb($token->expiryDate);
        $tokenModel->lastUsed = \CraftCms\Cms\Support\Query::prepareDateForDb($token->lastUsed);
        $tokenModel->schemaId = $token->schemaId;

        if ($token->accessToken) {
            $tokenModel->accessToken = $token->accessToken;
        }

        $tokenModel->save();
        $token->id = $tokenModel->id;
        $token->uid = $tokenModel->uid;
    }
}
