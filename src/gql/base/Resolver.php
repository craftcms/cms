<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\gql\ArgumentManager;
use craft\gql\ElementQueryConditionBuilder;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Resolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class Resolver
{
    /**
     * @var array Cache fields by context.
     */
    protected static $eagerLoadableFieldsByContext;

    /**
     * Resolve a field to its value.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     */
    abstract public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);

    /**
     * Returns a list of all the arguments that can be accepted as arrays.
     *
     * @return array
     * @deprecated in 3.6. Any argument modifications should be performed using argument handlers.
     */
    public static function getArrayableArguments(): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `craft\gql\base\Resolve::getArrayableArguments` method has been deprecated. Any argument modifications should be performed using argument handlers.');
        return [];
    }

    /**
     * Prepare arguments for use, converting to array where applicable.
     *
     * @param array $arguments
     * @return array
     * @deprecated in 3.6. Any argument modifications should be performed using argument handlers.
     */
    public static function prepareArguments(array $arguments): array
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The `craft\gql\base\Resolve::prepareArguments` Method has been deprecated. Any argument modifications should be performed using argument handlers.');
        $argumentManager = new ArgumentManager();

        return $argumentManager->prepareArguments($arguments);
    }

    /**
     * Extract eager load conditions for a given resolve information. Preferrably at the very top of the query.
     *
     * @param Node $parentNode
     * @return array
     * @deprecated as of Craft 3.5.0.
     */
    protected static function extractEagerLoadCondition(ResolveInfo $resolveInfo)
    {
        $conditions = (new ElementQueryConditionBuilder(['resolveInfo' => $resolveInfo]))->extractQueryConditions();

        return isset($conditions['with']) ? $conditions['with'] : [];
    }
}
