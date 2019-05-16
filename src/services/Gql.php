<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\events\RegisterGqlQueryEvent;
use craft\events\RegisterGqlTypeEvent;
use craft\gql\interfaces\Field as FieldInterface;
use craft\gql\types\AssetTransform;
use craft\gql\types\CategoryGroup;
use craft\gql\types\FieldGroup;
use craft\gql\types\fields\Assets as AssetsField;
use craft\gql\types\fields\Matrix as MatrixType;
use craft\gql\types\fields\PlainText;
use craft\gql\types\fields\Table;
use craft\gql\types\fields\UnsupportedField;
use craft\gql\types\Section;
use craft\gql\types\generators\EntryType as EntryTypeGenerator;
use craft\gql\types\Site;
use craft\gql\types\SiteGroup;
use craft\gql\types\Structure;
use craft\gql\types\StructureNode;
use craft\gql\queries\AssetTransform as AssetTransformQuery;
use craft\gql\queries\CategoryGroup as CategoryGroupQuery;
use craft\gql\queries\Entry as EntryQuery;
use craft\gql\queries\Field as FieldQuery;
use craft\gql\queries\FieldGroup as FieldGroupQuery;
use craft\gql\queries\Section as SectionQuery;
use craft\gql\queries\SiteGroup as SiteGroupQuery;
use GraphQL\Type\Definition\ObjectType;
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
    const EVENT_REGISTER_GQL_TYPES = 'registerGraphQlTypes';

    /**
     * @TODO docs
     */
    const EVENT_REGISTER_GQL_QUERIES = 'registerGrapQlQueries';

    // Properties
    // =========================================================================

    // Public Methods
    // =========================================================================

    /**
     * Returns the GraphQL schema.
     *
     * @param string $token the auth token
     * @return Schema
     */
    public function getSchema(string $token = null): Schema
    {
        // TODO check for cached schema first
        $types = $this->getGqlTypeDefinitions();
        $queries = $this->getGqlQueryDefinitions();

        return new Schema([
            'types' => $types,
            'query' => $queries,
        ]);
    }

    /**
     * Get GraphQL type definitions from a list of models that support GraphQL
     *
     * @return array
     */
    public function getGqlTypeDefinitions(): array
    {
        $typeList = [
            // Interfaces
            FieldInterface::getType(),

            // Entities
            AssetTransform::getType(),
            CategoryGroup::getType(),
            FieldGroup::getType(),
            Section::getType(),
            Site::getType(),
            SiteGroup::getType(),
            Structure::getType(),
            StructureNode::getType(),

            // Fields
            AssetsField::getType(),
            PlainText::getType(),
            Table::getType(),
            MatrixType::getType(),
            UnsupportedField::getType(),

        ];

        // Content
        $typeList = array_merge($typeList, EntryTypeGenerator::getTypes());

        $event = new RegisterGqlTypeEvent([
            'types' => $typeList
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_TYPES, $event);

        return $event->types;
    }

    /**
     * Get GraphQL query definitions
     *
     * @return ObjectType
     */
    public function getGqlQueryDefinitions(): ObjectType
    {
        $queryList = [
            // Entities
            AssetTransformQuery::getQueries(),
            CategoryGroupQuery::getQueries(),
            FieldGroupQuery::getQueries(),
            FieldQuery::getQueries(),
            SectionQuery::getQueries(),
            SiteGroupQuery::getQueries(),
            EntryQuery::getQueries(),
        ];

        $event = new RegisterGqlQueryEvent([
            'queries' => array_merge(...$queryList)
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_QUERIES, $event);

        return new ObjectType([
            'name' => 'Query',
            'fields' => $event->queries
        ]);
    }

}
