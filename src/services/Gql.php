<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\base\Field;
use craft\base\GqlInterface;
use craft\events\RegisterGqlModelEvent;
use craft\models\AssetTransform;
use craft\models\CategoryGroup;
use craft\models\FieldGroup;
use craft\models\Structure;
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
     * @event RegisterGqlModelEvent The event that is triggered when registering models that support GraphQL.
     *
     * Models must implement types must implement [[GqlInterface]]. [[GqlTrait]] provides a base implementation.
     *
     * @TODO: docs
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     * ---
     * ```php
     * use craft\events\RegisterGqlModelEvent;
     * use craft\services\GraphQl;
     * use yii\base\Event;
     *
     * Event::on(Gql::class,
     *     Gql::EVENT_REGISTER_GQL_MODELS,
     *     function(RegisterGqlModelEvent $event) {
     *         $event->models[] = MyModel::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_GQL_MODELS = 'registerGraphQlModels';

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
        $gqlSupportedClasses = $this->getGqlSupportedClasses();
        $types = $this->getGqlTypeDefinitions($gqlSupportedClasses);
        $queries = $this->getQueries($gqlSupportedClasses);

        return new Schema([
            'types' => $types,
            'query' => $queries,
        ]);
    }

    /**
     * Get GraphQL type definitions from a list of models that support GraphQL
     *
     * @param string[] $models
     * @return array
     */
    public function getGqlTypeDefinitions(array $models): array
    {
        $output = [[]];

        /** @var GqlInterface $model */
        foreach ($models as $model) {
            $output[] = $model::getGqlTypeDefinitions();
        }

        return array_merge(...$output);
    }

    /**
     * Get GraphQL query definitions from a list of models that support GraphQL
     *
     * @param string[] $models
     * @return ObjectType
     */
    public function getQueries(array $models) {
        $queries = [[]];

        /** @var GqlInterface $model */
        foreach ($models as $model) {
            $queries[] = $model::getGqlQueryDefinitions();
        }

        $queries = array_merge(...$queries);

        return new ObjectType([
            'name' => 'Query',
            'fields' => $queries
        ]);
    }

    /**
     * Return a list of models that support GraphQL.
     *
     * @return array
     */
    public function getGqlSupportedClasses(): array
    {
        $graphQlSupportedModels = [
            AssetTransform::class,
            CategoryGroup::class,
            FieldGroup::class,
            Structure::class,
            Field::class,
            \craft\models\Section::class,
        ];

        // TODO FIELDS
        $event = new RegisterGqlModelEvent([
            'models' => $graphQlSupportedModels
        ]);

        $this->trigger(self::EVENT_REGISTER_GQL_MODELS, $event);

        return $event->models;
    }
}
