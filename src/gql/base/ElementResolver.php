<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\base;

use Craft;
use craft\base\ElementInterface;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\elements\db\ElementQuery;
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
        $arguments = self::prepareArguments($arguments);
        $fieldName = $resolveInfo->fieldName;

        $query = static::prepareQuery($source, $arguments, $fieldName);

        // If that's already preloaded, then, uhh, skip the preloading?
        if (is_array($query)) {
            return $query;
        }

        $parentField = null;

        // This will happen if something is either dynamically added or is inside an block element that didn't support eager-loading
        // and broke the eager-loading chain. In this case Craft has to provide the relevant context so the condition builder knows where it's at.
        if ($source instanceof ElementInterface) {
            $context = $source->getFieldContext();

            if ($context !== 'global') {
                $field = Craft::$app->getFields()->getFieldByHandle($fieldName, $context);
                if ($field instanceof GqlInlineFragmentFieldInterface) {
                    $parentField = $field;
                }
            }
        }

        $conditionBuilder = Craft::createObject([
            'class' => ElementQueryConditionBuilder::class,
            'resolveInfo' => $resolveInfo
        ]);

        $conditions = $conditionBuilder->extractQueryConditions($parentField);

        /** @var ElementQuery $query */
        foreach ($conditions as $method => $parameters) {
            if (method_exists($query, $method)) {
                $query = $query->{$method}($parameters);
            }
        }

        return $query;
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
