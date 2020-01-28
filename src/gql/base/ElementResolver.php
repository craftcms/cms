<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\StringHelper;
use craft\services\Gql;
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
     * Resolve an element query to a single result.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return Element|null|mixed
     */
    public static function resolveOne($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        $value = $query instanceof ElementQuery ? $query->one() : $query;
        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }

    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        $value = $query instanceof ElementQuery ? $query->all() : $query;
        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }

    /**
     * Prepare an element query for given resolution argument set.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return ElementQuery|array
     */
    protected static function prepareElementQuery($source, array $arguments, $context, ResolveInfo $resolveInfo)
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

        $relationCountFields = [];

        // Set up the preload count
        foreach ($preloadNodes as $element => $parameters) {
            if (StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                if (isset($parameters['field'])) {
                    $relationCountFields[$parameters['field']] = true;
                }
            }
        }

        foreach ($preloadNodes as $element => $parameters) {
            if (StringHelper::endsWith($element, '@' . Gql::GRAPHQL_COUNT_FIELD)) {
                continue;
            }

            if (!empty($relationCountFields[$element])) {
                $parameters['count'] = true;
            }

            if (empty($parameters)) {
                $eagerLoadConditions[] = $element;
            } else {
                $eagerLoadConditions[] = [$element, $parameters];
            }
        }

        return $query->with($eagerLoadConditions);
    }

    /**
     * @inheritdoc
     */
    public static function prepareArguments(array $arguments): array
    {
        $arguments = parent::prepareArguments($arguments);

        if (isset($arguments['relatedToAll'])) {
            $ids = (array)$arguments['relatedToAll'];
            $ids = array_map(function($value) {
                return ['element' => $value];
            }, $ids);
            $arguments['relatedTo'] = array_merge(['and'], $ids);
            unset($arguments['relatedToAll']);
        }

        return $arguments;
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
