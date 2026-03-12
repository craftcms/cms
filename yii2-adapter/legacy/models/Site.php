<?php

declare(strict_types=1);

namespace craft\models;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Data\Site} instead.
 */
class Site extends \CraftCms\Cms\Site\Data\Site
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';
}
