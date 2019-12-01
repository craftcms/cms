<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query as DbQuery;
use craft\db\Table;
use craft\errors\GqlException;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
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
use craft\gql\types\DateTime;
use craft\gql\types\Query;
use craft\gql\types\QueryArgument;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use craft\records\GqlSchema as GqlSchemaRecord;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
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
    // Constants
    // =========================================================================

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
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlQueriesEvent $event) {
     *     // Add my GraphQL queries
     *     $even->queries['queryPluginData'] =
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
     *     Gql::EVENT_REGISTER_GQL_TYPES,
     *     function(RegisterGqlModelEvent $event) {
     *         $event->directives[] = MyDirective::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_GQL_DIRECTIVES = 'registerGqlDirectives';

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
     * Plugins get a chance to do sometheing after a performed GraphQL query.
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
     * @var Schema Currently loaded schema definition
     */
    private $_schemaDef;

    /**
     * @var GqlSchema The active GraphQL schema
     * @see setActiveSchema()
     */
    private $_schema;

    // Public Methods
    // =========================================================================

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
     * Execute a GraphQL query for a given active schema.
     *
     * @param GqlSchema $schema The active schema to use.
     * @param string $query The query string to execute.
     * @param array|null $variables The variables to use.
     * @param string|null $operationName The operation name.
     * @return array
     * @since 3.3.11
     */
    public function executeQuery(GqlSchema $schema, string $query, $variables = [], $operationName = ''): array
    {
        $event = new ExecuteGqlQueryEvent([
            'accessToken' => $schema->accessToken,
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
        ]);

        $this->trigger(self::EVENT_BEFORE_EXECUTE_GQL_QUERY, $event);

        if ($event->result === null) {
            $cacheKey = $this->_getCacheKey($schema, $query, $event->rootValue, $event->context, $variables, $operationName);

            if ($cacheKey && ($cachedResult = $this->getCachedResult($cacheKey))) {
                $event->result = $cachedResult;
            } else {
                $schemaDef = $this->getSchemaDef($schema, StringHelper::contains($query, '__schema'));
                $event->result = GraphQL::executeQuery($schemaDef, $query, $event->rootValue, $event->context, $event->variables, $event->operationName)->toArray(true);

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

        if ($schema) {
            $schema->lastUsed = DateTimeHelper::currentUTCDateTime();
            $this->saveSchema($schema, true, false);
        }
    }

    /**
     * Returns all GraphQL schemas.
     *
     * @return GqlSchema[]
     */
    public function getSchemas(): array
    {
        $rows = $this->_createSchemaQuery()->all();
        $schemas = [];
        $names = [];

        $publicSchema = null;

        foreach ($rows as $row) {
            $schema = new GqlSchema($row);
            if ($schema->getIsPublic()) {
                $publicSchema = $schema;
            } else {
                $schemas[] = $schema;
                $names[] = $schema->name;
            }
        }

        // Sort them by name
        array_multisort($names, SORT_ASC, SORT_STRING, $schemas);

        // Add the public schema to the top
        array_unshift($schemas, $publicSchema ?? $this->_createPublicSchema());

        return $schemas;
    }

    /**
     * Returns the public schema. If it does not exist, it will be created.
     *
     * @return GqlSchema
     * @throws Exception
     */
    public function getPublicSchema(): GqlSchema
    {
        $result = $this->_createSchemaQuery()
            ->where(['accessToken' => GqlSchema::PUBLIC_TOKEN])
            ->one();

        if ($result) {
            return new GqlSchema($result);
        }

        return $this->_createPublicSchema();
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

        return $permissions;
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
        TypeLoader::flush();
        GqlEntityRegistry::flush();
        $this->invalidateCaches();
    }

    /**
     * Returns a GraphQL schema by its id.
     *
     * @param int $id
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
     * Returns a GraphQL schema by its UID.
     *
     * @param string $uid
     * @return GqlSchema
     * @throws InvalidArgumentException if $uid is invalid
     */
    public function getSchemaByUid(string $uid): GqlSchema
    {
        $result = $this->_createSchemaQuery()
            ->where(['uid' => $uid])
            ->one();

        if (!$result) {
            throw new InvalidArgumentException('Invalid UID');
        }

        return new GqlSchema($result);
    }

    /**
     * Returns a GraphQL schema by its access token.
     *
     * @param string $token
     * @return GqlSchema
     * @throws InvalidArgumentException if $token is invalid
     */
    public function getSchemaByAccessToken(string $token): GqlSchema
    {
        if ($token == GqlSchema::PUBLIC_TOKEN) {
            return $this->getPublicSchema();
        }

        $result = $this->_createSchemaQuery()
            ->where(['accessToken' => $token])
            ->one();

        if (!$result) {
            throw new InvalidArgumentException('Invalid access token');
        }

        return new GqlSchema($result);
    }

    /**
     * Saves a GraphQL schema.
     *
     * @param GqlSchema $schema the schema to save
     * @param bool $runValidation Whether the schema should be validated
     * @param bool $invalidateCaches Whether the cached results should be invalidated
     * @return bool Whether the schema was saved successfully
     * @throws Exception
     */
    public function saveSchema(GqlSchema $schema, $runValidation = true, $invalidateCaches = true): bool
    {
        if ($schema->isTemporary) {
            return false;
        }

        $isNewSchema = !$schema->id;

        if ($runValidation && !$schema->validate()) {
            Craft::info('Schema not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSchema) {
            $schemaRecord = new GqlSchemaRecord();
        } else {
            $schemaRecord = GqlSchemaRecord::findOne($schema->id) ?: new GqlSchemaRecord();
        }

        $schemaRecord->name = $schema->name;
        $schemaRecord->enabled = (bool)$schema->enabled;
        $schemaRecord->expiryDate = $schema->expiryDate;
        $schemaRecord->lastUsed = $schema->lastUsed;
        $schemaRecord->scope = $schema->scope;

        if ($schema->accessToken) {
            $schemaRecord->accessToken = $schema->accessToken;
        }

        $schemaRecord->save();
        $schema->id = $schemaRecord->id;
        $schema->uid = $schemaRecord->uid;

        if ($invalidateCaches) {
            $this->invalidateCaches();
        }

        return true;
    }

    /**
     * Deletes a GraphQL schema by its ID.
     *
     * @param int $id The transform's ID
     * @return bool Whether the token was deleted.
     */
    public function deleteSchemaById(int $id): bool
    {
        $record = GqlSchemaRecord::findOne($id);

        if (!$record) {
            return true;
        }

        return $record->delete();
    }

    // Private Methods
    // =========================================================================

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
            $cacheKey = 'gql.results.' . sha1($schema->accessToken . $query . serialize($rootValue) . serialize($context) . serialize($variables) . serialize($operationName));
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
     * Returns a DbCommand object prepped for retrieving volumes.
     *
     * @return DbQuery
     */
    private function _createSchemaQuery(): DbQuery
    {
        $query = (new DbQuery())
            ->select([
                'id',
                'name',
                'accessToken',
                'enabled',
                'expiryDate',
                'lastUsed',
                'scope',
                'dateCreated',
                'uid',
            ])
            ->from([Table::GQLSCHEMAS]);

        return $query;
    }

    /**
     * Creates the public schema.
     *
     * @return GqlSchema
     * @throws Exception if the schema couldn't be created.
     */
    private function _createPublicSchema(): GqlSchema
    {
        $schema = new GqlSchema([
            'name' => 'Public Schema',
            'accessToken' => GqlSchema::PUBLIC_TOKEN,
            'enabled' => true,
        ]);
        if (!$this->saveSchema($schema)) {
            throw new Exception('Couldn’t create public schema.');
        }
        return $schema;
    }
}
