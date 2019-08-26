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
use craft\events\RegisterGqlDirectivesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\gql\common\SchemaObject;
use craft\gql\base\Directive;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\base\InterfaceType;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\queries\Asset as AssetQuery;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\Ping as PingQuery;
use craft\gql\queries\User as UserQuery;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\Query;
use craft\helpers\DateTimeHelper;
use craft\models\GqlToken;
use craft\records\GqlToken as GqlTokenRecord;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Gql service provides GraphQL functionality.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class Gql extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterGqlTypesEvent The event that is triggered when registering GraphQL types.
     *
     * Plugins get a chance to add their own GQL types.
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     *
     * ---
     * ```php
     * use craft\events\RegisterGqlTypeEvent;
     * use craft\services\GraphQl;
     * use yii\base\Event;
     *
     * Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypeEvent $event) {
     *     // Add my GQL types
     *     $event->types[] = MyType::class;
     * });
     * ```
     */
    const EVENT_REGISTER_GQL_TYPES = 'registerGqlTypes';

    /**
     * @event RegisterGqlQueriesEvent The event that is triggered when registering GraphQL queries.
     *
     * Plugins get a chance to add their own GQL queries.
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
     *     // Add my GQL queries
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
     * Plugins get a chance to add their own GQL directives.
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
     * Token value for the public token.
     */
    const PUBLIC_TOKEN = '__PUBLIC__';

    /**
     * Currently loaded schema
     *
     * @var Schema
     */
    private $_schema;

    /**
     * GQL token currently in user
     *
     * @var GqlToken
     */
    private $_token;

    // Public Methods
    // =========================================================================

    /**
     * Returns the GraphQL schema.
     *
     * @param GqlToken $token
     * @param bool $prebuildSchema should the schema be deep-scanned and pre-built instead of lazy-loaded
     * @return Schema
     * @throws GqlException in case of invalid schema
     */
    public function getSchema($token = null, $prebuildSchema = false): Schema
    {
        if ($token) {
            $this->setToken($token);
        }

        if (!$this->_schema || $prebuildSchema) {
            $this->_registerGqlTypes();
            $this->_registerGqlQueries();

            $schemaConfig = [
                'typeLoader' => TypeLoader::class . '::loadType',
                'query' => TypeLoader::loadType('Query'),
                'directives' => $this->_loadGqlDirectives(),
            ];

            if (!$prebuildSchema) {
                $this->_schema = new Schema($schemaConfig);
            } else {
                $interfaces = [
                    EntryInterface::class,
                    MatrixBlockInterface::class,
                    AssetInterface::class,
                    UserInterface::class,
                    GlobalSetInterface::class,
                    ElementInterface::class,
                ];

                foreach ($interfaces as $interfaceClass) {
                    if (!is_subclass_of($interfaceClass, InterfaceType::class)) {
                        throw new GqlException('Incorrectly defined interface ' . $interfaceClass);
                    }

                    $typeGeneratorClass = $interfaceClass::getTypeGenerator();

                    foreach ($typeGeneratorClass::generateTypes() as $type) {
                        $schemaConfig['types'][] = $type;
                    }
                }
                try {
                    $this->_schema = new Schema($schemaConfig);
                    $this->_schema->getTypeMap();
                } catch (\Throwable $exception) {
                    throw new GqlException('Failed to validate the GQL Schema - ' . $exception->getMessage());
                }
            }
        }

        return $this->_schema;
    }

    /**
     * Set the GQL token to be used.
     *
     * @param GqlToken|null $token The token to set. Null to unset.
     * @throws \yii\base\Exception
     */
    public function setToken(GqlToken $token = null)
    {
        $this->_token = $token;

        if ($token) {
            $token->lastUsed = DateTimeHelper::currentUTCDateTime();
            $this->saveToken($token);
        }
    }

    /**
     * Return the token in use for the current GQL request.
     *
     * @return GqlToken
     * @throws GqlException if no token set.
     */
    public function getCurrentToken(): GqlToken
    {
        if (!$this->_token) {
            throw new GqlException('No access token set.');
        }

        return $this->_token;
    }

    /**
     * Return an array of all tokens.
     *
     * @return array
     */
    public function getTokens(): array
    {
        $rows = $this->_createTokenQuery()->all();
        $tokens = [];
        $tokenNames = [];

        $publicToken = null;

        foreach ($rows as $row) {
            $token = new GqlToken($row);
            if ($token->accessToken == self::PUBLIC_TOKEN) {
                $publicToken = $token;
            } else {
                $tokens[] = $token;
                $tokenNames[] = $token->name;
            }
        }

        // Sort tokens by name
        array_multisort($tokenNames, SORT_ASC, SORT_STRING, $tokens);

        // Add the public token to the top
        array_unshift($tokens, $publicToken ?? $this->_createPublicToken());

        return $tokens;
    }

    /**
     * Return the public token. If it does not exist, it will be created.
     *
     * @return GqlToken
     * @throws \yii\base\Exception
     */
    public function getPublicToken(): GqlToken
    {
        $tokenRow = $this->_createTokenQuery()->where(['accessToken' => self::PUBLIC_TOKEN])->one();

        if ($tokenRow) {
            return new GqlToken($tokenRow);
        }

        return $this->_createPublicToken();
    }

    /**
     * Returns all of the known GQL permissions, sorted by category.
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

        return $permissions;

    }

    /**
     * Flush all GQL caches, registries and loaders.
     *
     * @return void
     */
    public function flushCaches()
    {
        $this->_token = null;
        $this->_schema = null;
        TypeLoader::flush();
        GqlEntityRegistry::flush();
    }

    /**
     * Return a GQL token by its id.
     *
     * @param int $tokenId
     * @return GqlToken|null
     */
    public function getTokenById(int $tokenId)
    {
        $tokenRow = $this->_createTokenQuery()->where(['id' => $tokenId])->one();
        return $tokenRow ? new GqlToken($tokenRow) : null;
    }

    /**
     * Return a GQL token by its uid.
     *
     * @param string $tokenUid
     * @return GqlToken|null
     */
    public function getTokenByUid(string $tokenUid)
    {
        $tokenRow = $this->_createTokenQuery()->where(['uid' => $tokenUid])->one();
        return $tokenRow ? new GqlToken($tokenRow) : null;
    }

    /**
     * Return a GQL token by its access token.
     *
     * @param string $accessToken
     * @return GqlToken|null
     */
    public function getTokenByAccessToken(string $accessToken)
    {
        if ($accessToken == self::PUBLIC_TOKEN) {
            return $this->getPublicToken();
        }

        $tokenRow = $this->_createTokenQuery()->where(['accessToken' => $accessToken])->one();
        return $tokenRow ? new GqlToken($tokenRow) : null;
    }

    /**
     * Save a GQL token.
     *
     * @param GqlToken $token the token to save
     * @param bool $runValidation Whether the token should be validated
     * @return bool Whether the token was saved successfully
     * @throws \yii\base\Exception
     */
    public function saveToken(GqlToken $token, $runValidation = true): bool
    {
        $isNewToken = !$token->id;

        if ($runValidation && !$token->validate()) {
            Craft::info('Token not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewToken) {
            $tokenRecord = new GqlTokenRecord();
        } else {
            $tokenRecord = GqlTokenRecord::findOne($token->id) ?: new GqlTokenRecord();
        }

        $tokenRecord->name = $token->name;
        $tokenRecord->enabled = (bool) $token->enabled;
        $tokenRecord->expiryDate = $token->expiryDate;
        $tokenRecord->lastUsed = $token->lastUsed;
        $tokenRecord->permissions = $token->permissions;

        if ($isNewToken) {
            $tokenRecord->accessToken = $token->accessToken;
        }

        $tokenRecord->save();
        $token->id = $tokenRecord->id;

        return true;
    }

    /**
     * Deletes a GQL token by its ID.
     *
     * @param int $tokenId The transform's ID
     * @return bool Whether the token was deleted.
     */
    public function deleteTokenById(int $tokenId): bool
    {
        $tokenRecord = GqlTokenRecord::findOne($tokenId);

        if (!$tokenRecord) {
            return true;
        }

        return $tokenRecord->delete();
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
        ];

        $event = new RegisterGqlTypesEvent([
            'types' => $typeList,
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_TYPES, $event);

        foreach ($event->types as $type) {
            /** @var SchemaObject $type */
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
            Transform::class,
        ];

        $event = new RegisterGqlDirectivesEvent([
            'directives' => $directiveClasses
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_DIRECTIVES, $event);

        $directives = GraphQL::getStandardDirectives();

        foreach ($event->directives as $directive) {
            /** @var Directive $directive */
            $directives[] = $directive::getDirective();
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
    private function _createTokenQuery(): DbQuery
    {
        $query = (new DbQuery())
            ->select([
                'id',
                'name',
                'accessToken',
                'enabled',
                'expiryDate',
                'lastUsed',
                'permissions',
                'dateCreated',
                'uid',
            ])
            ->from([Table::GQLTOKENS]);

        return $query;
    }

    /**
     * Creates the public token.
     *
     * @return GqlToken
     * @throws Exception if the token couldn't be created.
     */
    private function _createPublicToken(): GqlToken
    {
        $token = new GqlToken([
            'name' => 'Public Token',
            'accessToken' => self::PUBLIC_TOKEN,
            'enabled' => true,
        ]);
        if (!$this->saveToken($token)) {
            throw new Exception('Couldn’t create public token.');
        }
        return $token;
    }
}
