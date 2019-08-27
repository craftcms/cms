<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\elements\db\ElementQuery;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ElementResolver
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
abstract class ElementResolver extends Resolver
{
    /**
     * @inheritdoc
     */
    public static function getArrayableArguments(): array
    {
        return array_merge(parent::getArrayableArguments(), [
            'siteId',
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $arguments = self::prepareArguments($arguments);
        $fieldName = $resolveInfo->fieldName;

        $query = static::prepareQuery($source, $arguments, $fieldName);

        // If that's already preloaded, then, uhh, skip the preloading?
        if (is_array($query)) {
            return $query;
        }

        /** @var ElementQuery $query */
        $preloadNodes = self::extractEagerLoadCondition($resolveInfo);
        $eagerLoadConditions = [];

        // Set up the preload con
        foreach ($preloadNodes as $element => $parameters) {
            if (empty($parameters)) {
                $eagerLoadConditions[] = $element;
            } else {
                $eagerLoadConditions[] = [$element, $parameters];
            }
        }

        return $query->with($eagerLoadConditions)->all();
    }

    /**
     * Prepare an element Query based on the source, arguments and the field name on the source.
     *
     * @param mixed $source The source. Null if top-level field being resolved.
     * @param array $arguments Arguments to apply to the query.
     * @param null $fieldName Field name to resolve on the source, if not a top-level resolution.
     *
     * @return mixed
     */
    abstract protected static function prepareQuery($source, array $arguments, $fieldName = null);
}
