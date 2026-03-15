<?php

declare(strict_types=1);

namespace craft\models;

/**
 * @since 3.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Gql\Data\GqlSchema} instead.
 */
class GqlSchema extends \CraftCms\Cms\Gql\Data\GqlSchema
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';
}
