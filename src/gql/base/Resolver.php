<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\gql\ElementQueryConditionBuilder;
use craft\helpers\StringHelper;
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
     */
    public static function getArrayableArguments(): array
    {
        return [];
    }

    /**
     * Prepare arguments for use, converting to array where applicable.
     *
     * @param array $arguments
     * @return array
     */
    public static function prepareArguments(array $arguments): array
    {
        $arrayable = static::getArrayableArguments();

        foreach ($arguments as $key => &$value) {
            if (in_array($key, $arrayable, true) && !empty($value) && !is_array($value)) {
                $array = StringHelper::split($value);

                if (count($array) > 1) {
                    $value = $array;
                }
            } else if (is_array($value) && count($value) === 1 && isset($value[0]) && $value[0] === '*') {
                // Normalize ['*'] to '*'
                $value = '*';
            }
        }

        return $arguments;
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
        $conditions = (new ElementQueryConditionBuilder($resolveInfo))->extractQueryConditions();

        return isset($conditions['with']) ? $conditions['with'] : [];
    }
}
