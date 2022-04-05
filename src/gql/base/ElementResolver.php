<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\elements\db\ElementQuery;
use craft\gql\ArgumentManager;
use craft\gql\ElementQueryConditionBuilder;
use craft\helpers\Gql as GqlHelper;
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
     * Resolve an element query to a single result.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return ElementInterface|null|mixed
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
     * Resolve an element query to a total count of elements.
     *
     * @param $source
     * @param array $arguments
     * @param $context
     * @param ResolveInfo $resolveInfo
     * @return ElementInterface|null|mixed
     */
    public static function resolveCount($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        return $query->count();
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
        /** @var ArgumentManager $argumentManager */
        $argumentManager = empty($context['argumentManager']) ? Craft::createObject(['class' => ArgumentManager::class]) : $context['argumentManager'];
        $arguments = $argumentManager->prepareArguments($arguments);

        $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);

        $query = static::prepareQuery($source, $arguments, $fieldName);

        // If that's already preloaded, then, uhh, skip the preloading?
        if (is_array($query)) {
            return $query;
        }

        $parentField = null;

        if ($source instanceof ElementInterface) {
            $fieldContext = $source->getFieldContext();
            $field = Craft::$app->getFields()->getFieldByHandle($fieldName, $fieldContext);

            // This will happen if something is either dynamically added or is inside an block element that didn't support eager-loading
            // and broke the eager-loading chain. In this case Craft has to provide the relevant context so the condition builder knows where it's at.
            if (($fieldContext !== 'global' && $field instanceof GqlInlineFragmentFieldInterface) || $field instanceof EagerLoadingFieldInterface) {
                $parentField = $field;
            }
        }

        /** @var ElementQueryConditionBuilder $conditionBuilder */
        $conditionBuilder = empty($context['conditionBuilder']) ? Craft::createObject(['class' => ElementQueryConditionBuilder::class]) : $context['conditionBuilder'];
        $conditionBuilder->setResolveInfo($resolveInfo);
        $conditionBuilder->setArgumentManager($argumentManager);

        $conditions = $conditionBuilder->extractQueryConditions($parentField);

        /** @var ElementQuery $query */
        foreach ($conditions as $method => $parameters) {
            if (method_exists($query, $method)) {
                $query = $query->{$method}($parameters);
            }
        }

        // Apply max result config
        $maxGraphqlResults = Craft::$app->getConfig()->getGeneral()->maxGraphqlResults;

        // Reset negative limit to zero
        if ((int)$query->limit < 0) {
            $query->limit(0);
        }

        if ($maxGraphqlResults > 0) {
            $queryLimit = is_null($query->limit) ? $maxGraphqlResults : min($maxGraphqlResults, $query->limit);
            $query->limit($queryLimit);
        }

        return $query;
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
