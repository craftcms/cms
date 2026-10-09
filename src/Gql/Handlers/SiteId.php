<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

/**
 * @since 6.0.0
 */
class SiteId extends Site
{
    #[\Override]
    protected string $argumentName = 'siteId';
}
