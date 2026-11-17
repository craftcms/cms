<?php

declare(strict_types=1);

namespace craft\gql\base;

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Enums\ElementCapability;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Gql\Attributes\Gql;
use CraftCms\Cms\Gql\GqlHelper;

/**
 * Adds the draft and revision arguments that Craft 4 and 5 argument classes got by default, which they can override
 * {@see getDraftArguments()} and {@see getRevisionArguments()} to remove.
 */
abstract class ElementArguments extends \CraftCms\Cms\Gql\Arguments\ElementArguments
{
    public const EVENT_DEFINE_ARGUMENTS = 'defineArguments';

    /**
     * Adds the draft and revision arguments here rather than in `getArguments()`, so `EVENT_DEFINE_ARGUMENTS` listeners
     * see them, like they did in Craft 5.
     */
    protected static function gqlQueryParamArguments(string $elementType): array
    {
        return array_merge(parent::gqlQueryParamArguments($elementType), static::getDraftArguments(), static::getRevisionArguments());
    }

    public static function getDraftArguments(): array
    {
        return GqlHelper::canQueryDrafts() ? static::baseArgumentsRequiring(ElementCapability::Drafts) : [];
    }

    public static function getRevisionArguments(): array
    {
        return GqlHelper::canQueryRevisions() ? static::baseArgumentsRequiring(ElementCapability::Revisions) : [];
    }

    /**
     * Returns arguments for the base element query params that require the given capability and that GraphQL doesn’t
     * exclude.
     */
    protected static function baseArgumentsRequiring(ElementCapability $capability): array
    {
        $params = array_filter(
            ElementQueryParam::all(ElementQuery::class),
            fn(ElementQueryParam $param): bool => $param->requires === $capability && Gql::of($param) !== null,
        );

        return static::queryParamArguments(Element::class, array_keys($params));
    }
}
