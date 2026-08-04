<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Handlers;

class SiteId extends Site
{
    #[\Override]
    protected string $argumentName = 'siteId';
}
