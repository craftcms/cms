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
use craft\events\RegisterGqlPermissionsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\base\Directive;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\InterfaceType;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Markdown;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\interfaces\elements\User as UserInterface;
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
use craft\gql\types\Number;
use craft\gql\types\Query;
use craft\gql\types\QueryArgument;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\records\GqlSchema as GqlSchemaRecord;
use craft\records\GqlToken as GqlTokenRecord;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\FieldsOnCorrectType;
use GraphQL\Validator\Rules\KnownTypeNames;
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
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlTypeEvent;
     * use craft\services\GraphQl;
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
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlQueriesEvent;
     * use craft\services\GraphQl;
     * use yii\base\Event;
     * use GraphQL\Type\Definition\Type;
     *
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
     *     // Add my GraphQL queries
     *     $event->queries['queryPluginData'] =
     *     [
     *         'type' => Type::listOf(MyType::getType())),
     *         'args' => MyArguments::getArguments(),
     *         'resolve' => MyResolver::class . '::resolve'
     *     ];
     * });
     * ```
     */
    const EVENT_REGISTER_GQL_QUERIES = 'registerGqlQueries';

    /**
     * @event RegisterGqlDirectivesEvent The event that is triggered when registering GraphQL directives.
     *
     * Plugins get a chance to add their own GraphQL directives.
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlDirectivesEvent;
     * use craft\services\GraphQl;
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
     */
    const EVENT_REGISTER_GQL_PERMISSIONS = 'registerGqlPermissions';

    /**
     * @event DefineGqlValidationRulesEvent The event that is triggered when defining validation rules to be used.
     *
     * Plugins get a chance to alter the GraphQL validation rule list.
     *
     * ---
     * ```php
     * use craft\events\DefineGqlValidationRulesEvent;
     * use craft\services\GraphQl;
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
     * use craft\services\GraphQl;
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
     * use craft\services\GraphQl;
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
     * @since 3.4.0
     */
    const CONFIG_GQL_SCHEMAS_KEY = 'graphql.schemas';

    /**
     * The field name to use when fetching count of related elements
     *
     * @since 3.4.0
     */
    const GRAPHQL_COUNT_FIELD = '_count';

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

            $schemaConfig = [
                'typeLoader' => TypeLoader::class . '::loadType',
                'query' => TypeLoader::loadType('Query'),
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
     * @return array
     */
    public function getValidationRules($debug = false)
    {
        $validationRules = DocumentValidator::defaultRules();

        if (!$debug) {
            // Remove the rules which would generate a full schema just for a nice message, to avoid performance hit.
            unset(
                $validationRules[KnownTypeNames::class],
                $validationRules[FieldsOnCorrectType::class]
            );
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
    public function executeQuery(GqlSchema $schema, string $query, $variables = [], $operationName = '', $debugMode = false): array
    {
        $event = new ExecuteGqlQueryEvent([
            'schemaId' => $schema->id,
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
        ]);

        $this->trigger(self::EVENT_BEFORE_EXECUTE_GQL_QUERY, $event);

        if ($event->result === null) {
            $cacheKey = $this->_getCacheKey($schema, $query, $event->rootValue, $event->context, $event->variables, $event->operationName);

            if ($cacheKey && ($cachedResult = $this->getCachedResult($cacheKey))) {
                $event->result = $cachedResult;
            } else {
                $schemaDef = $this->getSchemaDef($schema, $debugMode || StringHelper::contains($query, '__schema'));
                $event->result = GraphQL::executeQuery($schemaDef, $query, $event->rootValue, $event->context, $event->variables, $event->operationName, null, $this->getValidationRules($debugMode))->toArray(true);

                if (empty($event->result['errors']) && $cacheKey) {
                    $this->setCachedResult($cacheKey, $event->result);
                }
            }
        }

        $this->trigger(self::EVENT_AFTER_EXECUTE_GQL_QUERY, $event);

        return $event->result ?? [];
    }

    /**
     * Invalidate all GraphQL result caches.
     *
     * @since 3.3.12
     */
    public function invalidateCaches()
    {
        TagDependency::invalidate(Craft::$app->getCache(), self::CACHE_TAG);
    }

    /**
     * Get the cached result for a key.
     *
     * @param $cacheKey
     * @return mixed
     * @since 3.3.12
     */
    public function getCachedResult($cacheKey)
    {
        return Craft::$app->getCache()->get($cacheKey);
    }

    /**
     * Cache a result for the key and tag it.
     *
     * @param $cacheKey
     * @param $result
     * @since 3.3.12
     */
    public function setCachedResult($cacheKey, $result)
    {
        Craft::$app->getCache()->set($cacheKey, $result, null, new TagDependency(['tags' => self::CACHE_TAG]));
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
        $result = $this->_createTokenQuery()
            ->where(['accessToken' => GqlToken::PUBLIC_TOKEN])
            ->one();

        if ($result && $result['schemaId']) {
            return (new GqlToken($result))->getSchema();
        }

        // If admin changes aren't currently supported, return null
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return null;
        }

        return $this->_createPublicSchema($result ? $result['id'] : null);
    }

    /**
     * Returns all of the known GraphQL permissions, sorted by category.
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $permissions = [];

        // Entries
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getSectionPermissions());

        // Assets
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getVolumePermissions());

        // Global Sets
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getGlobalSetPermissions());

        // Users
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getUserPermissions());

        // Categories
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getCategoryPermissions());

        // Tags
        // ---------------------------------------------------------------------
        $permissions = array_merge($permissions, $this->_getTagPermissions());

        // Let plugins customize them and add new ones
        // ---------------------------------------------------------------------

        $event = new RegisterGqlPermissionsEvent([
            'permissions' => $permissions
        ]);
        $this->trigger(self::EVENT_REGISTER_GQL_PERMISSIONS, $event);

        return $event->permissions;
    }

    /**
     * Flush all GraphQL caches, registries and loaders.
     *
     * @return void
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

        $isNewToken = !$token->id;

        if ($runValidation && !$token->validate()) {
            Craft::info('Schema not saved due to validation error.', __METHOD__);
            return false;
        }

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

        return true;
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
     * Saves a GraphQL scope.
     *
     * @param GqlSchema $schema the schema to save
     * @param bool $runValidation Whether the scope should be validated
     * @return bool Whether the scope was saved successfully
     * @throws Exception
     * @since 3.4.0
     */
    public function saveSchema(GqlSchema $schema, $runValidation = true): bool
    {
        $isNewScope = !$schema->id;

        if ($runValidation && !$schema->validate()) {
            Craft::info('Scope not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewScope && empty($schema->uid)) {
            $schema->uid = StringHelper::UUID();
        } else if (empty($schema->uid)) {
            $schema->uid = Db::uidById(Table::GQLSCHEMAS, $schema->id);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $configData = [
            'name' => $schema->name,
            'scope' => $schema->scope,
            'isPublic' => $schema->isPublic
        ];

        $configPath = self::CONFIG_GQL_SCHEMAS_KEY . '.' . $schema->uid;
        $projectConfig->set($configPath, $configData, "Save GraphQL schema “{$schema->name}”");

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
    public function deleteSchema(GqlSchema $scope): bool
    {
        Craft::$app->getProjectConfig()->remove(self::CONFIG_GQL_SCHEMAS_KEY . '.' . $scope->uid, "Delete the “{$scope->name}” GraphQL schema");
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
            // Delete the scope
            $db->createCommand()
                ->delete(Table::GQLSCHEMAS, ['id' => $schemaRecord->id])
                ->execute();

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

        $scopes = [];

        foreach ($rows as $row) {
            $scopes[] = new GqlSchema($row);
        }

        return $scopes;
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
                        $contentArguments[$contentField->handle] = [
                            'name' => $contentField->handle,
                            'type' => Type::listOf(QueryArgument::getType()),
                        ];
                    }
                }
            }

            $this->_contentFieldCache[$elementClass] = $contentArguments;
        }

        return $this->_contentFieldCache[$elementClass];
    }

    /**
     * Generate a cache key for the GraphQL operation. Returns null if caching is disabled or unable to generate one.
     *
     * @param GqlSchema $schema
     * @param string $query
     * @param $rootValue
     * @param $context
     * @param $variables
     * @param $operationName
     *
     * @return string|null
     */
    private function _getCacheKey(GqlSchema $schema, string $query, $rootValue, $context, $variables, $operationName)
    {
        // No cache key, if explicitly disabled
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if (!$generalConfig->enableGraphQlCaching) {
            return null;
        }

        // No cache key if we have placeholder elements
        if (!empty(Craft::$app->getElements()->getPlaceholderElements())) {
            return null;
        }

        try {
            $cacheKey = 'gql.results.' . sha1($schema->uid . $query . serialize($rootValue) . serialize($context) . serialize($variables) . serialize($operationName));
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
     *
     * @return void
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
            Transform::class,
        ];

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
    private function _getSectionPermissions(): array
    {
        $permissions = [];

        $sortedEntryTypes = [];

        foreach (Craft::$app->getSections()->getAllEntryTypes() as $entryType) {
            $sortedEntryTypes[$entryType->sectionId][] = $entryType;
        }

        if (!empty($sortedEntryTypes)) {
            $label = Craft::t('app', 'Entries');

            $sectionPermissions = [];

            foreach (Craft::$app->getSections()->getAllSections() as $section) {
                $nested = ['label' => Craft::t('app', 'View section - {section}', ['section' => Craft::t('site', $section->name)])];

                foreach ($sortedEntryTypes[$section->id] as $entryType) {
                    $nested['nested']['entrytypes.' . $entryType->uid . ':read'] = ['label' => Craft::t('app', 'View entry type - {entryType}', ['entryType' => Craft::t('site', $entryType->name)])];
                }

                $sectionPermissions['sections.' . $section->uid . ':read'] = $nested;
            }

            $permissions[$label] = $sectionPermissions;
        }

        return $permissions;
    }

    /**
     * Return volume permissions.
     *
     * @return array
     */
    private function _getVolumePermissions(): array
    {
        $permissions = [];

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        if (!empty($volumes)) {
            $label = Craft::t('app', 'Assets');
            $volumePermissions = [];

            foreach ($volumes as $volume) {
                $volumePermissions['volumes.' . $volume->uid . ':read'] = ['label' => Craft::t('app', 'View volume - {volume}', ['volume' => Craft::t('site', $volume->name)])];
            }

            $permissions[$label] = $volumePermissions;
        }

        return $permissions;
    }

    /**
     * Return global set permissions.
     *
     * @return array
     */
    private function _getGlobalSetPermissions(): array
    {
        $permissions = [];

        $globalSets = Craft::$app->getGlobals()->getAllSets();

        if (!empty($globalSets)) {
            $label = Craft::t('app', 'Globals');
            $globalSetPermissions = [];

            foreach ($globalSets as $globalSet) {
                $suffix = 'globalsets.' . $globalSet->uid;
                $globalSetPermissions[$suffix . ':read'] = ['label' => Craft::t('app', 'View global set - {globalSet}', ['globalSet' => Craft::t('site', $globalSet->name)])];
            }

            $permissions[$label] = $globalSetPermissions;
        }

        return $permissions;
    }

    /**
     * Return category group permissions.
     *
     * @return array
     */
    private function _getCategoryPermissions(): array
    {
        $permissions = [];

        $categoryGroups = Craft::$app->getCategories()->getAllGroups();

        if (!empty($categoryGroups)) {
            $label = Craft::t('app', 'Categories');
            $categoryPermissions = [];

            foreach ($categoryGroups as $categoryGroup) {
                $suffix = 'categorygroups.' . $categoryGroup->uid;
                $categoryPermissions[$suffix . ':read'] = ['label' => Craft::t('app', 'View category group - {categoryGroup}', ['categoryGroup' => Craft::t('site', $categoryGroup->name)])];
            }

            $permissions[$label] = $categoryPermissions;
        }

        return $permissions;
    }

    /**
     * Return tag group permissions.
     *
     * @return array
     */
    private function _getTagPermissions(): array
    {
        $permissions = [];

        $tagGroups = Craft::$app->getTags()->getAllTagGroups();

        if (!empty($tagGroups)) {
            $label = Craft::t('app', 'Tags');
            $tagPermissions = [];

            foreach ($tagGroups as $tagGroup) {
                $suffix = 'taggroups.' . $tagGroup->uid;
                $tagPermissions[$suffix . ':read'] = ['label' => Craft::t('app', 'View tag group - {tagGroup}', ['tagGroup' => Craft::t('site', $tagGroup->name)])];
            }

            $permissions[$label] = $tagPermissions;
        }

        return $permissions;
    }

    /**
     * Return user permissions.
     *
     * @return array
     */
    private function _getUserPermissions(): array
    {
        $permissions = [];

        $userGroups = Craft::$app->getUserGroups()->getAllGroups();

        $label = Craft::t('app', 'Users');

        $userPermissions = ['usergroups.everyone:read' => ['label' => Craft::t('app', 'View all users')]];

        foreach ($userGroups as $userGroup) {
            $suffix = 'usergroups.' . $userGroup->uid;
            $userPermissions[$suffix . ':read'] = ['label' => Craft::t('app', 'View user group - {userGroup}', ['userGroup' => Craft::t('site', $userGroup->name)])];
        }

        $permissions[$label] = $userPermissions;

        return $permissions;
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
     * @param int $tokenId Token id, if one exists already for the public schema
     * @return GqlSchema
     * @throws Exception if the schema couldn't be created.
     */
    private function _createPublicSchema(int $tokenId = null): GqlSchema
    {
        $existingSchema = $this->_createSchemaQuery()->where(['isPublic' => true])->one();

        if ($existingSchema) {
            $schema = new GqlSchema($existingSchema);
        } else {
            $schemaUid = StringHelper::UUID();
            $schema = new GqlSchema([
                'name' => 'Public Schema',
                'uid' => $schemaUid,
                'isPublic' => true,
            ]);
        }

        $this->saveSchema($schema);

        $token = $tokenId ? $this->getTokenById($tokenId) : new GqlToken([
            'name' => 'Public Token',
            'accessToken' => GqlToken::PUBLIC_TOKEN,
            'enabled' => true,
        ]);

        $token->schemaId = $existingSchema ? $schema->id : Db::idByUid(Table::GQLSCHEMAS, $schemaUid);

        if (!$this->saveToken($token)) {
            throw new Exception('Couldn’t create public schema.');
        }

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
