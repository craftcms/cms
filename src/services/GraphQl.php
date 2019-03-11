<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\Repository\PlatformRepository;
use Composer\Semver\VersionParser;
use Craft;
use craft\base\GraphQlInterface;
use craft\base\Plugin;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidPluginException;
use craft\events\RegisterGraphQlModelEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\AssetTransform;
use craft\models\CategoryGroup;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * The GraphQL service provides GraphQL functionality.
 * @TODO Docs
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class GraphQl extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterGraphQlModelEvent The event that is triggered when registering models that support GraphQL.
     *
     * Models must implement types must implement [[GraphQlInterface]]. [[GraphQlTrait]] provides a base implementation.
     *
     * @TODO: docs
     * See [GraphQL](https://docs.craftcms.com/v3/graphql.html) for documentation on adding GraphQL support.
     * ---
     * ```php
     * use craft\events\RegisterGraphQlModelEvent;
     * use craft\services\GraphQl;
     * use yii\base\Event;
     *
     * Event::on(GraphQl::class,
     *     GraphQl::EVENT_REGISTER_GRAPHQL_MODELS,
     *     function(RegisterGraphQlModelEvent $event) {
     *         $event->models[] = MyModel::class;
     *     }
     * );
     * ```
     */
    const EVENT_REGISTER_GRAPHQL_MODELS = 'registerGraphQlModels';

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
        $graphQlSupportedModels = $this->getGqlSupportedModels();
        $types = $this->getGqlTypeDefinitions($graphQlSupportedModels);
        $queries = $this->getQueries($graphQlSupportedModels);

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
        $output = [];

        /** @var GraphQlInterface $model */
        foreach ($models as $model) {
            $output[] = $model::getGqlTypeDefinition();
        }

        return $output;
    }

    /**
     * Get GraphQL query definitions from a list of models that support GraphQL
     *
     * @param string[] $models
     * @return ObjectType
     */
    public function getQueries(array $models) {
        $queries = [];

        /** @var GraphQlInterface $model */
        foreach ($models as $model) {
            $queries += $model::getGqlQueryDefinitions();
        }

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
    public function getGqlSupportedModels(): array
    {
        $graphQlSupportedModels = [
            AssetTransform::class,
            CategoryGroup::class,
        ];

        $event = new RegisterGraphQlModelEvent([
            'models' => $graphQlSupportedModels
        ]);

        $this->trigger(self::EVENT_REGISTER_GRAPHQL_MODELS, $event);

        return $event->models;
    }
}
