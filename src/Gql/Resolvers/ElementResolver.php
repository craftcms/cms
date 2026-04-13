<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use Craft;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Gql\ArgumentManager;
use CraftCms\Cms\Gql\Contracts\GqlInlineFragmentFieldInterface;
use CraftCms\Cms\Gql\ElementQueryConditionBuilder;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use GraphQL\Type\Definition\ResolveInfo;

abstract class ElementResolver extends Resolver
{
    public static function resolveOne(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        $value = $query instanceof ElementQueryInterface ? $query->one() : $query;

        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }

    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $query = self::prepareElementQuery($source, $arguments, $context, $resolveInfo);
        $value = $query instanceof ElementQueryInterface ? $query->all() : $query;

        return GqlHelper::applyDirectives($source, $resolveInfo, $value);
    }

    public static function resolveCount(mixed $source, array $arguments, ?array $context, ResolveInfo $resolveInfo): mixed
    {
        return self::prepareElementQuery($source, $arguments, $context, $resolveInfo)->count();
    }

    /**
     * @return ElementQuery|ElementCollection
     */
    protected static function prepareElementQuery(mixed $source, array $arguments, ?array $context, ResolveInfo $resolveInfo): ElementQueryInterface|ElementCollection
    {
        /** @var ArgumentManager $argumentManager */
        $argumentManager = empty($context['argumentManager']) ? Craft::createObject(['class' => ArgumentManager::class]) : $context['argumentManager'];
        $arguments = $argumentManager->prepareArguments($arguments);

        $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);

        // Pull out the parsed orderBy before passing arguments to the resolver
        $orderBy = Arr::pull($arguments, 'orderBy');

        // combine `search` and `searchTermOptions` if both are set
        $searchTermOptions = Arr::pull($arguments, 'searchTermOptions');
        if ($searchTermOptions && isset($arguments['search'])) {
            $searchTermOptions['query'] = $arguments['search'];
            $arguments['search'] = $searchTermOptions;
        }

        /** @var ElementQuery $query */
        $query = static::prepareQuery($source, $arguments, $fieldName);

        // If that's already preloaded, then, uhh, skip the preloading?
        if (! $query instanceof ElementQueryInterface) {
            return $query;
        }

        // Apply parsed orderBy pairs (e.g. [['slug', 'asc'], ['title', 'desc']])
        if (is_array($orderBy)) {
            foreach ($orderBy as [$column, $direction]) {
                $query->orderBy($column);
            }
        }

        $parentField = null;

        if ($source instanceof ElementInterface) {
            $fieldContext = $source->getFieldContext();
            $field = Fields::getFieldByHandle($fieldName, $fieldContext);

            // This will happen if something is either dynamically added or is inside an block element that didn't support eager-loading
            // and broke the eager-loading chain. In this case Craft has to provide the relevant context so the condition knows where it's at.
            if (($fieldContext !== 'global' && $field instanceof GqlInlineFragmentFieldInterface) || $field instanceof EagerLoadingFieldInterface) {
                $parentField = $field;
            }
        }

        /** @var ElementQueryConditionBuilder $conditionBuilder */
        $conditionBuilder = empty($context['conditionBuilder']) ? Craft::createObject(['class' => ElementQueryConditionBuilder::class]) : $context['conditionBuilder'];
        $conditionBuilder->setResolveInfo($resolveInfo);
        $conditionBuilder->setArgumentManager($argumentManager);

        $conditions = $conditionBuilder->extractQueryConditions($parentField);

        foreach ($conditions as $method => $parameters) {
            if (method_exists($query, $method)) {
                $query = $query->{$method}($parameters);
            }
        }

        // Apply max result config
        $maxGraphqlResults = Cms::config()->maxGraphqlResults;

        // Reset negative limit to zero
        if ((int) $query->getLimit() < 0) {
            $query->limit(0);
        }

        if ($maxGraphqlResults > 0) {
            $queryLimit = is_null($query->getLimit()) ? $maxGraphqlResults : min($maxGraphqlResults, $query->getLimit());
            $query->limit($queryLimit);
        }

        return $query;
    }

    /**
     * @param  mixed  $source  The source. Null if top-level field being resolved.
     * @param  array  $arguments  Arguments to apply to the query.
     * @param  string|null  $fieldName  Field name to resolve on the source, if not a top-level resolution.
     */
    abstract protected static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed;
}
