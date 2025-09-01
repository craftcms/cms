<?php

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CraftCms\Cms\Deprecator\Deprecator
 */
final class Deprecator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Deprecator\Deprecator::class;
    }
}
