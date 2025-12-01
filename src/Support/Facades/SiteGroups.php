<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Site\SiteGroups
 */
final class SiteGroups extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Site\SiteGroups::class;
    }
}
