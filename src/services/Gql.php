<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query as DbQuery;
use craft\db\Table;
use craft\errors\GqlException;
use craft\events\ConfigEvent;
use craft\events\DefineGqlValidationRulesEvent;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlPermissionsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\ArgumentManager;
use craft\gql\base\Directive;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\InterfaceType;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Markdown;
use craft\gql\directives\ParseRefs;
use craft\gql\directives\Transform;
use craft\gql\ElementQueryConditionBuilder;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\mutations\Asset as AssetMutation;
use craft\gql\mutations\Category as CategoryMutation;
use craft\gql\mutations\Entry as EntryMutation;
use craft\gql\mutations\GlobalSet as GlobalSetMutation;
use craft\gql\mutations\Ping as PingMutation;
use craft\gql\mutations\Tag as TagMutation;
use craft\gql\queries\Asset as AssetQuery;
use craft\gql\queries\Category as CategoryQuery;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\Ping as PingQuery;
use craft\gql\queries\Tag as TagQuery;
use craft\gql\queries\User as UserQuery;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;
use craft\gql\types\Mutation;
use craft\gql\types\Number;
use craft\gql\types\Query;
use craft\gql\types\QueryArgument;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\Section;
use craft\records\GqlSchema as GqlSchemaRecord;
use craft\records\GqlToken as GqlTokenRecord;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\Error;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\KnownTypeNames;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\caching\TagDependency;

/**
 * The Gql service provides GraphQL functionality.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class Gql extends Component
{
    /**
     * @event RegisterGqlTypesEvent The event that is triggered when registering GraphQL types.
     *
     * Plugins get a chance to add their own GraphQL types.
     * See [GraphQL API](https://craftcms.com/docs/3.x/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlTypeEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     *
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypeEvent $event) {
     *     // Add my GraphQL types
     *     $event->types[] = MyType::class;
     * });
     * ```
     */
    const EVENT_REGISTER_GQL_TYPES = 'registerGqlTypes';

    /**
     * @event RegisterGqlQueriesEvent The event that is triggered when registering GraphQL queries.
     *
     * Plugins get a chance to add their own GraphQL queries.
     * See [GraphQL API](https://craftcms.com/docs/3.x/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlQueriesEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     * use GraphQL\Type\Definition\Type;
     *
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
     *     // Add my GraphQL queries
     *     $event->queries['queryPluginData'] =
     *     [
     *         'type' => Type::listOf(MyType::getType()),
     *         'args' => MyArguments::getArguments(),
     *         'resolve' => MyResolver::class . '::resolve'
     *     ];
     * });
     * ```
     */
    const EVENT_REGISTER_GQL_QUERIES = 'registerGqlQueries';

    /**
     * @event RegisterGqlMutationsEvent The event that is triggered when registering GraphQL mutations.
     *
     * Plugins get a chance to add their own GraphQL mutations.
     * See [GraphQL API](https://craftcms.com/docs/3.x/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlMutationsEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     * use GraphQL\Type\Definition\Type;
     *
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_MUTATIONS, function(RegisterGqlMutationsEvent $event) {
     *     // Add my GraphQL queries
     *     $event->queries['mutationPluginData'] =
     *     [
     *         'type' => Type::listOf(MyType::getType()),
     *         'args' => MyArguments::getArguments(),
     *     ];
     * });
     * ```
     */
    const EVENT_REGISTER_GQL_MUTATIONS = 'registerGqlMutations';

    /**
     * @event RegisterGqlDirectivesEvent The event that is triggered when registering GraphQL directives.
     *
     * Plugins get a chance to add their own GraphQL directives.
     * See [GraphQL API](https://craftcms.com/docs/3.x/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlDirectivesEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     *
     * Event::on(Gql::class,
     *     Gql::EVENT_REGISTER_GQL_DIRECTIVES,
     *     function(RegisterGqlDirectivesEvent $event) {
     *         $event->directives[] = MyDirective::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_GQL_DIRECTIVES = 'registerGqlDirectives';

    /**
     * @event RegisterGqlPermissionsEvent The event that is triggered when registering user permissions.
     * @since 3.4.0
     * @deprecated in 3.5.0. Use the [[EVENT_REGISTER_GQL_SCHEMA_COMPONENTS]] event instead.
     */
    const EVENT_REGISTER_GQL_PERMISSIONS = 'registerGqlPermissions';

    /**
     * @event RegisterGqlSchemaComponentsEvent The event that is triggered when registering GraphQL schema components.
     * @since 3.5.0
     */
    const EVENT_REGISTER_GQL_SCHEMA_COMPONENTS = 'registerGqlSchemaComponents';

    /**
     * @event DefineGqlValidationRulesEvent The event that is triggered when defining validation rules to be used.
     *
     * Plugins get a chance to alter the GraphQL validation rule list.
     *
     * ---
     * ```php
     * use craft\events\DefineGqlValidationRulesEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     * use GraphQL\Type\Definition\Type;
     * use GraphQL\Validator\Rules\DisableIntrospection;
     *
     * Event::on(Gql::class, Gql::::EVENT_DEFINE_GQL_VALIDATION_RULES, function (DefineGqlValidationRulesEvent $event) {
     *     // Disable introspection permanently.
     *     $event->validationRules[DisableIntrospection::class] = new DisableIntrospection();
     * });
     * ```
     */
    const EVENT_DEFINE_GQL_VALIDATION_RULES = 'defineGqlValidationRules';

    /**
     * @event ExecuteGqlQueryEvent The event that is triggered before executing the GraphQL query.
     *
     * Plugins get a chance to modify the query or return a cached response.
     *
     * ---
     * ```php
     * use craft\events\ExecuteGqlQueryEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     *
     * Event::on(Gql::class,
     *     Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY,
     *     function(ExecuteGqlQueryEvent $event) {
     *         // Set the result from cache
     *         $event->result = ...;
     *     }
     * );
     * ```
     *
     * @since 3.3.11
     */
    const EVENT_BEFORE_EXECUTE_GQL_QUERY = 'beforeExecuteGqlQuery';

    /**
     * @event ExecuteGqlQueryEvent The event that is triggered after executing the GraphQL query.
     *
     * Plugins get a chance to do something after a performed GraphQL query.
     *
     * ---
     * ```php
     * use craft\events\ExecuteGqlQueryEvent;
     * use craft\services\Gql;
     * use yii\base\Event;
     *
     * Event::on(Gql::class,
     *     Gql::EVENT_AFTER_EXECUTE_GQL_QUERY,
     *     function(ExecuteGqlQueryEvent $event) {
     *         // Cache the results from $event->result or just tweak them
     *     }
     * );
     * ```
     *
     * @since 3.3.11
     */
    const EVENT_AFTER_EXECUTE_GQL_QUERY = 'afterExecuteGqlQuery';

    /**
     * @since 3.3.12
     */
    const CACHE_TAG = 'graphql';

    /**
     * @since 3.5.0
     */
    const CONFIG_GQL_KEY = 'graphql';

    /**
     * @since 3.4.0
     */
    const CONFIG_GQL_SCHEMAS_KEY = self::CONFIG_GQL_KEY . '.' . 'schemas';

    /**
     * @since 3.5.0
     */
    const CONFIG_GQL_PUBLIC_TOKEN_KEY = self::CONFIG_GQL_KEY . '.' . 'publicToken';

    /**
     * The field name to use when fetching count of related elements
     *
     * @since 3.4.0
     */
    const GRAPHQL_COUNT_FIELD = '_count';

    /**
     * Complexity value for accessing a simple field.
     *
     * @since 3.6.0
     */
    const GRAPHQL_COMPLEXITY_SIMPLE_FIELD = 1;

    /**
     * Complexity value for accessing a field that will trigger a single query for the request.
     *
     * @since 3.6.0
     */
    const GRAPHQL_COMPLEXITY_QUERY = 10;

    /**
     * Complexity value for accessing a field that will add an instance of eager-loading for the request.
     *
     * @since 3.6.0
     */
    const GRAPHQL_COMPLEXITY_EAGER_LOAD = 25;

    /**
     * Complexity value for accessing a field that will likely trigger a CPU heavy operation.
     *
     * @since 3.6.0
     */
    const GRAPHQL_COMPLEXITY_CPU_HEAVY = 200;

    /**
     * Complexity value for accessing a field that will trigger a query for every parent returned,
     *
     * @since 3.6.0
     */
    const GRAPHQL_COMPLEXITY_NPLUS1 = 500;

    /**
     * Save a GQL Token record based on the model.
     *
     * @param GqlToken $token
     */
    private function _saveTokenInternal(GqlToken $token)
    {
        $isNewToken = !$token->id;

        if ($isNewToken) {
            $tokenRecord = new GqlTokenRecord();
        } else {
            $tokenRecord = GqlTokenRecord::findOne($token->id) ?: new GqlTokenRecord();
        }

        $tokenRecord->name = $token->name;
        $tokenRecord->enabled = (bool)$token->enabled;
        $tokenRecord->expiryDate = $token->expiryDate;
        $tokenRecord->lastUsed = $token->lastUsed;
        $tokenRecord->schemaId = $token->schemaId;

        if ($token->accessToken) {
            $tokenRecord->accessToken = $token->accessToken;
        }

        $tokenRecord->save();
        $token->id = $tokenRecord->id;
        $token->uid = $tokenRecord->uid;
    }

    /**
     * @var Schema Currently loaded schema definition
     */
    private $_schemaDef;

    /**
     * @var GqlSchema The active GraphQL schema
     * @see setActiveSchema()
     */
    private $_schema;

    /**
     * @var array Cache of content fields by element class
     */
    private $_contentFieldCache = [];

    /**
     * Returns the GraphQL schema.
     *
     * @param GqlSchema $schema
     * @param bool $prebuildSchema should the schema be deep-scanned and pre-built instead of lazy-loaded
     * @return Schema
     * @throws GqlException in case of invalid schema
     */
    public function getSchemaDef(GqlSchema $schema = null, $prebuildSchema = false): Schema
    {
        if ($schema) {
            $this->setActiveSchema($schema);
        }
        if (!$this->_schemaDef || $prebuildSchema) {
            // Either cached version was not found or we need a pre-built schema.
            $registeredTypes = $this->_registerGqlTypes();
            $this->_registerGqlQueries();
            $this->_registerGqlMutations();

            $schemaConfig = [
                'typeLoader' => TypeLoader::class . '::loadType',
                'query' => TypeLoader::loadType('Query'),
                'mutation' => TypeLoader::loadType('Mutation'),
                'directives' => $this->_loadGqlDirectives(),
            ];

            // If we're not required to pre-build the schema the relevant GraphQL types will be added to the Schema
            // as the query is being resolved thanks to the magic of lazy-loading, so we needn't worry.
            if (!$prebuildSchema) {
                $this->_schemaDef = new Schema($schemaConfig);
                return $this->_schemaDef;
            }

            foreach ($registeredTypes as $registeredType) {
                if (method_exists($registeredType, 'getTypeGenerator')) {
                    /** @var GeneratorInterface $typeGeneratorClass */
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
            } catch (\Throwable $exception) {
                throw new GqlException('Failed to validate the GQL Schema - ' . $exception->getMessage());
            }
        }

        return $this->_schemaDef;
    }

    /**
     * Return a set of validation rules to use.
     *
     * @param bool $debug Whether debugging validation rules should be allowed.
     * @param bool $isIntrospectionQuery Whether this is an introspection query
     * @return array
     */
    public function getValidationRules(bool $debug = false, bool $isIntrospectionQuery = false): array
    {
        $validationRules = DocumentValidator::defaultRules();

        if (!$debug) {
            // Remove the rules which would generate a full schema just for a nice message, to avoid performance hit.
            unset(
                $validationRules[KnownTypeNames::class],
                $validationRules[FieldsOnCorrectType::class]
            );
        }

        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$isIntrospectionQuery) {
            // Set complexity rule, if defined,
            if (!empty($generalConfig->maxGraphqlComplexity)) {
                $validationRules[QueryComplexity::class] = new QueryComplexity($generalConfig->maxGraphqlComplexity);
            }

            // Set depth rule, if defined,
            if (!empty($generalConfig->maxGraphqlDepth)) {
                $validationRules[QueryDepth::class] = new QueryDepth($generalConfig->maxGraphqlDepth);
            }
        }

        if (!$generalConfig->enableGraphqlIntrospection && Craft::$app->getUser()->getIsGuest()) {
            $validationRules[DisableIntrospection::class] = new DisableIntrospection();
        }

        $event = new DefineGqlValidationRulesEvent([
            'validationRules' => $validationRules,
            'debug' => $debug
        ]);

        $this->trigger(self::EVENT_DEFINE_GQL_VALIDATION_RULES, $event);

        return array_values($event->validationRules);
    }

    /**
     * Execute a GraphQL query for a given schema.
     *
     * @param GqlSchema $schema The schema definition to use.
     * @param string $query The query string to execute.
     * @param array|null $variables The variables to use.
     * @param string|null $operationName The operation name.
     * @param bool $debugMode Whether debug mode validations rules should be used for GraphQL.
     * @return array
     * @since 3.3.11
     */
    public function executeQuery(
        GqlSchema $schema,
        string $query,
        array $variables = null,
        string $operationName = null,
        bool $debugMode = false
    ): array {
        $event = new ExecuteGqlQueryEvent([
            'schemaId' => $schema->id,
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
            'context' => [
                'conditionBuilder' => Craft::createObject([
                    'class' => ElementQueryConditionBuilder::class,
                ]),
                'argumentManager' => Craft::createObject([
                    'class' => ArgumentManager::class
                ])
            ]
        ]);

        $this->trigger(self::EVENT_BEFORE_EXECUTE_GQL_QUERY, $event);

        if ($event->result === null) {
            $cacheKey = $this->_getCacheKey(
                $schema,
                $event->query,
                $event->rootValue,
                $event->context,
                $event->variables,
                $event->operationName
            );

            if ($cacheKey && ($cachedResult = $this->getCachedResult($cacheKey)) !== null) {
                $event->result = $cachedResult;
            } else {
                $isIntrospectionQuery = StringHelper::containsAny($event->query, ['__schema', '__type']);
                $schemaDef = $this->getSchemaDef($schema, $debugMode || $isIntrospectionQuery);
                $elementsService = Craft::$app->getElements();
                $elementsService->startCollectingCacheTags();

                $event->result = GraphQL::executeQuery(
                    $schemaDef,
                    $event->query,
                    $event->rootValue,
                    $event->context,
                    $event->variables,
                    $event->operationName,
                    null,
                    $this->getValidationRules($debugMode, $isIntrospectionQuery)
                )
                ->setErrorsHandler([$this, 'handleQueryErrors'])
                ->toArray($debugMode ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE : false);

                $dep = $elementsService->stopCollectingCacheTags();

                if (empty($event->result['errors']) && $cacheKey) {
                    $this->setCachedResult($cacheKey, $event->result, $dep);
                }
            }
        }

        $this->trigger(self::EVENT_AFTER_EXECUTE_GQL_QUERY, $event);

        return $event->result ?? [];
    }

    /**
     * Invalidates all GraphQL result caches.
     *
     * @since 3.3.12
     */
    public function invalidateCaches()
    {
        TagDependency::invalidate(Craft::$app->getCache(), self::CACHE_TAG);
    }

    /**
     * Returns the cached result for a key.
     *
     * @param string $cacheKey
     * @return array|null
     * @since 3.3.12
     */
    public function getCachedResult($cacheKey)
    {
        return Craft::$app->getCache()->get($cacheKey) ?: null;
    }

    /**
     * Cache a result for the key and tag it.
     *
     * @param string $cacheKey
     * @param array $result
     * @param TagDependency|null $dependency
     * @since 3.3.12
     */
    public function setCachedResult(string $cacheKey, array $result, TagDependency $dependency = null)
    {
        if ($dependency === null) {
            $dependency = new TagDependency();
        }

        // Add the global graphql cache tag
        $dependency->tags[] = self::CACHE_TAG;

        Craft::$app->getCache()->set($cacheKey, $result, null, $dependency);
    }

    /**
     * Returns the active GraphQL schema.
     *
     * @return GqlSchema
     * @throws GqlException if no schema is currently active.
     */
    public function getActiveSchema(): GqlSchema
    {
        if (!$this->_schema) {
            throw new GqlException('No schema is active.');
        }

        return $this->_schema;
    }

    /**
     * Sets the active GraphQL schema.
     *
     * @param GqlSchema|null $schema The schema, or `null` to unset the active schema
     * @throws Exception
     */
    public function setActiveSchema(GqlSchema $schema = null)
    {
        $this->_schema = $schema;
    }

    /**
     * Returns all GraphQL tokens.
     *
     * @return GqlToken[]
     * @since 3.4.0
     */
    public function getTokens(): array
    {
        $rows = $this->_createTokenQuery()->all();
        $schemas = [];
        $names = [];

        $publicToken = null;

        foreach ($rows as $row) {
            $token = new GqlToken($row);

            if (!$token->getIsPublic()) {
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
     * @return GqlSchema|null
     * @throws Exception
     */
    public function getPublicSchema()
    {
        $token = $this->getPublicToken();
        return $token ? $token->getSchema() : null;
    }

    /**
     * Returns all of the known GraphQL permissions, sorted by category.
     *
     * @return array
     * @deprecated in 3.5.0. Use [[\craft\services\Gql::get()]] instead.
     */
    public function getAllPermissions(): array
    {
        return $this->getAllSchemaComponents()['queries'];
    }

    /**
     * Returns all of the known GraphQL schema components.
     *
     * @return array
     * @since 3.5.0
     */
    public function getAllSchemaComponents(): array
    {
        $queries = [];
        $mutations = [];

        // Entries
        // ---------------------------------------------------------------------
        $components = $this->_getSectionSchemaComponents();
        $label = Craft::t('app', 'Entries');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Assets
        // ---------------------------------------------------------------------
        $components = $this->_getVolumeSchemaComponents();
        $label = Craft::t('app', 'Assets');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Global Sets
        // ---------------------------------------------------------------------
        $components = $this->_getGlobalSetSchemaComponents();
        $label = Craft::t('app', 'Global sets');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Users
        // ---------------------------------------------------------------------
        $components = $this->_getUserSchemaComponents();
        $label = Craft::t('app', 'Users');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Categories
        // ---------------------------------------------------------------------
        $components = $this->_getCategorySchemaComponents();
        $label = Craft::t('app', 'Categories');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Tags
        // ---------------------------------------------------------------------
        $components = $this->_getTagSchemaComponents();
        $label = Craft::t('app', 'Tags');
        $queries[$label] = $components['query'] ?? [];
        $mutations[$label] = $components['mutation'] ?? [];

        // Let plugins customize them and add new ones
        // ---------------------------------------------------------------------

        if ($this->hasEventHandlers(self::EVENT_REGISTER_GQL_PERMISSIONS)) {
            $deprecatedEvent = new RegisterGqlPermissionsEvent([
                'permissions' => $queries
            ]);

            $this->trigger(self::EVENT_REGISTER_GQL_PERMISSIONS, $deprecatedEvent);

            $queries = $deprecatedEvent->permissions;
        }

        $event = new RegisterGqlSchemaComponentsEvent([
            'queries' => $queries,
            'mutations' => $mutations
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, $event);

        return [
            'queries' => $event->queries,
            'mutations' => $event->mutations
        ];
    }

    /**
     * Flush all GraphQL caches, registries and loaders.
     */
    public function flushCaches()
    {
        $this->_schema = null;
        $this->_schemaDef = null;
        $this->_contentFieldCache = [];
        TypeLoader::flush();
        GqlEntityRegistry::flush();
        TypeManager::flush();
        $this->invalidateCaches();
    }

    /**
     * Returns a GraphQL token by its id.
     *
     * @param int $id
     * @return GqlToken|null
     * @since 3.4.0
     */
    public function getTokenById(int $id)
    {
        $result = $this->_createTokenQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new GqlToken($result) : null;
    }

    /**
     * Returns a GraphQL token by its name.
     *
     * @param string $tokenName
     * @return GqlToken|null
     * @since 3.4.0
     */
    public function getTokenByName(string $tokenName)
    {
        $result = $this->_createTokenQuery()
            ->where(['name' => $tokenName])
            ->one();

        return $result ? new GqlToken($result) : null;
    }

    /**
     * Returns a GraphQL token by its UID.
     *
     * @param string $uid
     * @return GqlToken
     * @throws InvalidArgumentException if $uid is invalid
     * @since 3.4.0
     */
    public function getTokenByUid(string $uid): GqlToken
    {
        $result = $this->_createTokenQuery()
            ->where(['uid' => $uid])
            ->one();

        if (!$result) {
            throw new InvalidArgumentException('Invalid UID');
        }

        return new GqlToken($result);
    }

    /**
     * Returns a GraphQL token by its access token.
     *
     * @param string $token
     * @return GqlToken
     * @throws InvalidArgumentException if $token is invalid
     * @since 3.4.0
     */
    public function getTokenByAccessToken(string $token): GqlToken
    {
        $result = $this->_createTokenQuery()
            ->where(['accessToken' => $token])
            ->one();

        if (!$result) {
            throw new InvalidArgumentException('Invalid access token');
        }

        return new GqlToken($result);
    }

    /**
     * Returns the public token. If it does not exist and admin changes are allowed, it will be created.
     *
     * @return GqlToken|null
     * @since 3.5.0
     */
    public function getPublicToken()
    {
        $result = $this->_createTokenQuery()
            ->where(['accessToken' => GqlToken::PUBLIC_TOKEN])
            ->one();

        // If we don't have it and admin changes aren't currently supported, return null
        if (
            (!$result || !$result['schemaId']) &&
            !Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            return null;
        }

        $token = $result ? new GqlToken($result) : new GqlToken([
            'name' => 'Public Token',
            'accessToken' => GqlToken::PUBLIC_TOKEN,
            'enabled' => true,
        ]);

        if (!$token->schemaId) {
            $schema = $this->_createPublicSchema();
            $token->setSchema($schema);

            if (!$this->saveToken($token)) {
                throw new Exception('Couldn’t save the public token.');
            }
        }

        return $token;
    }

    /**
     * Saves a GraphQL token.
     *
     * @param GqlToken $token the schema to save
     * @param bool $runValidation Whether the schema should be validated
     * @return bool Whether the schema was saved successfully
     * @throws Exception
     * @since 3.4.0
     */
    public function saveToken(GqlToken $token, $runValidation = true): bool
    {
        if ($token->isTemporary) {
            return false;
        }

        // Public token information is stored in the project config
        if ($token->accessToken === GqlToken::PUBLIC_TOKEN) {
            $data = [
                'expiryDate' => $token->expiryDate ? $token->expiryDate->getTimestamp() : null,
                'enabled' => (bool)$token->enabled
            ];

            Craft::$app->getProjectConfig()->set(self::CONFIG_GQL_PUBLIC_TOKEN_KEY, $data);

            return true;
        }

        if ($runValidation && !$token->validate()) {
            Craft::info('Token not saved due to validation error.', __METHOD__);
            return false;
        }

        $this->_saveTokenInternal($token);

        return true;
    }

    /**
     * Handle public token settings being updated.
     *
     * @param ConfigEvent $event
     *
     * @since 3.5.0
     */
    public function handleChangedPublicToken(ConfigEvent $event)
    {
        $data = $event->newValue;

        // If we're just adding a public schema, ensure it makes it in.
        ProjectConfigHelper::ensureAllGqlSchemasProcessed();

        try {
            $token = $this->getTokenByAccessToken(GqlToken::PUBLIC_TOKEN);
        } catch (InvalidArgumentException $exception) {
            $token = new GqlToken([
                'name' => 'Public Token',
                'accessToken' => GqlToken::PUBLIC_TOKEN,
            ]);
        }

        $publicSchema = $this->_createSchemaQuery()
            ->where(['isPublic' => true])
            ->one();

        $token->schemaId = $publicSchema ? $publicSchema['id'] : null;
        $token->expiryDate = $data['expiryDate'] ? DateTimeHelper::toDateTime($data['expiryDate']) : null;
        $token->enabled = $data['enabled'] ?: false;

        $this->_saveTokenInternal($token);
    }

    /**
     * Deletes a GraphQL token by its ID.
     *
     * @param int $id The schemas's ID
     * @return bool Whether the schema was deleted.
     * @since 3.4.0
     */
    public function deleteTokenById(int $id): bool
    {
        $record = GqlTokenRecord::findOne($id);

        if (!$record) {
            return true;
        }

        return $record->delete();
    }

    /**
     * Saves a GraphQL schema.
     *
     * @param GqlSchema $schema the schema to save
     * @param bool $runValidation Whether the schema should be validated
     * @return bool Whether the schema was saved successfully
     * @throws Exception
     * @since 3.4.0
     */
    public function saveSchema(GqlSchema $schema, $runValidation = true): bool
    {
        $isNewSchema = !$schema->id;

        if ($runValidation && !$schema->validate()) {
            Craft::info('Schema not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSchema && empty($schema->uid)) {
            $schema->uid = StringHelper::UUID();
        } else if (empty($schema->uid)) {
            $schema->uid = Db::uidById(Table::GQLSCHEMAS, $schema->id);
        }

        $configPath = self::CONFIG_GQL_SCHEMAS_KEY . '.' . $schema->uid;
        $configData = $schema->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save GraphQL schema “{$schema->name}”");

        if ($isNewSchema) {
            $schema->id = Db::idByUid(Table::GQLSCHEMAS, $schema->uid);
        }

        return true;
    }

    /**
     * Handle schema change
     *
     * @param ConfigEvent $event
     * @since 3.4.0
     */
    public function handleChangedSchema(ConfigEvent $event)
    {
        $schemaUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $schemaRecord = $this->_getSchemaRecord($schemaUid);
            $isNew = $schemaRecord->getIsNewRecord();

            $schemaRecord->uid = $schemaUid;
            $schemaRecord->name = $data['name'];
            $schemaRecord->isPublic = (bool)($data['isPublic'] ?? false);
            $schemaRecord->scope = (!empty($data['scope']) && is_array($data['scope'])) ? Json::encode((array)$data['scope']) : [];

            // Save the schema record
            $schemaRecord->save(false);

            // If we're updating to 3.4+, check if the old token info for this schema was cached
            if (
                $isNew &&
                ($allSchemas = Craft::$app->getCache()->get('migration:add_gql_project_config_support:schemas')) &&
                !empty($allSchemas[$schemaUid])
            ) {
                $migratedSchema = $allSchemas[$schemaUid];
                $token = new GqlToken([
                    'name' => $migratedSchema['name'],
                    'accessToken' => $migratedSchema['accessToken'],
                    'enabled' => $migratedSchema['enabled'],
                    'expiryDate' => $migratedSchema['expiryDate'],
                    'lastUsed' => $migratedSchema['lastUsed'],
                    'schemaId' => $schemaRecord->id,
                ]);
                $this->saveToken($token);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->invalidateCaches();
    }

    /**
     * Deletes a GraphQL schema by its ID.
     *
     * @param int $id The schema's ID
     * @return bool Whether the schema was deleted.
     * @since 3.4.0
     */
    public function deleteSchemaById(int $id): bool
    {
        $schema = $this->getSchemaById($id);

        if (!$schema) {
            return false;
        }

        return $this->deleteSchema($schema);
    }

    /**
     * Deletes a GraphQL schema.
     *
     * @param GqlSchema schema
     * @return bool
     * @since 3.4.0
     */
    public function deleteSchema(GqlSchema $schema): bool
    {
        Craft::$app->getProjectConfig()->remove(self::CONFIG_GQL_SCHEMAS_KEY . '.' . $schema->uid, "Delete the “{$schema->name}” GraphQL schema");
        return true;
    }


    /**
     * Handle schema getting deleted
     *
     * @param ConfigEvent $event
     * @since 3.4.0
     */
    public function handleDeletedSchema(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $schemaRecord = $this->_getSchemaRecord($uid);

        if ($schemaRecord->getIsNewRecord()) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Delete the schema
            Db::delete(Table::GQLSCHEMAS, [
                'id' => $schemaRecord->id,
            ]);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->invalidateCaches();
    }

    /**
     * Get a schema by its ID.
     *
     * @param int $id The schema's ID
     * @return GqlSchema|null
     */
    public function getSchemaById(int $id)
    {
        $result = $this->_createSchemaQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new GqlSchema($result) : null;
    }

    /**
     * Get a schema by its UID.
     *
     * @param string $uid The schema's UID
     * @return GqlSchema|null
     * @since 3.4.0
     */
    public function getSchemaByUid(string $uid)
    {
        $result = $this->_createSchemaQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new GqlSchema($result) : null;
    }

    /**
     * Get all schemas.
     *
     * @return GqlSchema[]
     * @since 3.4.0
     */
    public function getSchemas(): array
    {
        $rows = $this->_createSchemaQuery()
            ->all();

        $schemas = [];

        foreach ($rows as $row) {
            $schemas[] = new GqlSchema($row);
        }

        return $schemas;
    }


    /**
     * Return the content arguments based on an element class and contexts for it.
     *
     * @param FieldLayoutBehavior[] $contexts
     * @param string $elementClass
     * @return array
     */
    public function getContentArguments(array $contexts, $elementClass): array
    {
        if (!array_key_exists($elementClass, $this->_contentFieldCache)) {
            $contentArguments = [];

            foreach ($contexts as $context) {
                if (!GqlHelper::isSchemaAwareOf($elementClass::gqlScopesByContext($context))) {
                    continue;
                }

                foreach ($context->getFields() as $contentField) {
                    if (!$contentField instanceof GqlInlineFragmentFieldInterface) {
                        $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
                    }
                }
            }

            $this->_contentFieldCache[$elementClass] = $contentArguments;
        }

        return $this->_contentFieldCache[$elementClass];
    }

    /**
     * Custom error handler for GraphQL query errors
     *
     * @param Error[] $errors
     * @param callable $formatter
     * @return Error[]
     * @since 3.6.2
     */
    public function handleQueryErrors(array $errors, callable $formatter)
    {
        $devMode = Craft::$app->getConfig()->getGeneral()->devMode;

        /** @var Error $error */
        foreach ($errors as &$error) {
            $originException = $nextException = $error;

            // Get the origin exception.
            while ($nextException = $nextException->getPrevious()) {
                $originException = $nextException;
            }

            // If devMode enabled, substitute the original exception here.
            if ($devMode) {
                $error = $originException;
            }

            // Otherwise, just log it.
            Craft::$app->getErrorHandler()->logException($originException);
        }

        return array_map($formatter, $errors);
    }

    /**
     * Generate a cache key for the GraphQL operation. Returns null if caching is disabled or unable to generate one.
     *
     * @param GqlSchema $schema
     * @param string $query
     * @param mixed $rootValue
     * @param mixed $context
     * @param array|null $variables
     * @param string|null $operationName
     *
     * @return string|null
     */
    private function _getCacheKey(
        GqlSchema $schema,
        string $query,
        $rootValue,
        $context,
        array $variables = null,
        string $operationName = null
    ) {
        // No cache key, if explicitly disabled
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$generalConfig->enableGraphQlCaching) {
            return null;
        }

        // Do not cache mutations
        if (preg_match('/^\s*mutation(?P<operationName>\s+\w+)?\s*(?P<variables>\(.*\))?\s*{/si', $query)) {
            return null;
        }

        // No cache key if we have placeholder elements
        if (!empty(Craft::$app->getElements()->getPlaceholderElements())) {
            return null;
        }

        try {
            $cacheKey = self::CACHE_TAG .
                '::' . Craft::$app->getSites()->getCurrentSite()->id .
                '::' . $schema->uid .
                '::' . md5($query) .
                '::' . serialize($rootValue) .
                '::' . serialize($context) .
                '::' . serialize($variables) .
                ($operationName ? "::$operationName" : '');
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $cacheKey = null;
        }

        return $cacheKey;
    }

    /**
     * Register GraphQL types
     *
     * @return array the list of registered types.
     */
    private function _registerGqlTypes(): array
    {
        $typeList = [
            // Scalars
            DateTime::class,
            Number::class,
            QueryArgument::class,

            // Interfaces
            ElementInterface::class,
            EntryInterface::class,
            MatrixBlockInterface::class,
            AssetInterface::class,
            UserInterface::class,
            GlobalSetInterface::class,
            CategoryInterface::class,
            TagInterface::class,
        ];

        $event = new RegisterGqlTypesEvent([
            'types' => $typeList,
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_TYPES, $event);

        foreach ($event->types as $type) {
            /** @var InterfaceType $type */
            TypeLoader::registerType($type::getName(), $type . '::getType');
        }

        return $event->types;
    }

    /**
     * Get GraphQL query definitions
     */
    private function _registerGqlQueries()
    {
        $queryList = [
            // Queries
            PingQuery::getQueries(),
            EntryQuery::getQueries(),
            AssetQuery::getQueries(),
            UserQuery::getQueries(),
            GlobalSetQuery::getQueries(),
            CategoryQuery::getQueries(),
            TagQuery::getQueries(),
        ];


        $event = new RegisterGqlQueriesEvent([
            'queries' => array_merge(...$queryList)
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_QUERIES, $event);

        TypeLoader::registerType('Query', function() use ($event) {
            return call_user_func(Query::class . '::getType', $event->queries);
        });
    }

    /**
     * Get GraphQL mutation definitions
     */
    private function _registerGqlMutations()
    {
        $mutationList = [
            // Mutations
            PingMutation::getMutations(),
            EntryMutation::getMutations(),
            TagMutation::getMutations(),
            CategoryMutation::getMutations(),
            GlobalSetMutation::getMutations(),
            AssetMutation::getMutations(),
        ];


        $event = new RegisterGqlMutationsEvent([
            'mutations' => array_merge(...$mutationList)
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_MUTATIONS, $event);

        TypeLoader::registerType('Mutation', function() use ($event) {
            return call_user_func(Mutation::class . '::getType', $event->mutations);
        });
    }

    /**
     * Get GraphQL query definitions
     *
     * @return Directive[]
     */
    private function _loadGqlDirectives(): array
    {
        $directiveClasses = [
            // Directives
            FormatDateTime::class,
            Markdown::class,
            ParseRefs::class,
        ];

        if (!Craft::$app->getConfig()->getGeneral()->disableGraphqlTransformDirective) {
            $directiveClasses[] = Transform::class;
        }

        $event = new RegisterGqlDirectivesEvent([
            'directives' => $directiveClasses
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_DIRECTIVES, $event);

        $directives = GraphQL::getStandardDirectives();

        foreach ($event->directives as $directive) {
            /** @var Directive $directive */
            $directives[] = $directive::create();
        }

        return $directives;
    }

    /**
     * Return section permissions.
     *
     * @return array
     */
    private function _getSectionSchemaComponents(): array
    {
        $sortedEntryTypes = [];

        foreach (Craft::$app->getSections()->getAllEntryTypes() as $entryType) {
            $sortedEntryTypes[$entryType->sectionId][] = $entryType;
        }

        $queryComponents = [];
        $mutationComponents = [];

        if (!empty($sortedEntryTypes)) {

            foreach (Craft::$app->getSections()->getAllSections() as $section) {
                $query = ['label' => Craft::t('app', 'Section - {section}', ['section' => Craft::t('site', $section->name)])];
                $mutate = ['label' => Craft::t('app', 'Section - {section}', ['section' => Craft::t('site', $section->name)])];

                foreach ($sortedEntryTypes[$section->id] as $entryType) {
                    $suffix = 'entrytypes.' . $entryType->uid;

                    if ($section->type == Section::TYPE_SINGLE) {
                        $mutate['nested'][$suffix . ':save'] = ['label' => Craft::t('app', 'Edit “{entryType}”', ['entryType' => Craft::t('site', $entryType->name)])];
                    } else {
                        $mutate['nested'][$suffix . ':edit'] = [
                            'label' => Craft::t('app', 'Edit entries with the “{entryType}” entry type', ['entryType' => Craft::t('site', $entryType->name)]),
                            'nested' => [
                                $suffix . ':create' => ['label' => Craft::t('app', 'Create entries with the “{entryType}” entry type', ['entryType' => Craft::t('site', $entryType->name)])],
                                $suffix . ':save' => ['label' => Craft::t('app', 'Save entries with the “{entryType}” entry type', ['entryType' => Craft::t('site', $entryType->name)])],
                                $suffix . ':delete' => ['label' => Craft::t('app', 'Delete entries with the “{entryType}” entry type', ['entryType' => Craft::t('site', $entryType->name)])],
                            ],
                        ];
                    }

                    $query['nested'][$suffix . ':read'] = [
                        'label' => Craft::t('app', 'View entries with the “{entryType}” entry type', ['entryType' => Craft::t('site', $entryType->name)]),
                    ];
                }

                $queryComponents['sections.' . $section->uid . ':read'] = $query;
                $mutationComponents['sections.' . $section->uid . ':edit'] = $mutate;
            }
        }

        return [
            'query' => $queryComponents,
            'mutation' => $mutationComponents,
        ];
    }

    /**
     * Return volume permissions.
     *
     * @return array
     */
    private function _getVolumeSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!empty($volumes)) {
            foreach ($volumes as $volume) {
                $suffix = 'volumes.' . $volume->uid;
                $queryComponents[$suffix . ':read'] = ['label' => Craft::t('app', 'View volume - {volume}', ['volume' => Craft::t('site', $volume->name)])];
                $mutationComponents[$suffix . ':edit'] = [
                    'label' => Craft::t('app', 'Edit assets in the “{volume}” volume', ['volume' => Craft::t('site', $volume->name)]),
                    'nested' => [
                        $suffix . ':create' => ['label' => Craft::t('app', 'Create assets in the “{volume}” volume', ['volume' => Craft::t('site', $volume->name)])],
                        $suffix . ':save' => ['label' => Craft::t('app', 'Modify assets in the “{volume}” volume', ['volume' => Craft::t('site', $volume->name)])],
                        $suffix . ':delete' => ['label' => Craft::t('app', 'Delete assets from the “{volume}” volume', ['volume' => Craft::t('site', $volume->name)])],
                    ]
                ];
            }
        }

        return [
            'query' => $queryComponents,
            'mutation' => $mutationComponents,
        ];
    }

    /**
     * Return global set permissions.
     *
     * @return array
     */
    private function _getGlobalSetSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!empty($globalSets)) {
            foreach ($globalSets as $globalSet) {
                $suffix = 'globalsets.' . $globalSet->uid;
                $queryComponents[$suffix . ':read'] = ['label' => Craft::t('app', 'View global set - {globalSet}', ['globalSet' => Craft::t('site', $globalSet->name)])];
                $mutationComponents[$suffix . ':edit'] = ['label' => Craft::t('app', 'Edit the “{globalSet}” global set.', ['globalSet' => Craft::t('site', $globalSet->name)])];
            }
        }

        return [
            'query' => $queryComponents,
            'mutation' => $mutationComponents,
        ];
    }

    /**
     * Return category group permissions.
     *
     * @return array
     */
    private function _getCategorySchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($categoryGroups)) {
            foreach ($categoryGroups as $categoryGroup) {
                $suffix = 'categorygroups.' . $categoryGroup->uid;
                $queryComponents[$suffix . ':read'] = ['label' => Craft::t('app', 'View category group - {categoryGroup}', ['categoryGroup' => Craft::t('site', $categoryGroup->name)])];
                $mutationComponents[$suffix . ':edit'] = [
                    'label' => Craft::t('app', 'Edit categories in the “{categoryGroup}” category group', ['categoryGroup' => Craft::t('site', $categoryGroup->name)]),
                    'nested' => [
                        $suffix . ':save' => ['label' => Craft::t('app', 'Save categories in the “{categoryGroup}” category group', ['categoryGroup' => Craft::t('site', $categoryGroup->name)])],
                        $suffix . ':delete' => ['label' => Craft::t('app', 'Delete categories from the “{categoryGroup}” category group', ['categoryGroup' => Craft::t('site', $categoryGroup->name)])],
                    ]
                ];
            }
        }

        return [
            'query' => $queryComponents,
            'mutation' => $mutationComponents,
        ];
    }

    /**
     * Return tag group permissions.
     *
     * @return array
     */
    private function _getTagSchemaComponents(): array
    {
        $queryComponents = [];
        $mutationComponents = [];

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($tagGroups)) {
            foreach ($tagGroups as $tagGroup) {
                $suffix = 'taggroups.' . $tagGroup->uid;
                $queryComponents[$suffix . ':read'] = ['label' => Craft::t('app', 'View tag group - {tagGroup}', ['tagGroup' => Craft::t('site', $tagGroup->name)])];
                $mutationComponents[$suffix . ':edit'] = [
                    'label' => Craft::t('app', 'Edit tags in the “{tagGroup}” tag group', ['tagGroup' => Craft::t('site', $tagGroup->name)]),
                    'nested' => [
                        $suffix . ':save' => ['label' => Craft::t('app', 'Save tags in the “{tagGroup}” tag group', ['tagGroup' => Craft::t('site', $tagGroup->name)])],
                        $suffix . ':delete' => ['label' => Craft::t('app', 'Delete tags from the “{tagGroup}” tag group', ['tagGroup' => Craft::t('site', $tagGroup->name)])],
                    ]
                ];
            }
        }

        return [
            'query' => $queryComponents,
            'mutation' => $mutationComponents,
        ];
    }

    /**
     * Return user permissions.
     *
     * @return array
     */
    private function _getUserSchemaComponents(): array
    {
        $queryComponents = [];
        $userGroups = Craft::$app->getUserGroups()->getAllGroups();

        $queryComponents['usergroups.everyone:read'] = ['label' => Craft::t('app', 'View all users')];

        foreach ($userGroups as $userGroup) {
            $suffix = 'usergroups.' . $userGroup->uid;
            $queryComponents[$suffix . ':read'] = ['label' => Craft::t('app', 'View user group - {userGroup}', ['userGroup' => Craft::t('site', $userGroup->name)])];
        }

        return [
            'query' => $queryComponents,
        ];
    }

    /**
     * Returns a DbCommand object prepped for retrieving tokens.
     *
     * @return DbQuery
     */
    private function _createTokenQuery(): DbQuery
    {
        $query = (new DbQuery())
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
            ])
            ->from([Table::GQLTOKENS]);

        return $query;
    }

    /**
     * Returns a DbCommand object prepped for retrieving schemas.
     *
     * @return DbQuery
     */
    private function _createSchemaQuery(): DbQuery
    {
        $query = (new DbQuery())
            ->select([
                'id',
                'name',
                'scope',
                'isPublic',
                'uid',
            ])
            ->from([Table::GQLSCHEMAS]);

        return $query;
    }

    /**
     * Creates the public schema.
     *
     * @return GqlSchema
     */
    private function _createPublicSchema(): GqlSchema
    {
        // See if it already exists, and is just missing its token
        $result = $this->_createSchemaQuery()->where(['isPublic' => true])->one();

        if ($result) {
            $schema = new GqlSchema($result);
        } else {
            $schemaUid = StringHelper::UUID();
            $schema = new GqlSchema([
                'name' => 'Public Schema',
                'uid' => $schemaUid,
                'isPublic' => true,
            ]);
        }

        $this->saveSchema($schema, false);
        return $schema;
    }

    /**
     * Gets a schema's record by uid.
     *
     * @param string $uid
     * @return GqlSchemaRecord
     */
    private function _getSchemaRecord(string $uid): GqlSchemaRecord
    {
        return GqlSchemaRecord::findOne(['uid' => $uid]) ?? new GqlSchemaRecord();
    }
}
