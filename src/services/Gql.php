<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\events\RegisterGqlQueryEvent;
use craft\events\RegisterGqlTypeEvent;
use craft\gql\common\SchemaObject;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\TypeLoader;
use craft\gql\types\AssetTransform;
use craft\gql\types\CategoryGroup;
use craft\gql\types\CategoryGroup_SiteSettings;
use craft\gql\types\DateTimeType;
use craft\gql\types\FieldGroup;
use craft\gql\types\fields\PlainText;
use craft\gql\types\fields\Table;
use craft\gql\types\fields\UnsupportedField;
use craft\gql\types\generators\EntryType;
use craft\gql\types\Query;
use craft\gql\types\Section;
use craft\gql\types\Section_SiteSettings;
use craft\gql\types\Site;
use craft\gql\types\SiteGroup;
use craft\gql\types\Structure;
use craft\gql\types\StructureNode;
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

    private $_schema;

    // Public Methods
    // =========================================================================

    /**
     * Returns the GraphQL schema.
     *
     * @param string $token the auth token
     * @return Schema
     */
    public function getSchema(string $token = null, bool $devMode = false): Schema
    {
        if (!$this->_schema || $devMode) {
            $this->_registerGqlTypes();
            $this->_registerGqlQueries();

            $schemaConfig = [
                'typeLoader' => TypeLoader::class . '::loadType',
                'query' => TypeLoader::loadType('Query'),
            ];

            if ($devMode) {
                // allow plugins to register their generators
                $schemaConfig['types'] = EntryType::getTypes();
            }

            $this->_schema = new Schema($schemaConfig);

            if ($devMode) {
                $this->_schema->assertValid();
            }
        }

        return $this->_schema;
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
            DateTimeType::class,

            // Interfaces
            EntryInterface::class,
        ];

        $event = new RegisterGqlTypeEvent([
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
        ];

        $event = new RegisterGqlQueryEvent([
            'queries' => array_merge(...$queryList)
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_QUERIES, $event);

        TypeLoader::registerType('Query', function () use ($event) {
            return call_user_func(Query::class . '::getType', $event->queries);
        });
    }
}
