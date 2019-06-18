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
use craft\gql\directives\BaseDirective;
use craft\gql\directives\FormatDateTime;
use craft\gql\directives\Transform;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\interfaces\elements\Element as ElementInterface;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\interfaces\elements\GlobalSet as GlobalSetInterface;
use craft\gql\interfaces\elements\MatrixBlock as MatrixBlockInterface;
use craft\gql\interfaces\elements\User as UserInterface;
use craft\gql\queries\Asset as AssetQuery;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\queries\GlobalSet as GlobalSetQuery;
use craft\gql\queries\MatrixBlock as MatrixBlockQuery;
use craft\gql\queries\User as UserQuery;
use craft\gql\TypeLoader;
use craft\gql\types\DateTime;
use craft\gql\types\generators\AssetType;
use craft\gql\types\generators\EntryType;
use craft\gql\types\generators\GlobalSetType;
use craft\gql\types\generators\MatrixBlockType;
use craft\gql\types\generators\UserType;
use craft\gql\types\Query;
use craft\models\GqlToken;
use craft\models\Section;
use craft\records\GqlToken as GqlTokenRecord;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use yii\base\Component;

/**
 * The Gql service provides GraphQL functionality.
 * @TODO Docs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class Gql extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterGqlModelEvent The event that is triggered when registering GraphQL types.
     *
     * @TODO: docs
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     * ---
     * ```php
     * use craft\events\RegisterGqlTypeEvent;
     * use craft\services\GraphQl;
     * use yii\base\Event;
     *
     * Event::on(Gql::class,
     *     Gql::EVENT_REGISTER_GQL_TYPES,
     *     function(RegisterGqlModelEvent $event) {
     *         $event->types[] = MyType::getType();
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_GQL_TYPES = 'registerGqlTypes';

    /**
     * @TODO docs
     */
    const EVENT_REGISTER_GQL_QUERIES = 'registerGqlQueries';

    const EVENT_REGISTER_GQL_DIRECTIVES = 'registerGqlDirectives';

    private $_schema;

    // Public Methods
    // =========================================================================

    /**
     * Returns the GraphQL schema.
     *
     * @param bool $validateSchema should the schema be deep-scanned and validated
     * @return Schema
     * @throws GqlException in case of invalid schema
     */
    public function getSchema($validateSchema = false): Schema
    {
        if (!$this->_schema || $validateSchema) {
            $this->_registerGqlTypes();
            $this->_registerGqlQueries();

            $schemaConfig = [
                'typeLoader' => TypeLoader::class . '::loadType',
                'query' => TypeLoader::loadType('Query'),
                'directives' => $this->_loadGqlDirectives(),
            ];

            if (!$validateSchema) {
                $this->_schema = new Schema($schemaConfig);
            } else {
                // @todo: allow plugins to register their generators
                $schemaConfig['types'] = array_merge(
                    EntryType::generateTypes(),
                    MatrixBlockType::generateTypes(),
                    AssetType::generateTypes(),
                    UserType::generateTypes(),
                    GlobalSetType::generateTypes()
                );
                try {
                    $this->_schema = new Schema($schemaConfig);
                    $this->_schema->assertValid();
                } catch (\Throwable $exception) {
                    throw new GqlException('Failed to validate the GQL Schema - ' . $exception->getMessage());
                }
            }
        }

        return $this->_schema;
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

        foreach ($rows as $row) {
            $tokens[] = new GqlToken($row);
        }

        return $tokens;
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
     * Return a GQL token by its access token.
     *
     * @param string $accessToken
     * @return GqlToken|null
     */
    public function getTokenByAccessToken(string $accessToken)
    {
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

        if ($isNewToken) {
            $token->accessToken = Craft::$app->getSecurity()->generateRandomString(32);
        }

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
        $tokenRecord->permissions = $token->permissions;

        if ($isNewToken) {
            $tokenRecord->accessToken = $token->accessToken;
        }

        return $tokenRecord->save();
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
            EntryQuery::getQueries(),
            MatrixBlockQuery::getQueries(),
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
     * @return BaseDirective[]
     */
    private function _loadGqlDirectives(): array
    {
        $directiveClasses = [
            // Queries
            FormatDateTime::class,
            Transform::class,
        ];

        $event = new RegisterGqlDirectivesEvent([
            'directives' => $directiveClasses
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_DIRECTIVES, $event);

        $directives = GraphQL::getStandardDirectives();

        foreach ($event->directives as $directive) {
            /** @var BaseDirective $directive */
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
                $suffix = 'sections.' . $section->uid;
                $nested = ['label' => Craft::t('app', 'View section - {section}', ['section' => Craft::t('site', $section->name)])];

                if ($section->type != Section::TYPE_SINGLE) {
                    foreach ($sortedEntryTypes[$section->id] as $entryType) {
                        $nested['nested'][$suffix . '.entryTypes.' . $entryType->uid . '.view'] = ['label' => Craft::t('app', 'View entry type - {entryType}', ['entryType' => Craft::t('site', $entryType->name)])];
                    }
                }

                $sectionPermissions[$suffix . '.view'] = $nested;
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
                $suffix = 'volumes.' . $volume->uid;
                $volumePermissions[$suffix . '.view'] = ['label' => Craft::t('app', 'View volume - {volume}', ['volume' => Craft::t('site', $volume->name)])];
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
                $suffix = 'globalSets.' . $globalSet->uid;
                $globalSetPermissions[$suffix . '.view'] = ['label' => Craft::t('app', 'View global set - {globalSet}', ['globalSet' => Craft::t('site', $globalSet->name)])];
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
        $userPermissions = ['userGroups.admin.view' => ['label' => Craft::t('app', 'View user group - Admin')]];

        foreach ($userGroups as $userGroup) {
            $suffix = 'userGroups.' . $userGroup->uid;
            $userPermissions[$suffix . '.view'] = ['label' => Craft::t('app', 'View user group - {userGroup}', ['userGroup' => Craft::t('site', $userGroup->name)])];
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
                'permissions',
            ])
            ->from([Table::GQLTOKENS]);

        return $query;
    }

}
