<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\ProjectConfig\ProjectConfig
 */
final class ProjectConfig extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\ProjectConfig\ProjectConfig::class;
    }
}
