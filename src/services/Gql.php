<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

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

            if (!$validateSchema ){
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
}
