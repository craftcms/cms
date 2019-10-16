<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Table;
use craft\db\Query as DbQuery;
use craft\errors\GqlException;
use craft\events\ExecuteGqlQueryEvent;
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\base\Directive;
use craft\gql\base\GeneratorInterface;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Markdown;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\base\InterfaceType;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\interfaces\elements\Tag as TagInterface;
use craft\gql\queries\Asset as AssetQuery;
use craft\gql\queries\Category as CategoryQuery;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\Ping as PingQuery;
use craft\gql\queries\User as UserQuery;
use craft\gql\queries\Tag as TagQuery;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\Query;
use craft\helpers\DateTimeHelper;
use craft\models\GqlSchema;
use craft\records\GqlSchema as GqlSchemaRecord;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

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
     */
    const EVENT_AFTER_EXECUTE_GQL_QUERY = 'afterExecuteGqlQuery';

    /**
     * Currently loaded schema definition
     *
     * @var Schema
     */
    private $_schemaDef;

    /**
     * The active GraphQL schema
     *
     * @var GqlSchema
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
            $this->_registerGqlTypes();
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

            // Create a pre-built schema if that's what they want.
            $interfaces = [
                EntryInterface::class,
                MatrixBlockInterface::class,
                AssetInterface::class,
                UserInterface::class,
                GlobalSetInterface::class,
                ElementInterface::class,
                CategoryInterface::class,
                TagInterface::class,
            ];

            foreach ($interfaces as $interfaceClass) {
                if (!is_subclass_of($interfaceClass, InterfaceType::class)) {
                    throw new GqlException('Incorrectly defined interface ' . $interfaceClass);
                }

                /** @var GeneratorInterface $typeGeneratorClass */
                $typeGeneratorClass = $interfaceClass::getTypeGenerator();

                foreach ($typeGeneratorClass::generateTypes() as $type) {
                    $schemaConfig['types'][] = $type;
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
     * Execute a GraphQL query for a given schema definition.
     *
     * @param Schema $schema The schema definition to use.
     * @param string $query The query string to execute.
     * @param array|null $variables The variables to use.
     * @param string|null $operationName The operation name.
     * @return array
     */
    public function executeQuery(Schema $schemaDef, string $query, $variables, $operationName): array
    {
        $event = new ExecuteGqlQueryEvent([
            'schemaDef' => $schemaDef,
            'query' => $query,
            'variables' => $variables,
            'operationName' => $operationName,
        ]);

        $this->trigger(self::EVENT_BEFORE_EXECUTE_GQL_QUERY, $event);

        if ($event->result === null) {
            $event->result = GraphQL::executeQuery($schemaDef, $query, $event->rootValue, $event->context, $variables, $operationName)->toArray(true);
        }

        $this->trigger(self::EVENT_AFTER_EXECUTE_GQL_QUERY, $event);

        return $event->result;
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
            $this->saveSchema($schema);
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
     * @return bool Whether the schema was saved successfully
     * @throws Exception
     */
    public function saveSchema(GqlSchema $schema, $runValidation = true): bool
    {
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
        $schemaRecord->enabled = (bool) $schema->enabled;
        $schemaRecord->expiryDate = $schema->expiryDate;
        $schemaRecord->lastUsed = $schema->lastUsed;
        $schemaRecord->scope = $schema->scope;

        if ($schema->accessToken) {
            $schemaRecord->accessToken = $schema->accessToken;
        }

        $schemaRecord->save();
        $schema->id = $schemaRecord->id;

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
     * Get GraphQL type definitions from a list of models that support GraphQL
     *
     * @return void
     */
    private function _registerGqlTypes()
    {
        $typeList = [
            // Scalars
            DateTime::class,

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

        TypeLoader::registerType('Query', function () use ($event) {
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
